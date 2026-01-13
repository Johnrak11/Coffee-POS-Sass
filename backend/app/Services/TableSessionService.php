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
    public function scanTable(string $qrToken): ?TableSession
    {
        // Find the table by QR token
        $shopTable = ShopTable::where('qr_token', $qrToken)->first();

        if (!$shopTable) {
            return null;
        }

        // Check for active session
        $activeSession = TableSession::where('shop_table_id', $shopTable->id)
            ->where('status', 'active')
            ->first();

        if ($activeSession) {
            return $activeSession->load('shopTable.shop');
        }

        // Create new session
        $session = TableSession::create([
            'shop_table_id' => $shopTable->id,
            'session_token' => Str::random(100),
            'status' => 'active',
        ]);

        return $session->load('shopTable.shop');
    }

    /**
     * Get session by token
     */
    public function getSession(string $sessionToken): ?TableSession
    {
        return TableSession::with(['shopTable.shop', 'cartItems.product'])
            ->where('session_token', $sessionToken)
            ->first();
    }

    /**
     * Complete session (after order placement)
     */
    public function completeSession(TableSession $session): void
    {
        $session->update(['status' => 'completed']);

        // Mark table as available
        $session->shopTable->update(['status' => 'available']);
    }

    /**
     * Mark table as occupied
     */
    public function markTableOccupied(TableSession $session): void
    {
        $session->shopTable->update(['status' => 'occupied']);
    }
}
