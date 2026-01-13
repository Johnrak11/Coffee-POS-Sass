<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;
use App\Models\ShopTable;
use Illuminate\Support\Str;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo coffee shop
        $shop = Shop::create([
            'name' => 'Lucky Cafe',
            'slug' => 'lucky-cafe',
            'owner_name' => 'John Doe',
            'bakong_wallet_id' => 'lucky_cafe@bakong',
            'subscription_status' => 'active',
        ]);

        // Create staff users
        User::create([
            'shop_id' => $shop->id,
            'name' => 'John Doe',
            'pin_code' => '123456', // Will be hashed automatically
            'role' => 'owner',
        ]);

        User::create([
            'shop_id' => $shop->id,
            'name' => 'Jane Smith',
            'pin_code' => '111111',
            'role' => 'cashier',
        ]);

        User::create([
            'shop_id' => $shop->id,
            'name' => 'Bob Johnson',
            'pin_code' => '222222',
            'role' => 'barista',
        ]);

        // Create tables with QR tokens
        $tables = [
            ['number' => 'T-01', 'token' => Str::random(64)],
            ['number' => 'T-02', 'token' => Str::random(64)],
            ['number' => 'T-03', 'token' => Str::random(64)],
            ['number' => 'T-04', 'token' => Str::random(64)],
            ['number' => 'T-05', 'token' => Str::random(64)],
            ['number' => 'VIP-1', 'token' => Str::random(64)],
            ['number' => 'VIP-2', 'token' => Str::random(64)],
        ];

        foreach ($tables as $table) {
            ShopTable::create([
                'shop_id' => $shop->id,
                'table_number' => $table['number'],
                'qr_token' => $table['token'],
                'status' => 'available',
            ]);
        }

        $this->command->info('Shop, users, and tables created for Lucky Cafe');
        $this->command->info('QR Token for Table T-01: ' . $tables[0]['token']);
    }
}
