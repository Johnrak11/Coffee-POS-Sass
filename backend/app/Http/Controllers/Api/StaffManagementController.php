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
        // Exclude super admins from the staff list shown to shop owners
        $staff = User::where('shop_id', $shop->id)
            ->where(function ($q) {
                $q->where('is_super_admin', false)
                    ->orWhereNull('is_super_admin');
            })
            ->get();

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
            'pin_code' => $validated['pin'], // Map 'pin' input to 'pin_code' column
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $shopSlug, $staffId)
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

        // Map pin to pin_code for update
        if (isset($validated['pin'])) {
            $validated['pin_code'] = $validated['pin'];
            unset($validated['pin']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy($shopSlug, $staffId)
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
