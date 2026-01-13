<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;

class CategoryManagementController extends Controller
{
    public function index($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $categories = Category::where('shop_id', $shop->id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'integer'
        ]);

        $category = Category::create([
            'shop_id' => $shop->id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'Coffee',
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, $categoryId)
    {
        $category = Category::findOrFail($categoryId);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'integer'
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $category->delete();

        return response()->json(['success' => true]);
    }
}
