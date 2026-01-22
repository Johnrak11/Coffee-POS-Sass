<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class ShopManagementService
{
    public function getPlatformStats()
    {
        return [
            'total_shops' => Shop::count(),
            'total_revenue' => Order::sum('total_amount'),
            'active_shops' => Shop::where('subscription_status', 'active')->count(),
            'revenue_growth' => 12.5, // Placeholder
        ];
    }

    public function getAllShops()
    {
        return Shop::with([
            'users' => function ($q) {
                $q->where('role', 'owner');
            }
        ])->get()->map(function ($shop) {
            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'owner' => $shop->owner_name ?? ($shop->users->first()->name ?? 'Unknown'),
                'status' => $shop->subscription_status,
                'plan' => $shop->subscription_plan,
                'joined_at' => $shop->created_at->format('Y-m-d'),
                'revenue' => 0
            ];
        });
    }

    public function toggleShopStatus($shopId)
    {
        $shop = Shop::findOrFail($shopId);
        $newStatus = $shop->subscription_status === 'active' ? 'expired' : 'active';

        $shop->subscription_status = $newStatus;
        $shop->subscription_expires_at = $newStatus === 'expired'
            ? now()->subDay()
            : now()->addDays(30);

        $shop->save();

        return $newStatus;
    }

    public function createShop(array $data)
    {
        $shop = Shop::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'password' => Hash::make($data['password']),
            'subscription_plan' => $data['plan'],
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addDays(30),
            'owner_name' => 'Owner',
            'theme_mode' => $data['theme_mode'] ?? 'dark',

            // Fill optional fields
            'bakong_account_id' => $data['bakong_account_id'] ?? null,
            'merchant_name' => $data['merchant_name'] ?? null,
            'merchant_city' => $data['merchant_city'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'receipt_footer' => $data['receipt_footer'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'currency_symbol' => $data['currency_symbol'] ?? '$',
            'bakong_wallet_id' => $data['bakong_wallet_id'] ?? null,
        ]);

        $user = User::create([
            'name' => 'Owner',
            'email' => $data['owner_email'],
            'password' => Hash::make($data['owner_password']),
            'role' => 'owner',
            'shop_id' => $shop->id,
            'pin_code' => '123456',
            'is_super_admin' => false
        ]);

        $shop->owner_name = $user->name;
        $shop->save();

        return $shop;
    }

    public function updateShop($shopId, array $data)
    {
        $shop = Shop::findOrFail($shopId);

        $shop->update([
            'name' => $data['name'],
            'subscription_plan' => $data['plan'] ?? $shop->subscription_plan,
            'bakong_account_id' => $data['bakong_account_id'] ?? $shop->bakong_account_id,
            'merchant_name' => $data['merchant_name'] ?? $shop->merchant_name,
            'merchant_city' => $data['merchant_city'] ?? $shop->merchant_city,
            'phone' => $data['phone'] ?? $shop->phone,
            'address' => $data['address'] ?? $shop->address,
            'logo_url' => $data['logo_url'] ?? $shop->logo_url,
            'receipt_footer' => $data['receipt_footer'] ?? $shop->receipt_footer,
            'primary_color' => $data['primary_color'] ?? $shop->primary_color,
            'currency_symbol' => $data['currency_symbol'] ?? $shop->currency_symbol,
            'bakong_wallet_id' => $data['bakong_wallet_id'] ?? $shop->bakong_wallet_id,
            'theme_mode' => $data['theme_mode'] ?? $shop->theme_mode,
        ]);

        // Manage Owner Update if requested
        if (!empty($data['owner_email']) || !empty($data['owner_password'])) {
            $owner = $shop->users()->where('role', 'owner')->first();
            if ($owner) {
                if (!empty($data['owner_email'])) {
                    // Check uniqueness excluding current user if needed, but for now simple update
                    $owner->email = $data['owner_email'];
                }
                if (!empty($data['owner_password'])) {
                    $owner->password = Hash::make($data['owner_password']);
                }
                $owner->save();
            }
        }

        return $shop;
    }

    public function resetTerminalPassword($shopId, $password)
    {
        $shop = Shop::findOrFail($shopId);
        $shop->password = Hash::make($password);
        $shop->save();
    }
}
