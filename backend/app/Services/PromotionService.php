<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\Product;
use Carbon\Carbon;

class PromotionService
{
    /**
     * Calculate and apply ALL applicable promotions for an order.
     * Multiple promotions can apply to different products simultaneously.
     *
     * @param Order $order
     * @param array $cartItems items with product_id, quantity, options
     * @return array Result with discount amount and promotion IDs applied
     */
    public function calculateDiscount(Order $order, $cartItems)
    {
        $activePromotions = Promotion::where('shop_id', $order->shop_id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->get();

        // Track which products have already been discounted to prevent double-discounting
        $discountedProductIds = [];
        $totalDiscount = 0;
        $appliedPromotionIds = [];

        // Apply BOGO promotions first (they're typically more specific)
        foreach ($activePromotions as $promotion) {
            if ($promotion->type === 'buy_x_get_y') {
                $discount = $this->calculateBuyXGetYDiscount($cartItems, $promotion);

                if ($discount > 0) {
                    $totalDiscount += $discount;
                    $appliedPromotionIds[] = $promotion->id;

                    // Mark products as discounted
                    $rules = $promotion->rules;
                    $buyProductIds = $rules['buy_product_ids'] ?? [];
                    $getProductIds = $rules['get_product_ids'] ?? [];
                    $discountedProductIds = array_merge(
                        $discountedProductIds,
                        $buyProductIds,
                        $getProductIds
                    );
                }
            }
        }

        // Then apply percentage discounts to remaining products
        foreach ($activePromotions as $promotion) {
            if ($promotion->type === 'percentage') {
                // Filter out already-discounted products
                $remainingItems = array_filter($cartItems, function ($item) use ($discountedProductIds) {
                    $pid = $item['product_id'] ?? $item['product']['id'];
                    return !in_array($pid, $discountedProductIds);
                });

                if (!empty($remainingItems)) {
                    $discount = $this->calculatePercentageDiscount($order, $promotion, $remainingItems);

                    if ($discount > 0) {
                        $totalDiscount += $discount;
                        $appliedPromotionIds[] = $promotion->id;

                        // Mark applicable products as discounted
                        $rules = $promotion->rules ?? [];
                        $applicableProductIds = $rules['applicable_product_ids'] ?? [];

                        if (empty($applicableProductIds)) {
                            // If no specific products, mark all remaining items
                            foreach ($remainingItems as $item) {
                                $pid = $item['product_id'] ?? $item['product']['id'];
                                $discountedProductIds[] = $pid;
                            }
                        } else {
                            $discountedProductIds = array_merge($discountedProductIds, $applicableProductIds);
                        }
                    }
                }
            }
        }

        return [
            'discount_amount' => $totalDiscount,
            'promotion_id' => !empty($appliedPromotionIds) ? $appliedPromotionIds[0] : null, // For backward compatibility
            'promotion_ids' => $appliedPromotionIds, // New field for multiple promotions
        ];
    }

    /**
     * Calculate percentage discount.
     * Applies to the FULL item price (base + options).
     */
    private function calculatePercentageDiscount(Order $order, Promotion $promotion, $cartItems = [])
    {
        $rules = $promotion->rules ?? [];
        $applicableProductIds = $rules['applicable_product_ids'] ?? [];

        // If no specific products defined, apply to whole order
        if (empty($applicableProductIds)) {
            return $order->total_amount * ($promotion->value / 100);
        }

        // Calculate based on qualifying items (including their options)
        $discountableAmount = 0;
        $itemsToCheck = !empty($cartItems) ? $cartItems : $order->items;

        foreach ($itemsToCheck as $item) {
            $pid = is_array($item)
                ? ($item['product_id'] ?? $item['product']['id'])
                : $item->product_id;

            if (in_array($pid, $applicableProductIds)) {
                // Get base price
                $basePrice = is_array($item)
                    ? ($item['product']['price'] ?? 0)
                    : ($item->product->price ?? 0);

                // Calculate options price
                $optionsPrice = 0;
                if (is_array($item)) {
                    $options = $item['options'] ?? [];
                    foreach ($options as $opt) {
                        $optionsPrice += ($opt['extra_price'] ?? 0);
                    }
                }

                // Full unit price = base + options
                $unitPrice = $basePrice + $optionsPrice;
                $qty = is_array($item) ? $item['quantity'] : $item->quantity;

                $discountableAmount += $unitPrice * $qty;
            }
        }

        return $discountableAmount * ($promotion->value / 100);
    }

    /**
     * Calculate Buy X Get Y discount.
     * CRITICAL: Only discounts the BASE PRICE, NOT options.
     * Options on free items are still charged.
     */
    private function calculateBuyXGetYDiscount($cartItems, Promotion $promotion)
    {
        $rules = $promotion->rules;
        if (!$rules) return 0;

        $buyProductIds = $rules['buy_product_ids'] ?? [];
        $buyQty = $rules['buy_quantity'] ?? 1;
        $getProductIds = $rules['get_product_ids'] ?? [];
        $getQty = $rules['get_quantity'] ?? 1;
        $discountPercent = $rules['discount_percent'] ?? 100;

        if (empty($buyProductIds) || empty($getProductIds)) {
            return 0;
        }

        // Collect all items and their BASE prices (excluding options)
        $buyItemCount = 0;
        $possibleGetBasePrices = [];

        foreach ($cartItems as $item) {
            $pid = $item['product_id'] ?? $item['product']['id'];
            $qty = $item['quantity'];

            // Get BASE price only (not including options)
            $basePrice = $item['product']['price'] ?? 0;

            if (in_array($pid, $buyProductIds)) {
                $buyItemCount += $qty;
            }

            if (in_array($pid, $getProductIds)) {
                // Add base price for each unit (options are NOT included in discount)
                for ($i = 0; $i < $qty; $i++) {
                    $possibleGetBasePrices[] = $basePrice;
                }
            }
        }

        if (empty($possibleGetBasePrices)) {
            return 0;
        }

        $totalDiscount = 0;

        // Check if Buy and Get products are the same (e.g., Buy 1 Get 1 on same product)
        if (!empty(array_intersect($buyProductIds, $getProductIds))) {
            // Same product BOGO logic
            // Group size = buyQty + getQty
            // For every (buyQty + getQty) items, discount getQty items' BASE prices

            $totalRelevantItems = 0;
            $allRelevantBasePrices = [];

            foreach ($cartItems as $item) {
                $pid = $item['product_id'] ?? $item['product']['id'];

                if (in_array($pid, $buyProductIds)) {
                    $basePrice = $item['product']['price'] ?? 0;
                    $totalRelevantItems += $item['quantity'];

                    for ($i = 0; $i < $item['quantity']; $i++) {
                        $allRelevantBasePrices[] = $basePrice;
                    }
                }
            }

            // Sort by price (discount cheapest items first for maximum fairness)
            sort($allRelevantBasePrices);

            $groupSize = $buyQty + $getQty;
            $numGroups = floor($totalRelevantItems / $groupSize);
            $numDiscountable = $numGroups * $getQty;

            // Apply discount to BASE prices only
            for ($i = 0; $i < $numDiscountable && $i < count($allRelevantBasePrices); $i++) {
                $totalDiscount += $allRelevantBasePrices[$i] * ($discountPercent / 100);
            }
        } else {
            // Different products (Buy Coffee, Get Cookie)
            // For every buyQty of buy_product, discount getQty of get_product BASE prices

            $sets = floor($buyItemCount / $buyQty);
            $maxGetItems = $sets * $getQty;

            // Sort get items by base price (discount cheapest first)
            sort($possibleGetBasePrices);

            $count = 0;
            foreach ($possibleGetBasePrices as $basePrice) {
                if ($count < $maxGetItems) {
                    $totalDiscount += $basePrice * ($discountPercent / 100);
                    $count++;
                } else {
                    break;
                }
            }
        }

        return $totalDiscount;
    }
}
