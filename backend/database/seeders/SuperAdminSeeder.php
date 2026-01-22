<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');
        $name = 'Super Admin';

        if (env('SUPER_ADMIN_EMAIL') === null || env('SUPER_ADMIN_PASSWORD') === null) {
            $this->command->warn('⚠️  SUPER_ADMIN_EMAIL or SUPER_ADMIN_PASSWORD not set. Using defaults: admin@example.com / password');
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
