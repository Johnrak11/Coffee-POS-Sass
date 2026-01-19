<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shop;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        // 1. Core Metrics (Paid orders)
        $totalRevenue = Order::where('shop_id', $shop->id)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $totalOrders = Order::where('shop_id', $shop->id)
            ->where('payment_status', 'paid')
            ->count();

        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // 2. Sales Chart Data (Last 7 days)
        $history = Order::where('shop_id', $shop->id)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(6))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // 3. Top Products
        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.shop_id', $shop->id)
            ->where('orders.payment_status', 'paid')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'DESC')
            ->limit(5)
            ->get();

        return response()->json([
            'metrics' => [
                'revenue' => (float) $totalRevenue,
                'orders' => $totalOrders,
                'avg_order_value' => (float) $avgOrderValue,
            ],
            'chart_data' => $history,
            'top_products' => $topProducts
        ]);
    }

    /**
     * Get paginated transactions
     */
    public function transactions(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        // KHQR Batch Check: Check status of recent pending KHQR orders
        $pendingKhqrOrders = Order::where('shop_id', $shop->id)
            ->where('payment_status', 'pending')
            ->whereNotNull('khqr_md5')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();

        if ($pendingKhqrOrders->isNotEmpty()) {
            $md5List = $pendingKhqrOrders->pluck('khqr_md5')->toArray();
            try {
                $bakongService = app(\App\Services\BakongService::class);
                $result = $bakongService->checkStatusBatch($md5List);

                if (isset($result['data']) && is_array($result['data'])) {
                    foreach ($result['data'] as $tx) {
                        if (isset($tx['status']) && $tx['status'] === 'SUCCESS' && isset($tx['md5'])) {
                            Order::where('khqr_md5', $tx['md5'])
                                ->where('payment_status', 'pending')
                                ->update([
                                    'payment_status' => 'paid',
                                    'payment_metadata' => $tx['data'] ?? null
                                ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
            }
        }

        $transactions = \App\Models\Transaction::with(['order.items.product', 'order.tableSession.shopTable'])
            ->whereHas('order', function ($query) use ($shop) {
                $query->where('shop_id', $shop->id);
            });

        // Filter by Order Number
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $transactions->whereHas('order', function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%");
            });
        }

        // Filter by Status
        if ($request->has('status') && $request->status) {
            $status = $request->status;
            if ($status === 'verified') {
                $transactions->where(function ($q) {
                    $q->whereNotNull('verified_at')
                        ->orWhereHas('order', function ($subQ) {
                            $subQ->where('payment_status', 'paid');
                        });
                });
            } elseif ($status === 'failed') {
                $transactions->whereNull('verified_at')
                    ->whereHas('order', function ($subQ) {
                        $subQ->where('payment_status', '!=', 'paid');
                    });
            }
        }

        $transactions = $transactions->orderBy('created_at', 'DESC')
            ->paginate(20);

        return response()->json($transactions);
    }
}
