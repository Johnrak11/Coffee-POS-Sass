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
            'theme_mode' => 'dark'
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
            'bakong_account_id' => $data['bakong_account_id'] ?? $shop->bakong_account_id,
            'merchant_name' => $data['merchant_name'] ?? $shop->merchant_name,
            'subscription_plan' => $data['plan'] ?? $shop->subscription_plan
        ]);

        return $shop;
    }

    public function resetTerminalPassword($shopId, $password)
    {
        $shop = Shop::findOrFail($shopId);
        $shop->password = Hash::make($password);
        $shop->save();
    }
}
