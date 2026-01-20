<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffAuthService
{
    public function getStaffList(string $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->first();

        if (!$shop) {
            return null;
        }

        $users = $shop->users()
            ->whereIn('role', ['owner', 'cashier', 'barista'])
            ->where('is_super_admin', false)
            ->select('id', 'name', 'role')
            ->get();

        return [
            'shop' => $shop,
            'users' => $users
        ];
    }

    public function authenticate(int $userId, string $pinCode)
    {
        $user = User::find($userId);

        if (!$user || !Hash::check($pinCode, $user->pin_code)) {
            return null;
        }

        // Generate Token
        // Use real Sanctum token for auth:sanctum middleware
        $token = $user->createToken('staff-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}
