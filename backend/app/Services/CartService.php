<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\TableSession;
use App\Models\Product;

class CartService
{
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    // ... (rest of CRUD methods remain the same) ...

    /**
     * Add item to cart
     */
    public function addItem(TableSession $session, int $productId, int $quantity = 1, ?string $notes = null, array $options = []): CartItem
    {
        // Check if item already exists (Matching options too)
        // Note: JSON matching in SQL can be tricky. We'll do a simple collection filter logic or precise match if possible.
        // For simplicity: We will query by product_id/session, then filter in PHP for options match.
        // If high volume, move to specific hash column.

        $candidates = CartItem::where('table_session_id', $session->id)
            ->where('product_id', $productId)
            ->where('notes', $notes)
            ->get();

        $existingItem = $candidates->first(function ($item) use ($options) {
            // Compare options arrays
            // Assuming options structure is consistent (e.g. sorted by group_id/option_id)
            // Frontend should sort them, but let's be loose: compare JSON strings essentially or array diff.
            $currentOptions = $item->options ?? [];
            return json_encode($currentOptions) === json_encode($options);
        });

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem->fresh();
        }

        // Create new cart item
        return CartItem::create([
            'table_session_id' => $session->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'notes' => $notes,
            'options' => $options,
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(int $cartItemId, int $quantity): bool
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return false;
        }

        if ($quantity <= 0) {
            $cartItem->delete();
        } else {
            $cartItem->update(['quantity' => $quantity]);
        }

        return true;
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $cartItemId): bool
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return false;
        }

        $cartItem->delete();
        return true;
    }

    // ... (updateQuantity, removeItem remain same)

    /**
     * Get cart with calculated total and promotions
     */
    public function getCart(TableSession $session): array
    {
        $cartItems = CartItem::with('product')
            ->where('table_session_id', $session->id)
            ->get();

        $subtotal = $cartItems->sum(function ($item) {
            $unitPrice = $item->product->price;

            // Add Option Prices
            if (!empty($item->options) && is_array($item->options)) {
                foreach ($item->options as $opt) {
                    $unitPrice += ($opt['extra_price'] ?? 0);
                }
            }

            return $unitPrice * $item->quantity;
        });

        // Mock a temporary Order object for calculation
        // We need the shop_id, which we can get from the session
        $shopId = $session->shop_table_id ? $session->shopTable->shop_id : null;
        // Note: Relation might be shopTable->shop_id properly
        if (!$shopId) {
            // Fallback if relation not loaded deep enough
            $shopId = \App\Models\ShopTable::where('id', $session->shop_table_id)->value('shop_id');
        }

        $mockOrder = new \App\Models\Order([
            'shop_id' => $shopId,
            'total_amount' => $subtotal
        ]);

        // Format cart items for promotion calculation
        // Ensure product data is accessible in array format
        $formattedCartItems = $cartItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'options' => $item->options ?? [],
                'product' => [
                    'id' => $item->product->id,
                    'price' => $item->product->price,
                ]
            ];
        })->toArray();

        $promotionResult = $this->promotionService->calculateDiscount($mockOrder, $formattedCartItems);
        $discountAmount = $promotionResult['discount_amount'];
        $promotionId = $promotionResult['promotion_id'];

        $finalTotal = max(0, $subtotal - $discountAmount);

        // Check for active partial order
        $partialOrder = \App\Models\Order::where('table_session_id', $session->id)
            ->where('payment_status', 'partial')
            ->latest()
            ->first();

        // Append remaining_amount if order exists
        if ($partialOrder) {
            $partialOrder->append('remaining_amount');
        }

        return [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'promotion_id' => $promotionId,
            'total' => $finalTotal,
            'item_count' => $cartItems->sum('quantity'),
            'partial_order' => $partialOrder
        ];
    }

    /**
     * Clear cart
     */
    public function clearCart(TableSession $session): void
    {
        CartItem::where('table_session_id', $session->id)->delete();
    }
}
