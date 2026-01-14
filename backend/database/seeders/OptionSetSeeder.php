<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OptionSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $shops = \App\Models\Shop::all();

        foreach ($shops as $shop) {
            // 1. Size Presets
            $sizeSet = \App\Models\OptionSet::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Standard Sizes']
            );
            if ($sizeSet->elements()->count() === 0) {
                $sizeSet->elements()->createMany([
                    ['label' => 'Regular', 'price_modifier' => 0, 'position' => 0],
                    ['label' => 'Large', 'price_modifier' => 0.50, 'position' => 1],
                    ['label' => 'Extra Large', 'price_modifier' => 1.00, 'position' => 2],
                ]);
            }

            // 2. Sugar Presets
            $sugarSet = \App\Models\OptionSet::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Sugar Levels']
            );
            if ($sugarSet->elements()->count() === 0) {
                $sugarSet->elements()->createMany([
                    ['label' => '100% (Normal)', 'price_modifier' => 0, 'position' => 0],
                    ['label' => '50% (Less)', 'price_modifier' => 0, 'position' => 1],
                    ['label' => '0% (No Sugar)', 'price_modifier' => 0, 'position' => 2],
                ]);
            }

            // 3. Ice Presets
            $iceSet = \App\Models\OptionSet::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Ice Levels']
            );
            if ($iceSet->elements()->count() === 0) {
                $iceSet->elements()->createMany([
                    ['label' => 'Normal Ice', 'price_modifier' => 0, 'position' => 0],
                    ['label' => 'Less Ice', 'price_modifier' => 0, 'position' => 1],
                    ['label' => 'No Ice', 'price_modifier' => 0, 'position' => 2],
                ]);
            }

            // 4. Toppings Presets
            $toppingSet = \App\Models\OptionSet::firstOrCreate(
                ['shop_id' => $shop->id, 'name' => 'Common Toppings']
            );
            if ($toppingSet->elements()->count() === 0) {
                $toppingSet->elements()->createMany([
                    ['label' => 'Pearl', 'price_modifier' => 0.50, 'position' => 0],
                    ['label' => 'Coconut Jelly', 'price_modifier' => 0.50, 'position' => 1],
                    ['label' => 'Brown Sugar', 'price_modifier' => 0.50, 'position' => 2],
                ]);
            }
        }
    }
}
