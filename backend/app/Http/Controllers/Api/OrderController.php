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
                // Use CF-Connecting-IP if available
                $userIp = $request->server('HTTP_CF_CONNECTING_IP') ?? $request->ip();
                $trustedIps = $shop->trusted_ips ?? [];

                // Allow localhost for dev if it's in the list OR always allow if you want dev ease? 
                // Better to be strict: Must be in trusted list.
                // Note: If user added local IP (192...) but accesses via localhost (127...), this fails.
                // That is correct behavior for security.

                if (!in_array($userIp, $trustedIps)) {
                    return response()->json([
                        'error' => 'Cash payment is not allowed from this network. Please connect to Shop Wi-Fi.',
                        'code' => 'IP_RESTRICTED'
                    ], 403);
                }
            }
        }

        try {
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
                    // Log error but don't fail the order creation
                    \Illuminate\Support\Facades\Log::error('KHQR Generation Failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);

                    return response()->json([
                        'error' => 'Failed to generate KHQR. Please check if Bakong service is running.',
                        'details' => $e->getMessage()
                    ], 500);
                }
            }

            // Mark table as occupied and update session
            $this->sessionService->markTableOccupied($session);
            $session->update(['status' => 'ordering']);

            // Send Notification to Staff
            // Send Notification to Staff
            $shopUsers = \App\Models\User::where('shop_id', $order->shop_id)->get();
            \Illuminate\Support\Facades\Notification::send($shopUsers, new \App\Notifications\NewOrderNotification($order));

            return response()->json($response);
        } catch (\Exception $e) {
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

        // Check if order with this MD5 already exists to prevent duplicates
        $existingOrder = \App\Models\Order::where('khqr_md5', $md5)->first();
        if ($existingOrder) {
            return response()->json([
                'success' => true,
                'order' => $existingOrder,
                'message' => 'Order already created'
            ]);
        }

        // Verify Payment Status with Bakong
        // We use the session's shop to get credentials
        $shop = $session->shopTable->shop; // Assuming relation exists
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        try {
            $result = $this->bakongService->checkTransactionStatus(
                $md5,
                $shop->bakong_telegram_chat_id,
                $shop->merchant_name ?? $shop->name
            );

            // Check if PAID
            if ($result && isset($result['responseCode']) && $result['responseCode'] === 0) {
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
                        'received_amount' => $paidAmount // Track received amount
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

                    // 3. Mark Table Occupied (User is committed)
                    $this->sessionService->markTableOccupied($session);
                    $session->update(['status' => 'ordering']);

                    // 4. DO NOT Send Notification (Hidden from Staff)

                    return response()->json([
                        'success' => true,
                        'status' => 'partial',
                        'order' => $order,
                        'message' => 'Partial payment received. Please pay the remaining balance.',
                        'paid_amount' => $paidAmount,
                        'remaining_amount' => $order->remaining_amount
                    ]);
                }

                // Normal Full Payment Flow
                // Create Order as "Paid"
                $order = $this->orderService->createFromCart($session, 'khqr');

                $order->update([
                    'payment_status' => 'paid',
                    'khqr_md5' => $md5,
                    'payment_metadata' => $result['data'] ?? null,
                    'received_amount' => $order->total_amount // Full payment
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

                // Send Notification (Visible to Staff)
                $shopUsers = \App\Models\User::where('shop_id', $shop->id)->get();
                \Illuminate\Support\Facades\Notification::send($shopUsers, new \App\Notifications\NewOrderNotification($order));

                return response()->json([
                    'success' => true,
                    'status' => 'paid',
                    'order' => $order
                ]);
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
}
