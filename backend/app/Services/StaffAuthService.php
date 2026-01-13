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
        // Ideally use: $token = $user->createToken('staff-token')->plainTextToken;
        // For now maintaining the mock token but wrapped in service
        $token = base64_encode($user->id . ':' . Str::random(32));

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}
