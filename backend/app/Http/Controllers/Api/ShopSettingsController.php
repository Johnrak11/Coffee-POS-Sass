<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopSettingsController extends Controller
{
    public function show($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        return response()->json($shop);
    }

    public function update(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'logo_url' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'receipt_footer' => 'nullable|string',
            'currency_symbol' => 'sometimes|string|max:10',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'primary_color' => 'nullable|string|max:7', // Hex color code
            'bakong_account_id' => 'nullable|string|max:100',
            'merchant_name' => 'nullable|string|max:255',
            'merchant_city' => 'nullable|string|max:100',
            'theme_mode' => 'nullable|in:light,dark',
        ]);

        $shop->update($validated);

        return response()->json($shop);
    }
}
