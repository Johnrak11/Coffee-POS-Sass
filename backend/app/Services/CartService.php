<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\TableSession;
use App\Models\Product;

class CartService
{
    /**
     * Add item to cart
     */
    public function addItem(TableSession $session, int $productId, int $quantity = 1, ?string $notes = null): CartItem
    {
        // Check if item already exists
        $existingItem = CartItem::where('table_session_id', $session->id)
            ->where('product_id', $productId)
            ->where('notes', $notes)
            ->first();

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
            return true;
        }

        $cartItem->update(['quantity' => $quantity]);
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

    /**
     * Get cart with calculated total
     */
    public function getCart(TableSession $session): array
    {
        $cartItems = CartItem::with('product')
            ->where('table_session_id', $session->id)
            ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return [
            'items' => $cartItems,
            'total' => $total,
            'item_count' => $cartItems->sum('quantity'),
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
