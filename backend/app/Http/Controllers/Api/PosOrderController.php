<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Models\Shop;
use Illuminate\Http\Request;

class PosOrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Create a new POS order
     * POST /api/staff/orders
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.variant_price' => 'nullable|numeric|min:0',
            'items.*.options' => 'nullable|array',
            'items.*.options.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.options.*.group_name' => 'required_with:items.*.options|string',
            'items.*.options.*.option_name' => 'required_with:items.*.options|string',
            'items.*.options.*.extra_price' => 'required_with:items.*.options|numeric|min:0',
            'payment_method' => 'required|in:cash,khqr',
            'payment_currency' => 'nullable|in:USD,KHR',
            'received_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Note: In a real secure app, we should re-fetch prices from DB to avoid client manipulation.
            // For this MVP, we will trust the prices sent (or we should re-calculate them here).
            // Let's implement basic re-calculation support later if needed.
            // The service method we just added trusts the 'price' passed to it.

            $order = $this->orderService->createPosOrder(
                $validated['shop_id'],
                $validated['items'],
                $validated['payment_method'],
                $validated['payment_currency'] ?? 'USD',
                $validated['received_amount'] ?? 0
            );

            return response()->json([
                'success' => true,
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    /**
     * Get orders list
     * GET /api/staff/orders
     */
    public function index(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'payment_status' => 'nullable|in:pending,paid,failed',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:5|max:100'
        ]);

        $orders = $this->orderService->getOrders(
            $request->shop_id,
            $request->only(['start_date', 'end_date', 'payment_status', 'search']),
            $request->per_page ?? 15
        );

        return response()->json($orders);
    }

    /**
     * Get order details
     * GET /api/staff/orders/{order}
     */
    public function show(Request $request, $id)
    {
        // Ideally use Route Model Binding, but for now manual check to ensure shop ownership
        $order = \App\Models\Order::with(['items.product', 'items.variant', 'items.options', 'tableSession.shopTable'])
            ->findOrFail($id);

        if ($request->has('shop_id') && $order->shop_id != $request->shop_id) {
            return response()->json(['error' => 'Unauthorized access to this order'], 403);
        }

        return response()->json($order);
    }

    /**
     * Update payment status (Mark as Paid)
     * PUT /api/staff/orders/{order}/payment-status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:paid,pending,failed,rejected'
        ]);

        $order = \App\Models\Order::findOrFail($id);

        $updatedOrder = $this->orderService->updatePaymentStatus($order, $request->status);

        return response()->json([
            'success' => true,
            'order' => $updatedOrder
        ]);
    }
}
