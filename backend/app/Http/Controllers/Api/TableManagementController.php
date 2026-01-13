<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableManagementController extends Controller
{
    public function index($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $tables = ShopTable::where('shop_id', $shop->id)
            ->orderBy('table_number', 'ASC')
            ->get();

        return response()->json($tables);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'table_number' => 'required|string|max:10',
        ]);

        $table = ShopTable::create([
            'shop_id' => $shop->id,
            'table_number' => $validated['table_number'],
            'qr_token' => Str::random(32), // Generate unique token
            'status' => 'available',
        ]);

        return response()->json([
            'success' => true,
            'table' => $table
        ]);
    }

    public function update(Request $request, $shopSlug, $tableId)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $table = ShopTable::where('shop_id', $shop->id)->findOrFail($tableId);

        $validated = $request->validate([
            'table_number' => 'required|string|max:10',
            'status' => 'nullable|in:available,occupied',
        ]);

        $table->update($validated);

        return response()->json([
            'success' => true,
            'table' => $table
        ]);
    }

    public function destroy($shopSlug, $tableId)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $table = ShopTable::where('shop_id', $shop->id)->findOrFail($tableId);

        $table->delete();

        return response()->json(['success' => true]);
    }
}
