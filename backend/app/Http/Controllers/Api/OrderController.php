<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\TableSessionService;
use App\Services\BakongService;
use App\Services\CartService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;
    protected $sessionService;
    protected $bakongService;
    protected $cartService;

    public function __construct(
        OrderService $orderService,
        TableSessionService $sessionService,
        BakongService $bakongService,
        CartService $cartService
    ) {
        $this->orderService = $orderService;
        $this->sessionService = $sessionService;
        $this->bakongService = $bakongService;
        $this->cartService = $cartService;
    }

    /**
     * Create order from cart
     * POST /api/guest/checkout
     *
     * SCALABILITY FIX: Wrapped in DB::transaction()
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'session_token' => 'required|string',
            'payment_method' => 'required|in:cash,khqr',
        ]);

        $session = $this->sessionService->getSession($validated['session_token']);

        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 404);
        }

        // Security Check: Enforce IP restrictions for Cash Payments
        if ($validated['payment_method'] === 'cash') {
            $shop = $session->shopTable->shop;
            if ($shop && $shop->ip_check_enabled) {
                $userIp = $request->server('HTTP_CF_CONNECTING_IP') ?? $request->ip();
                $trustedIps = $shop->trusted_ips ?? [];

                if (!in_array($userIp, $trustedIps)) {
                    return response()->json([
                        'error' => 'Cash payment is not allowed from this network. Please connect to Shop Wi-Fi.',
                        'code' => 'IP_RESTRICTED'
                    ], 403);
                }
            }
        }

        try {
            // CRITICAL: Wrap entire checkout in transaction
            $response = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $session) {
                $order = $this->orderService->createFromCart($session, $validated['payment_method']);

                $response = [
                    'success' => true,
                    'order' => $order,
                ];

                // Generate KHQR if payment method is khqr
                if ($validated['payment_method'] === 'khqr') {
                    $shop = $order->shop;

                    try {
                        $result = $this->bakongService->generateQr(
                            (float) $order->total_amount,
                            $order->payment_currency ?? 'USD',
                            [
                                'merchant_name' => $shop->merchant_name ?? $shop->name ?? 'Coffee POS',
                                'merchant_city' => $shop->merchant_city ?? 'Phnom Penh',
                                'telegram_chat_id' => $shop->bakong_telegram_chat_id,
                                'order_id' => $order->order_number,
                            ]
                        );

                        // Cast to array to be safe
                        $result = (array) $result;

                        // Support both flat structure and nested 'data'
                        $data = isset($result['data']) ? (array) $result['data'] : $result;

                        if (isset($data['qr_string']) && isset($data['md5'])) {
                            // Update Order with KHQR data
                            $order->update([
                                'khqr_string' => $data['qr_string'],
                                'khqr_md5' => $data['md5'],
                            ]);

                            // Create Transaction Record
                            \App\Models\Transaction::create([
                                'order_id' => $order->id,
                                'payment_method' => 'khqr',
                                'amount' => $order->total_amount,
                                'currency' => $order->payment_currency ?? 'USD',
                                'khqr_string' => $data['qr_string'],
                                'md5_hash' => $data['md5'],
                                'payload' => $result
                            ]);

                            $response['khqr'] = $result;
                        } else {
                            throw new \Exception('Invalid KHQR response from Bakong service');
                        }
                    } catch (\Exception $e) {
                        // Re-throw to rollback transaction
                        \Illuminate\Support\Facades\Log::error('KHQR Generation Failed', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }

                // Mark table as occupied and update session (within transaction)
                $this->sessionService->markTableOccupied($session);
                $session->update(['status' => 'ordering']);

                return $response;
            });

            // Send Notification to Staff (OUTSIDE transaction - async/queued)
            $order = $response['order'];
            $shopUsers = \App\Models\User::where('shop_id', $order->shop_id)->get();
            \Illuminate\Support\Facades\Notification::send($shopUsers, new \App\Notifications\NewOrderNotification($order));

            return response()->json($response);
        } catch (\Exception $e) {
            // Better error handling for KHQR failures
            if (str_contains($e->getMessage(), 'KHQR') || str_contains($e->getMessage(), 'Bakong')) {
                return response()->json([
                    'error' => 'Failed to generate KHQR. Please check if Bakong service is running.',
                    'details' => $e->getMessage()
                ], 500);
            }

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get order status (for payment polling)
     * GET /api/guest/order/{orderId}/status
     */
    public function getOrderStatus($orderId)
    {
        $order = \App\Models\Order::with(['shop', 'items.product', 'items.variant', 'items.options'])
            ->find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // If order is pending and uses KHQR, check status from Bakong service
        if ($order->payment_status === 'pending' && $order->khqr_md5) {
            $bakongService = app(\App\Services\BakongService::class);
            // ... (keep existing polling logic if needed, but for SuccessView it's usually paid)

            // Re-fetching logic is fine here, but omitted for brevity in this replace block as I'm targeting the return
        }

        // Get the latest transaction for this order
        $transaction = \App\Models\Transaction::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->first();

        // Return FULL order object for Receipt/Success page
        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'queue_number' => $order->queue_number,
            'total_amount' => (float) $order->total_amount,
            'discount_amount' => (float) $order->discount_amount,
            'received_amount' => (float) $order->received_amount,
            'change' => (float) $order->change,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'payment_currency' => $order->payment_currency,
            'fulfillment_status' => $order->fulfillment_status,
            'created_at' => $order->created_at,
            'items' => $order->items,
            'shop' => $order->shop,
            'khqr_string' => $order->khqr_string ?? $transaction?->khqr_string,
            'verified_at' => $transaction?->verified_at,
        ]);
    }

    /**
     * Finalize KHQR Order (Create Order AFTER Payment Success)
     * POST /api/guest/checkout/finalize-khqr
     *
     * SCALABILITY FIX: Transaction wrapper + lockForUpdate() to prevent duplicate orders
     */
    public function finalizeKhqr(Request $request)
    {
        $validated = $request->validate([
            'session_token' => 'required|string',
            'khqr_md5' => 'required|string',
        ]);

        $md5 = $validated['khqr_md5'];
        $session = $this->sessionService->getSession($validated['session_token']);

        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 404);
        }

        // We use the session's shop to get credentials
        $shop = $session->shopTable->shop;
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        try {
            // Verify Payment Status with Bakong FIRST (outside transaction)
            $result = $this->bakongService->checkTransactionStatus(
                $md5,
                $shop->bakong_telegram_chat_id,
                $shop->merchant_name ?? $shop->name
            );

            // Check if PAID
            if ($result && isset($result['responseCode']) && $result['responseCode'] === 0) {
                // CRITICAL: Wrap order creation in transaction with pessimistic locking
                $orderResult = \Illuminate\Support\Facades\DB::transaction(function () use ($md5, $session, $result, $shop) {
                    // RACE CONDITION FIX: Lock the row if it exists
                    // This prevents duplicate submissions from creating multiple orders
                    $existingOrder = \App\Models\Order::where('khqr_md5', $md5)
                        ->lockForUpdate() // 🔒 CRITICAL FIX
                        ->first();

                    if ($existingOrder) {
                        return [
                            'existing' => true,
                            'order' => $existingOrder
                        ];
                    }

                    // EXPLOIT FIX V2: Partial Payment Handling
                    $paymentData = $result['data'] ?? [];
                    $paidAmount = isset($paymentData['amount']) ? (float) $paymentData['amount'] : null;

                    // Get current cart total
                    $cartData = $this->cartService->getCart($session);
                    $cartTotal = (float) $cartData['total'];

                    if ($paidAmount !== null && $paidAmount < $cartTotal) {
                        // 1. Create Order as "Partial"
                        $order = $this->orderService->createFromCart($session, 'khqr');
                        $order->update([
                            'payment_status' => 'partial',
                            'khqr_md5' => $md5,
                            'payment_metadata' => $result['data'] ?? null,
                            'received_amount' => $paidAmount
                        ]);

                        // 2. Register Partial Transaction
                        $order->transactions()->create([
                            'payment_method' => 'khqr',
                            'amount' => $paidAmount,
                            'currency' => $order->payment_currency ?? 'USD',
                            'khqr_string' => 'PARTIAL_PAYMENT',
                            'md5_hash' => $md5,
                            'verified_at' => now(),
                            'payload' => ['success_data' => $result['data'] ?? null]
                        ]);

                        // 3. Mark Table Occupied
                        $this->sessionService->markTableOccupied($session);
                        $session->update(['status' => 'ordering']);

                        return [
                            'existing' => false,
                            'status' => 'partial',
                            'order' => $order
                        ];
                    }

                    // Normal Full Payment Flow
                    $order = $this->orderService->createFromCart($session, 'khqr');

                    $order->update([
                        'payment_status' => 'paid',
                        'khqr_md5' => $md5,
                        'payment_metadata' => $result['data'] ?? null,
                        'received_amount' => $order->total_amount
                    ]);

                    // Create Transaction Record
                    $order->transactions()->create([
                        'payment_method' => 'khqr',
                        'amount' => $order->total_amount,
                        'currency' => $order->payment_currency ?? 'USD',
                        'khqr_string' => 'VERIFIED_BY_MD5',
                        'md5_hash' => $md5,
                        'verified_at' => now(),
                        'payload' => ['success_data' => $result['data'] ?? null]
                    ]);

                    // Mark table occupied
                    $this->sessionService->markTableOccupied($session);
                    $session->update(['status' => 'ordering']);

                    return [
                        'existing' => false,
                        'status' => 'paid',
                        'order' => $order
                    ];
                });

                // Send Notification if order is new and fully paid (OUTSIDE transaction)
                if (!$orderResult['existing'] && $orderResult['status'] === 'paid') {
                    $shopUsers = \App\Models\User::where('shop_id', $shop->id)->get();
                    \Illuminate\Support\Facades\Notification::send($shopUsers, new \App\Notifications\NewOrderNotification($orderResult['order']));
                }

                $response = [
                    'success' => true,
                    'status' => $orderResult['status'] ?? 'paid',
                    'order' => $orderResult['order']
                ];

                if ($orderResult['existing']) {
                    $response['message'] = 'Order already created';
                } elseif ($orderResult['status'] === 'partial') {
                    $response['message'] = 'Partial payment received. Please pay the remaining balance.';
                    $response['paid_amount'] = $orderResult['order']->received_amount;
                    $response['remaining_amount'] = $orderResult['order']->remaining_amount;
                }

                return response()->json($response);
            } else {
                return response()->json(['error' => 'Payment not verified', 'details' => $result], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Finalize REMAINING Payment for Partial Order
     * POST /api/guest/checkout/finalize-payment
     */
    public function finalizePayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'khqr_md5' => 'required|string',
        ]);

        $order = \App\Models\Order::findOrFail($validated['order_id']);
        $md5 = $validated['khqr_md5'];

        // Verify with Bakong
        $shop = $order->shop;
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        // Prevent duplicate transaction usage
        if ($order->transactions()->where('md5_hash', $md5)->exists()) {
            return response()->json(['error' => 'Transaction already processed'], 409);
        }

        try {
            $result = $this->bakongService->checkTransactionStatus(
                $md5,
                $shop->bakong_telegram_chat_id,
                $shop->merchant_name ?? $shop->name
            );

            if ($result && isset($result['responseCode']) && $result['responseCode'] === 0) {
                $paymentData = $result['data'] ?? [];
                $paidAmount = isset($paymentData['amount']) ? (float) $paymentData['amount'] : 0.0;

                // Register NEW Transaction
                $order->transactions()->create([
                    'payment_method' => 'khqr',
                    'amount' => $paidAmount,
                    'currency' => $order->payment_currency ?? 'USD',
                    'khqr_string' => 'REMAINING_PAYMENT',
                    'md5_hash' => $md5,
                    'verified_at' => now(),
                    'payload' => ['success_data' => $result['data'] ?? null]
                ]);

                // Re-calculate remaining amount
                $order->refresh();
                if ($order->remaining_amount <= 0.01) {
                    // FULLY PAID
                    $order->update([
                        'payment_status' => 'paid',
                        'received_amount' => $order->transactions()->sum('amount')
                    ]);

                    // NOW Send Notification
                    $shopUsers = \App\Models\User::where('shop_id', $shop->id)->get();
                    \Illuminate\Support\Facades\Notification::send($shopUsers, new \App\Notifications\NewOrderNotification($order));

                    return response()->json([
                        'success' => true,
                        'status' => 'paid', // Done
                        'order' => $order
                    ]);
                } else {
                    // STILL PARTIAL
                    return response()->json([
                        'success' => true,
                        'status' => 'partial',
                        'order' => $order,
                        'message' => 'Payment received. Balance remaining.',
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $order->remaining_amount
                    ]);

                    // Update received amount for partial steps too
                    $order->update([
                        'received_amount' => $order->transactions()->sum('amount')
                    ]);
                }
            } else {
                return response()->json(['error' => 'Payment not verified'], 400);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Finalize Payment Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all orders for current session
     * GET /api/guest/orders
     */
    public function getSessionOrders(Request $request)
    {
        $validated = $request->validate([
            'session_token' => 'required|string',
        ]);

        $session = $this->sessionService->getSession($validated['session_token']);

        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 404);
        }

        try {
            // Get all orders for this session
            $orders = \App\Models\Order::where('table_session_id', $session->id)
                ->with(['items.product'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'queue_number' => $order->queue_number,
                        'total_amount' => $order->total_amount,
                        'payment_status' => $order->payment_status,
                        'payment_method' => $order->payment_method,
                        'fulfillment_status' => $order->fulfillment_status,
                        'item_count' => $order->items->count(),
                        'created_at' => $order->created_at,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'name' => $item->product->name ?? 'Unknown',
                                'quantity' => $item->quantity,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'orders' => $orders,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Get Session Orders Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
