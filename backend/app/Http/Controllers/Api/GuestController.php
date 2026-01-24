<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TableSessionService;
use App\Services\CartService;
use App\Models\Product;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    protected $sessionService;
    protected $cartService;

    public function __construct(TableSessionService $sessionService, CartService $cartService)
    {
        $this->sessionService = $sessionService;
        $this->cartService = $cartService;
    }

    /**
     * Scan QR code and create/get session
     * POST /api/guest/scan/{qrToken}
     */
    public function scanTable($qrToken)
    {
        $existingSessionToken = request()->input('session_token');
        $session = $this->sessionService->scanTable($qrToken, $existingSessionToken);

        if (!$session) {
            return response()->json(['error' => 'Invalid QR code'], 404);
        }

        return response()->json([
            'session_token' => $session->session_token,
            'table_number' => $session->shopTable->table_number ?? 'N/A',
            'shop' => [
                'name' => $session->shopTable->shop?->name ?? 'Unknown Shop',
                'slug' => $session->shopTable->shop?->slug ?? '',
                'logo_url' => $session->shopTable->shop?->logo_url ?? null,
                'cash_payment_allowed' => (function () use ($session) {
                    $shop = $session->shopTable->shop;
                    if (!$shop) return false;

                    if (!$shop->ip_check_enabled) return true;

                    $userIp = request()->ip();
                    $trustedIps = $shop->trusted_ips ?? [];

                    return in_array($userIp, $trustedIps);
                })(),
            ],
        ]);
    }

    /**
     * Get menu for shop
     * GET /api/guest/menu/{shopSlug}
     */
    public function getMenu($shopSlug)
    {
        $shop = \App\Models\Shop::where('slug', $shopSlug)->first();

        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        // Security: Only expose necessary public shop info
        $publicShopData = [
            'name' => $shop->name,
            'slug' => $shop->slug,
            'logo_url' => $shop->logo_url,
            'address' => $shop->address,
            'phone' => $shop->phone,
            'currency_symbol' => $shop->currency_symbol,
            'exchange_rate' => $shop->exchange_rate,
            'primary_color' => $shop->primary_color,
            'theme_mode' => $shop->theme_mode,
            'merchant_name' => $shop->merchant_name, // Needed for KHQR display? Maybe.
            'merchant_city' => $shop->merchant_city,
            // 'is_ip_check_enabled' => $shop->ip_check_enabled, // Maybe useful for frontend to know if restriction exists
        ];

        $categories = $shop->categories()
            ->with([
                'products' => function ($query) {
                    $query->available()->with('variants');
                }
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'shop' => $publicShopData,
            'categories' => $categories,
        ]);
    }

    /**
     * Check if current IP allows cash payment
     * GET /api/guest/check-access/{shopSlug}
     */
    public function checkAccess(Request $request, $shopSlug)
    {
        $shop = \App\Models\Shop::where('slug', $shopSlug)->first();

        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        $allowCash = true;

        if ($shop->ip_check_enabled) {
            $userIp = $request->server('HTTP_CF_CONNECTING_IP') ?? $request->ip();
            $trustedIps = $shop->trusted_ips ?? [];
            $allowCash = in_array($userIp, $trustedIps);
        }

        return response()->json([
            'cash_payment_allowed' => $allowCash,
        ]);
    }

    /**
     * Add item to cart
     * POST /api/guest/cart/add
     */
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'session_token' => 'required|string',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $session = $this->sessionService->getSession($validated['session_token']);

        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 404);
        }

        $cartItem = $this->cartService->addItem(
            $session,
            $validated['product_id'],
            $validated['quantity'] ?? 1,
            $validated['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'cart_item' => $cartItem->load('product'),
        ]);
    }

    /**
     * Get cart
     * GET /api/guest/cart/{sessionToken}
     */
    public function getCart($sessionToken)
    {
        $session = $this->sessionService->getSession($sessionToken);

        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 404);
        }

        $cart = $this->cartService->getCart($session);

        return response()->json($cart);
    }

    /**
     * Update cart item
     * PATCH /api/guest/cart/{cartItemId}
     */
    public function updateCartItem(Request $request, $cartItemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $success = $this->cartService->updateQuantity($cartItemId, $validated['quantity']);

        if (!$success) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove cart item
     * DELETE /api/guest/cart/{cartItemId}
     */
    public function removeCartItem($cartItemId)
    {
        $success = $this->cartService->removeItem($cartItemId);

        if (!$success) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        return response()->json(['success' => true]);
    }
}
