<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::where('slug', 'lucky-cafe')->first();

        if (!$shop) {
            $this->command->error('Shop not found. Run ShopSeeder first.');
            return;
        }

        // Create categories
        $coffeeCategory = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Coffee',
            'sort_order' => 1,
        ]);

        $teaCategory = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Tea',
            'sort_order' => 2,
        ]);

        $frappeCategory = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Frappe',
            'sort_order' => 3,
        ]);

        $pastryCategory = Category::create([
            'shop_id' => $shop->id,
            'name' => 'Pastry',
            'sort_order' => 4,
        ]);

        // Coffee products
        $products = [
            [
                'category' => $coffeeCategory,
                'name' => 'Espresso',
                'price' => 2.50,
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Double Shot', 'extra_price' => 1.00],
                ],
            ],
            [
                'category' => $coffeeCategory,
                'name' => 'Americano',
                'price' => 3.00,
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Large', 'extra_price' => 0.50],
                ],
            ],
            [
                'category' => $coffeeCategory,
                'name' => 'Cappuccino',
                'price' => 3.50,
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Large', 'extra_price' => 0.50],
                    ['name' => 'Extra', 'option_name' => 'Extra Foam', 'extra_price' => 0.25],
                ],
            ],
            [
                'category' => $coffeeCategory,
                'name' => 'Latte',
                'price' => 3.75,
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Large', 'extra_price' => 0.50],
                    ['name' => 'Sweetness', 'option_name' => '50%', 'extra_price' => 0.00],
                    ['name' => 'Sweetness', 'option_name' => '0% (No Sugar)', 'extra_price' => 0.00],
                ],
            ],
            [
                'category' => $coffeeCategory,
                'name' => 'Mocha',
                'price' => 4.00,
            ],

            // Tea
            [
                'category' => $teaCategory,
                'name' => 'Green Tea',
                'price' => 2.50,
                'variants' => [
                    ['name' => 'Temperature', 'option_name' => 'Iced', 'extra_price' => 0.00],
                ],
            ],
            [
                'category' => $teaCategory,
                'name' => 'Jasmine Tea',
                'price' => 2.75,
            ],
            [
                'category' => $teaCategory,
                'name' => 'Thai Tea',
                'price' => 3.25,
                'variants' => [
                    ['name' => 'Sweetness', 'option_name' => '50%', 'extra_price' => 0.00],
                    ['name' => 'Sweetness', 'option_name' => '0%', 'extra_price' => 0.00],
                ],
            ],

            // Frappe
            [
                'category' => $frappeCategory,
                'name' => 'Coffee Frappe',
                'price' => 4.50,
            ],
            [
                'category' => $frappeCategory,
                'name' => 'Chocolate Frappe',
                'price' => 4.50,
            ],
            [
                'category' => $frappeCategory,
                'name' => 'Caramel Frappe',
                'price' => 4.75,
            ],

            // Pastry
            [
                'category' => $pastryCategory,
                'name' => 'Croissant',
                'price' => 2.00,
            ],
            [
                'category' => $pastryCategory,
                'name' => 'Chocolate Muffin',
                'price' => 2.50,
            ],
            [
                'category' => $pastryCategory,
                'name' => 'Blueberry Muffin',
                'price' => 2.50,
            ],
            [
                'category' => $pastryCategory,
                'name' => 'Cheesecake',
                'price' => 3.50,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create([
                'shop_id' => $shop->id,
                'category_id' => $productData['category']->id,
                'name' => $productData['name'],
                'price' => $productData['price'],
                'is_available' => true,
            ]);

            // Create variants if specified
            if (isset($productData['variants'])) {
                foreach ($productData['variants'] as $variantData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $variantData['name'],
                        'option_name' => $variantData['option_name'],
                        'extra_price' => $variantData['extra_price'],
                    ]);
                }
            }
        }

        $this->command->info('Menu created with ' . count($products) . ' products');
    }
}
