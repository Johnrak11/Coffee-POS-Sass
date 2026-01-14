<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;

use App\Services\CloudinaryService;

class CategoryManagementController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function index(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $query = Category::where('shop_id', $shop->id)
            ->orderBy('sort_order', 'asc');

        // Search
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Pagination
        $perPage = $request->query('limit', 10);
        $categories = $query->paginate($perPage);

        return response()->json($categories);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:500',
            'sort_order' => 'integer'
        ]);

        $category = Category::create([
            'shop_id' => $shop->id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'Coffee',
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, $shopSlug, $categoryId)
    {
        $category = Category::findOrFail($categoryId);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:500',
            'sort_order' => 'integer'
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy($shopSlug, $categoryId)
    {
        $category = Category::findOrFail($categoryId);

        // Delete image from Cloudinary if exists
        if ($category->icon && str_contains($category->icon, 'cloudinary')) {
            try {
                if (preg_match('/\/v\d+\/(.+)\.\w+$/', $category->icon, $matches)) {
                    $this->cloudinary->deleteImage($matches[1]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to delete Category image: " . $e->getMessage());
            }
        }

        $category->delete();

        return response()->json(['success' => true]);
    }
}
