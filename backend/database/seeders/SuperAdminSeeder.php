<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('JOHNRAK_ADMIN_EMAIL');
        $password = env('JOHNRAK_ADMIN_PASSWORD');
        $name = env('JOHNRAK_ADMIN_NAME', 'Super Admin');

        if (!$email || !$password) {
            $this->command->error('JOHNRAK_ADMIN_EMAIL or JOHNRAK_ADMIN_PASSWORD is not set in .env');
            return;
        }

        // Ensure a shop exists to attach the user to (Required by DB constraint)
        $shop = \App\Models\Shop::first();
        if (!$shop) {
            $shop = \App\Models\Shop::create([
                'name' => 'System Shop',
                'slug' => 'system-admin',
                'owner_name' => 'System',
                'subscription_status' => 'active',
                'theme_mode' => 'dark'
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $email], // Look up by email
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'role' => 'owner', // Default role
                'pin_code' => '000000', // Dummy PIN required by DB
                'is_super_admin' => true,
                'shop_id' => $shop->id // Assign to the found/created shop
            ]
        );

        // Ensure shop_id is handled if strictly required by system, but for fresh admin it might remain null until they pick one
        // or we assign a system shop.
        // For now, let's leave shop_id as is (or null if new).

        $this->command->info("Super Admin seeded: {$user->name} ({$user->email})");
    }
}
