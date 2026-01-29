<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PromotionManagementController extends Controller
{
    public function index($shopSlug)
    {
        $shop = \App\Models\Shop::where('slug', $shopSlug)->firstOrFail();

        $promotions = $shop->promotions()
            ->latest()
            ->get();

        return response()->json($promotions);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = \App\Models\Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'required|in:percentage,fixed,buy_x_get_y',
            'value' => 'nullable|numeric|min:0',
            'rules' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean'
        ]);

        $promotion = $shop->promotions()->create($validated);

        return response()->json($promotion, 201);
    }

    public function update(Request $request, $shopSlug, $id)
    {
        $shop = \App\Models\Shop::where('slug', $shopSlug)->firstOrFail();
        $promotion = $shop->promotions()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'sometimes|in:percentage,fixed,buy_x_get_y',
            'value' => 'nullable|numeric|min:0',
            'rules' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean'
        ]);

        $promotion->update($validated);

        return response()->json($promotion);
    }

    public function destroy($shopSlug, $id)
    {
        $shop = \App\Models\Shop::where('slug', $shopSlug)->firstOrFail();
        $promotion = $shop->promotions()->findOrFail($id);

        $promotion->delete();

        return response()->json(['message' => 'Promotion deleted']);
    }
}
