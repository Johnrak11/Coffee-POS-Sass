<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionSet;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OptionSetController extends Controller
{
    public function index($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $sets = OptionSet::where('shop_id', $shop->id)
            ->with('elements')
            ->get();

        return response()->json($sets);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'elements' => 'required|array|min:1',
            'elements.*.label' => 'required|string|max:255',
            'elements.*.price_modifier' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($shop, $validated) {
            $set = OptionSet::create([
                'shop_id' => $shop->id,
                'name' => $validated['name'],
            ]);

            foreach ($validated['elements'] as $index => $element) {
                $set->elements()->create([
                    'label' => $element['label'],
                    'price_modifier' => $element['price_modifier'],
                    'position' => $index,
                ]);
            }

            return $set->load('elements');
        });
    }

    public function update(Request $request, $shopSlug, OptionSet $optionSet)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        if ($optionSet->shop_id !== $shop->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'elements' => 'required|array|min:1',
            'elements.*.label' => 'required|string|max:255',
            'elements.*.price_modifier' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($optionSet, $validated) {
            $optionSet->update(['name' => $validated['name']]);

            // Re-create elements simplistic approach
            $optionSet->elements()->delete();

            foreach ($validated['elements'] as $index => $element) {
                $optionSet->elements()->create([
                    'label' => $element['label'],
                    'price_modifier' => $element['price_modifier'],
                    'position' => $index,
                ]);
            }

            return $optionSet->load('elements');
        });
    }

    public function destroy($shopSlug, OptionSet $optionSet)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        if ($optionSet->shop_id !== $shop->id) {
            abort(403);
        }

        $optionSet->delete();
        return response()->noContent();
    }
}
