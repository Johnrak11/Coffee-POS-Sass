<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductManagementController extends Controller
{
    public function index($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $products = Product::with(['category', 'variants'])
            ->where('shop_id', $shop->id)
            ->get();

        return response()->json($products);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_available' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.option_name' => 'required|string',
            'variants.*.extra_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($shop, $validated) {
            $product = Product::create([
                'shop_id' => $shop->id,
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'price' => $validated['price'],
                'image_url' => $validated['image_url'],
                'is_available' => $validated['is_available'] ?? true,
            ]);

            if (!empty($validated['variants'])) {
                foreach ($validated['variants'] as $v) {
                    $product->variants()->create($v);
                }
            }

            return response()->json($product->load('variants'), 201);
        });
    }

    public function update(Request $request, $shopSlug, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'is_available' => 'boolean',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_with:variants|string',
            'variants.*.option_name' => 'required_with:variants|string',
            'variants.*.extra_price' => 'required_with:variants|numeric|min:0',
        ]);

        return DB::transaction(function () use ($product, $validated) {
            // Update basic product fields
            $product->update([
                'category_id' => $validated['category_id'] ?? $product->category_id,
                'name' => $validated['name'] ?? $product->name,
                'price' => $validated['price'] ?? $product->price,
                'image_url' => $validated['image_url'] ?? $product->image_url,
                'is_available' => $validated['is_available'] ?? $product->is_available,
            ]);

            // Sync variants (delete old, create new)
            if (isset($validated['variants'])) {
                // Delete existing variants
                $product->variants()->delete();

                // Create new variants
                foreach ($validated['variants'] as $v) {
                    $product->variants()->create($v);
                }
            }

            return response()->json($product->load('variants'));
        });
    }

    public function destroy($shopSlug, $productId)
    {
        $product = Product::findOrFail($productId);
        $product->delete();

        return response()->json(['success' => true]);
    }
}
