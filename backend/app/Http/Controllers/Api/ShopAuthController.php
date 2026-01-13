<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShopAuthController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'slug' => 'required',
            'password' => 'nullable' // Optional for now if shop has no password
        ]);

        $shop = \App\Models\Shop::where('slug', $request->slug)->first();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ], 404);
        }

        // 1. If shop has NO password set (legacy/new shop), allow access but warn
        if (!$shop->password) {
            return response()->json([
                'success' => true,
                'shop' => [
                    'name' => $shop->name,
                    'slug' => $shop->slug,
                    'logo_url' => $shop->logo_url
                ],
                // Return a token or something? 
                // For this simple flow, frontend just proceeds if success=true
            ]);
        }

        // 2. If password IS set, verify it
        if (!\Illuminate\Support\Facades\Hash::check($request->password, $shop->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Shop Password'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'shop' => [
                'name' => $shop->name,
                'slug' => $shop->slug,
                'logo_url' => $shop->logo_url
            ]
        ]);
    }
}
