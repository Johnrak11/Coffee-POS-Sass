<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Models\Shop;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get active orders for kitchen (Queue or Preparing)
     * GET /api/staff/kitchen/{shopSlug}/orders
     */
    public function index($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $orders = $this->orderService->getKitchenOrders($shop->id);

        return response()->json([
            'orders' => $orders
        ]);
    }

    /**
     * Update order status
     * POST /api/staff/kitchen/orders/{orderId}/status
     */
    public function updateStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status' => 'required|in:queue,preparing,served'
        ]);

        $order = \App\Models\Order::findOrFail($orderId);
        $this->orderService->updateFulfillmentStatus($order, $validated['status']);

        return response()->json(['success' => true, 'order' => $order]);
    }
}
