<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShopManagementService;
use App\Http\Requests\SuperAdmin\StoreShopRequest;
use App\Http\Requests\SuperAdmin\UpdateShopRequest;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    protected $shopService;

    public function __construct(ShopManagementService $shopService)
    {
        $this->shopService = $shopService;
    }

    public function getStats()
    {
        return response()->json($this->shopService->getPlatformStats());
    }

    public function getShops()
    {
        return response()->json($this->shopService->getAllShops());
    }

    public function toggleShopStatus($shopId)
    {
        $newStatus = $this->shopService->toggleShopStatus($shopId);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => "Shop is now {$newStatus}"
        ]);
    }

    public function store(StoreShopRequest $request)
    {

        $this->shopService->createShop($request->all());

        return response()->json(['success' => true, 'message' => 'Shop created successfully']);
    }

    public function update(UpdateShopRequest $request, $shopId)
    {

        $this->shopService->updateShop($shopId, $request->all());

        return response()->json(['success' => true, 'message' => 'Shop updated']);
    }

    public function resetPassword(Request $request, $shopId)
    {
        $request->validate(['password' => 'required|min:4']);

        $this->shopService->resetTerminalPassword($shopId, $request->password);

        return response()->json(['success' => true, 'message' => 'Terminal password updated']);
    }
}
