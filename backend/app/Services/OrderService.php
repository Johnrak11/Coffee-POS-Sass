<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TableSession;
use App\Models\Shop;

class OrderService
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Create order from cart
     */
    public function createFromCart(TableSession $session, string $paymentMethod): Order
    {
        $cart = $this->cartService->getCart($session);

        if (count($cart['items']) === 0) {
            throw new \Exception('Cart is empty');
        }

        // Generate order number
        $orderNumber = $this->generateOrderNumber($session->shopTable->shop_id);

        // Determine confirmation status
        $confirmationStatus = ($paymentMethod === 'cash') ? 'pending_confirmation' : 'confirmed';

        // Create order
        $order = Order::create([
            'shop_id' => $session->shopTable->shop_id,
            'table_session_id' => $session->id,
            'order_number' => $orderNumber,
            'total_amount' => $cart['total'],
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'confirmation_status' => $confirmationStatus,
            'fulfillment_status' => 'queue',
        ]);

        // Create order items
        foreach ($cart['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product']['id'],
                'product_variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['product']['price'],
                'extra_price' => $item['variant_extra_price'] ?? 0,
                'subtotal' => ($item['product']['price'] + ($item['variant_extra_price'] ?? 0)) * $item['quantity'],
            ]);
        }

        // Clear cart after order creation
        $this->cartService->clearCart($session);

        return $order;
    }

    /**
     * Create POS order directly (no session)
     */
    public function createPosOrder(int $shopId, array $items, string $paymentMethod, string $paymentCurrency = 'USD', float $receivedAmount = 0, ?int $userId = null): Order
    {
        if (empty($items)) {
            throw new \Exception('Order items are empty');
        }

        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $price = $item['price'];
            $extraPrice = 0;

            // Handle legacy variant_price
            if (isset($item['variant_price'])) {
                $extraPrice += $item['variant_price'];
            }

            // Handle new options
            if (isset($item['options']) && is_array($item['options'])) {
                foreach ($item['options'] as $option) {
                    $extraPrice += ($option['extra_price'] ?? 0);
                }
            }

            $total += ($price + $extraPrice) * $item['quantity'];
        }

        // Generate order number
        $orderNumber = $this->generateOrderNumber($shopId);

        // Fetch Shop Exchange Rate
        $shop = Shop::find($shopId);
        $exchangeRate = $shop ? $shop->exchange_rate : 4100;

        // Convert total to KHR if paid in KHR
        if ($paymentCurrency === 'KHR') {
            // Calculate KHR total from USD total (value of goods)
            // Note: Currently $total is in USD (sum of item prices)
            $total = ceil(($total * $exchangeRate) / 100) * 100;
        }

        // Create order
        $order = Order::create([
            'shop_id' => $shopId,
            'table_session_id' => null, // No session for POS
            'order_number' => $orderNumber,
            'total_amount' => $total,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'cash' ? 'paid' : 'pending',
            'fulfillment_status' => 'queue',
            'payment_currency' => $paymentCurrency,
            'exchange_rate_snapshot' => $exchangeRate,
            'received_amount' => $receivedAmount,
            'user_id' => $userId,
            'confirmation_status' => 'confirmed', // POS orders are always confirmed
        ]);

        // Create order items
        // Create order items
        foreach ($items as $item) {
            $basePrice = $item['price'];
            $extraPrice = $item['variant_price'] ?? 0;

            // Calculate total extra price from options
            if (isset($item['options']) && is_array($item['options'])) {
                foreach ($item['options'] as $option) {
                    $extraPrice += ($option['extra_price'] ?? 0);
                }
            }

            $orderItem = $order->items()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $basePrice,
                'extra_price' => $extraPrice,
                'subtotal' => ($basePrice + $extraPrice) * $item['quantity'],
            ]);

            // Save options
            if (isset($item['options']) && is_array($item['options'])) {
                foreach ($item['options'] as $option) {
                    $orderItem->options()->create([
                        'product_variant_id' => $option['product_variant_id'] ?? null,
                        'group_name' => $option['group_name'],
                        'option_name' => $option['option_name'],
                        'extra_price' => $option['extra_price'] ?? 0,
                    ]);
                }
            }
        }

        return $order;
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(int $shopId): string
    {
        $date = now()->format('Ymd');
        $lastOrder = Order::where('shop_id', $shopId)
            ->where('order_number', 'like', "ORD-{$date}-%")
            ->latest('id')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "ORD-{$date}-{$newNumber}";
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Order $order, string $status): Order
    {
        $order->update(['payment_status' => $status]);
        return $order->fresh();
    }

    /**
     * Update fulfillment status
     */
    public function updateFulfillmentStatus(Order $order, string $status): Order
    {
        $order->update(['fulfillment_status' => $status]);
        return $order->fresh();
    }

    /**
     * Confirm order
     */
    public function confirmOrder(Order $order): Order
    {
        $order->update(['confirmation_status' => 'confirmed']);
        return $order->fresh();
    }

    /**
     * Get orders with filters and pagination
     */
    public function getOrders(int $shopId, array $filters = [], int $perPage = 15)
    {
        $query = Order::with(['items.product', 'items.variant', 'items.options', 'tableSession.shopTable', 'user'])
            ->where('shop_id', $shopId);

        // Filter by Date
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filter by Status
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Search by Order Number
        if (!empty($filters['search'])) {
            $query->where('order_number', 'like', "%{$filters['search']}%");
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get orders for kitchen display
     */
    public function getKitchenOrders(int $shopId): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with(['tableSession.shopTable', 'items.product', 'items.variant', 'items.options'])
            ->where('shop_id', $shopId)
            ->where('shop_id', $shopId)
            ->whereIn('fulfillment_status', ['queue', 'preparing'])
            ->where(function ($query) {
                // Show if confirmed OR confirmation_status is null (legacy/pos)
                $query->where('confirmation_status', 'confirmed')
                    ->orWhereNull('confirmation_status');
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
