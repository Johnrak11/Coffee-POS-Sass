<?php

namespace App\Services;

use App\Models\ShopTable;
use App\Models\TableSession;
use Illuminate\Support\Str;

class TableSessionService
{
    /**
     * Scan QR code and create or retrieve active session
     */
    /**
     * Scan QR code and create or retrieve active session
     * 
     * @param string $qrToken
     * @param string|null $existingSessionToken
     * @return TableSession|null
     */
    public function scanTable(string $qrToken, ?string $existingSessionToken = null): ?TableSession
    {
        // Find the table by QR token
        $shopTable = ShopTable::where('qr_token', $qrToken)->first();

        if (!$shopTable) {
            return null;
        }

        // 1. Try to resume SPECIFIC existing session if provided
        if ($existingSessionToken) {
            $existingSession = TableSession::where('shop_table_id', $shopTable->id)
                ->where('session_token', $existingSessionToken)
                ->whereIn('status', ['active', 'ordering'])
                ->first();

            if ($existingSession) {
                // Check expiry
                if ($existingSession->expires_at && $existingSession->expires_at->isPast()) {
                    $existingSession->update(['status' => 'closed']);
                } else {
                    // Valid existing session, resume it
                    return $existingSession->load('shopTable.shop');
                }
            }
        }

        // 2. Otherwise/Fallthrough: Create NEW session (isolation by default)
        // We do NOT look for "any" active session anymore.

        $session = TableSession::create([
            'shop_table_id' => $shopTable->id,
            'session_token' => Str::random(100),
            'status' => 'active',
            'expires_at' => now()->addMinutes((int) env('TABLE_SESSION_LIFETIME', 120)),
        ]);

        // Ensure table is marked occupied if it wasn't already
        if ($shopTable->status === 'available') {
            $shopTable->update(['status' => 'occupied']);
        }

        return $session->load('shopTable.shop');
    }

    /**
     * Get session by token
     */
    public function getSession(string $sessionToken): ?TableSession
    {
        $session = TableSession::with(['shopTable.shop', 'cartItems.product'])
            ->where('session_token', $sessionToken)
            ->first();

        if (!$session) {
            return null;
        }

        // Check if expired
        if ($session->expires_at && $session->expires_at->isPast()) {
            // Session expired
            return null;
        }

        // Renew session if active
        if ($session->status === 'active' || $session->status === 'ordering') {
            $session->update(['expires_at' => now()->addMinutes((int) env('TABLE_SESSION_LIFETIME', 120))]);
        }

        return $session;
    }

    /**
     * Complete session (after order placement)
     */
    public function completeSession(TableSession $session): void
    {
        $session->update(['status' => 'completed']);

        // Only mark table as available if NO other active sessions exist
        $activeCount = TableSession::where('shop_table_id', $session->shop_table_id)
            ->whereIn('status', ['active', 'ordering'])
            ->count();

        if ($activeCount === 0) {
            $session->shopTable->update(['status' => 'available']);
        }
    }

    /**
     * Mark table as occupied
     */
    public function markTableOccupied(TableSession $session): void
    {
        $session->shopTable->update(['status' => 'occupied']);
    }
}
