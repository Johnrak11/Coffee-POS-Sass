<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\TableSessionService;
use App\Services\KhqrService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;
    protected $sessionService;
    protected $khqrService;

    public function __construct(
        OrderService $orderService,
        TableSessionService $sessionService,
        KhqrService $khqrService
    ) {
        $this->orderService = $orderService;
        $this->sessionService = $sessionService;
        $this->khqrService = $khqrService;
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

        try {
            $order = $this->orderService->createFromCart($session, $validated['payment_method']);

            $response = [
                'success' => true,
                'order' => $order,
            ];

            // Generate KHQR if payment method is khqr
            if ($validated['payment_method'] === 'khqr') {
                $khqr = $this->khqrService->generateKhqr($order);
                $response['khqr'] = $khqr;
            }

            // Mark table as occupied and update session
            $this->sessionService->markTableOccupied($session);
            $session->update(['status' => 'ordering']);

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
        $order = \App\Models\Order::with('transaction')->find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json([
            'order_number' => $order->order_number,
            'amount' => (float) $order->total_amount,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'khqr_string' => $order->transaction?->khqr_string,
            'verified_at' => $order->transaction?->verified_at,
        ]);
    }
}
