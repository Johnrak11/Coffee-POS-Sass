<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffManagementController extends Controller
{
    public function index($shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();
        $staff = User::where('shop_id', $shop->id)->get();

        return response()->json($staff);
    }

    public function store(Request $request, $shopSlug)
    {
        $shop = Shop::where('slug', $shopSlug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:owner,cashier,barista',
            'pin' => 'required|string|size:6', // 6-digit PIN
        ]);

        // Prevent creating new owners
        if ($validated['role'] === 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create new owners. Only system administrators can assign owner roles.'
            ], 403);
        }

        $user = User::create([
            'shop_id' => $shop->id,
            'name' => $validated['name'],
            'role' => $validated['role'],
            'pin' => $validated['pin'], // Note: In a real app we might hash this, but we use it for direct PIN matching
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $staffId)
    {
        $user = User::findOrFail($staffId);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'role' => 'sometimes|required|in:owner,cashier,barista',
            'pin' => 'sometimes|required|string|size:6',
        ]);

        // Prevent promoting to owner
        if (isset($validated['role']) && $validated['role'] === 'owner' && $user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot promote staff to owner role.'
            ], 403);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy($staffId)
    {
        $user = User::findOrFail($staffId);

        // Prevent deleting the last owner if needed
        if ($user->role === 'owner') {
            $ownerCount = User::where('shop_id', $user->shop_id)->where('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return response()->json(['error' => 'Cannot delete the last owner'], 422);
            }
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}
