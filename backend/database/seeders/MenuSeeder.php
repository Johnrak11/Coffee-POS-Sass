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

        // Cleanup existing menu for this shop to avoid duplicates if re-run
        Product::where('shop_id', $shop->id)->delete();
        Category::where('shop_id', $shop->id)->delete();

        // 1. Create Categories
        $categories = [
            'Coffee' => Category::create(['shop_id' => $shop->id, 'name' => 'Signature Coffee', 'sort_order' => 1]),
            'Tea'    => Category::create(['shop_id' => $shop->id, 'name' => 'Tea & Milk Tea', 'sort_order' => 2]),
            'Frappe' => Category::create(['shop_id' => $shop->id, 'name' => 'Ice Blended (Frappe)', 'sort_order' => 3]),
            'Soda'   => Category::create(['shop_id' => $shop->id, 'name' => 'Fizzy Soda', 'sort_order' => 4]),
            'Cake'   => Category::create(['shop_id' => $shop->id, 'name' => 'Premium Cakes', 'sort_order' => 5]),
            'Bakery' => Category::create(['shop_id' => $shop->id, 'name' => 'Fresh Bakery', 'sort_order' => 6]),
        ];

        // 2. Define Products
        $menuItems = [
            // --- COFFEE ---
            [
                'category' => 'Coffee',
                'name' => 'Hot Espresso',
                'price' => 1.50,
                'image_url' => 'https://images.unsplash.com/photo-1510591509098-f40962d43898?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Shots', 'option_name' => 'Single Shot', 'extra_price' => 0.00],
                    ['name' => 'Shots', 'option_name' => 'Double Shot', 'extra_price' => 0.50],
                ]
            ],
            [
                'category' => 'Coffee',
                'name' => 'Iced Americano',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Regular (16oz)', 'extra_price' => 0.00],
                    ['name' => 'Size', 'option_name' => 'Large (22oz)', 'extra_price' => 0.50],
                    ['name' => 'Sweetness', 'option_name' => '100% (Normal)', 'extra_price' => 0.00],
                    ['name' => 'Sweetness', 'option_name' => '50% (Less Sweet)', 'extra_price' => 0.00],
                    ['name' => 'Sweetness', 'option_name' => '0% (No Sugar)', 'extra_price' => 0.00],
                ]
            ],
            [
                'category' => 'Coffee',
                'name' => 'Iced Latte',
                'price' => 2.75,
                'image_url' => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Regular', 'extra_price' => 0.00],
                    ['name' => 'Size', 'option_name' => 'Large', 'extra_price' => 0.75],
                    ['name' => 'Milk', 'option_name' => 'Whole Milk', 'extra_price' => 0.00],
                    ['name' => 'Milk', 'option_name' => 'Oat Milk', 'extra_price' => 0.50],
                ]
            ],
            [
                'category' => 'Coffee',
                'name' => 'Cappuccino',
                'price' => 2.75,
                'image_url' => 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Type', 'option_name' => 'Hot', 'extra_price' => 0.00],
                    ['name' => 'Type', 'option_name' => 'Iced', 'extra_price' => 0.25],
                ]
            ],
            [
                'category' => 'Coffee',
                'name' => 'Caramel Macchiato',
                'price' => 3.25,
                'image_url' => 'https://images.unsplash.com/photo-1485808191679-5f8c7c860695?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Size', 'option_name' => 'Regular', 'extra_price' => 0.00],
                    ['name' => 'Size', 'option_name' => 'Large', 'extra_price' => 0.75],
                ]
            ],

            // --- TEA ---
            [
                'category' => 'Tea',
                'name' => 'Iced Green Tea Latte',
                'price' => 3.00,
                'image_url' => 'https://images.unsplash.com/photo-1515823662972-da6a2e4d3002?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Topping', 'option_name' => 'Red Bean', 'extra_price' => 0.50],
                ]
            ],
            [
                'category' => 'Tea',
                'name' => 'Thai Tea',
                'price' => 2.50,
                'image_url' => 'https://images.unsplash.com/photo-1595981267035-7b04ca84a82d?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Tea',
                'name' => 'Passion Fruit Tea',
                'price' => 2.25,
                'image_url' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Sweetness', 'option_name' => 'Normal', 'extra_price' => 0.00],
                    ['name' => 'Sweetness', 'option_name' => 'Less Sweet', 'extra_price' => 0.00],
                ]
            ],
            [
                'category' => 'Tea',
                'name' => 'Lemon Iced Tea',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=800&q=80',
            ],

            // --- FRAPPE ---
            [
                'category' => 'Frappe',
                'name' => 'Mocha Frappe',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?auto=format&fit=crop&w=800&q=80',
                'variants' => [
                    ['name' => 'Topping', 'option_name' => 'Whipped Cream', 'extra_price' => 0.00],
                    ['name' => 'Topping', 'option_name' => 'No Cream', 'extra_price' => 0.00],
                ]
            ],
            [
                'category' => 'Frappe',
                'name' => 'Strawberry Smoothie',
                'price' => 3.25,
                'image_url' => 'https://images.unsplash.com/photo-1623594830172-5b9678822db6?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Frappe',
                'name' => 'Mango Passion Smoothie',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1623065422902-30a2d299bbe4?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Frappe',
                'name' => 'Oreo Frappe',
                'price' => 3.75,
                'image_url' => 'https://images.unsplash.com/photo-1577805947697-89e18249d76e?auto=format&fit=crop&w=800&q=80',
            ],

            // --- SODA ---
            [
                'category' => 'Soda',
                'name' => 'Blue Hawaii Soda',
                'price' => 2.25,
                'image_url' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Soda',
                'name' => 'Strawberry Soda',
                'price' => 2.25,
                'image_url' => 'https://images.unsplash.com/photo-1625740822008-e45a8a9d169c?auto=format&fit=crop&w=800&q=80',
            ],

            // --- CAKE ---
            [
                'category' => 'Cake',
                'name' => 'New York Cheesecake',
                'price' => 3.50,
                'image_url' => 'https://images.unsplash.com/photo-1508737027454-e6454ef45afd?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Cake',
                'name' => 'Tiramisu',
                'price' => 4.00,
                'image_url' => 'https://images.unsplash.com/photo-1571115177098-24ec42ed204d?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Cake',
                'name' => 'Chocolate Fudge Cake',
                'price' => 3.25,
                'image_url' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Cake',
                'name' => 'Red Velvet Cake',
                'price' => 3.75,
                'image_url' => 'https://images.unsplash.com/photo-1616541823729-00fe0aacd32c?auto=format&fit=crop&w=800&q=80',
            ],

            // --- BAKERY ---
            [
                'category' => 'Bakery',
                'name' => 'Butter Croissant',
                'price' => 1.75,
                'image_url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Bakery',
                'name' => 'Pain Au Chocolat',
                'price' => 2.00,
                'image_url' => 'https://images.unsplash.com/photo-1530610476181-d83430b64dcd?auto=format&fit=crop&w=800&q=80',
            ],
            [
                'category' => 'Bakery',
                'name' => 'Almond Danish',
                'price' => 2.25,
                'image_url' => 'https://images.unsplash.com/photo-1509365465985-25d11c17e812?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        // 3. Insert Data
        foreach ($menuItems as $item) {
            $product = Product::create([
                'shop_id' => $shop->id,
                'category_id' => $categories[$item['category']]->id,
                'name' => $item['name'],
                'price' => $item['price'],
                'image_url' => $item['image_url'] ?? null,
                'is_available' => true,
            ]);

            // Add Variants
            if (isset($item['variants'])) {
                foreach ($item['variants'] as $variant) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $variant['name'],
                        'option_name' => $variant['option_name'],
                        'extra_price' => $variant['extra_price'],
                    ]);
                }
            }
        }

        $this->command->info('Full Menu Seeded: ' . count($menuItems) . ' products across ' . count($categories) . ' categories.');
    }
}
