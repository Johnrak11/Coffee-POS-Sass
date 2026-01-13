<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StaffAuthService;
use Illuminate\Http\Request;

use App\Http\Requests\Staff\AuthRequest;

class StaffController extends Controller
{
    protected $staffAuthService;

    public function __construct(StaffAuthService $staffAuthService)
    {
        $this->staffAuthService = $staffAuthService;
    }

    /**
     * Get list of staff users for a shop (publicly safe info only)
     * GET /api/staff/users/{shopSlug}
     */
    public function getStaffList($shopSlug)
    {
        $result = $this->staffAuthService->getStaffList($shopSlug);

        if (!$result) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        return response()->json($result);
    }

    /**
     * Verify PIN for specific user
     * POST /api/staff/auth
     */
    public function authenticate(AuthRequest $request)
    {
        $validated = $request->validated();

        $result = $this->staffAuthService->authenticate($validated['user_id'], $validated['pin_code']);

        if (!$result) {
            return response()->json(['error' => 'Invalid PIN'], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }
}
