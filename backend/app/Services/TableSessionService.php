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

        // Check for active or ordering session
        $activeSession = TableSession::where('shop_table_id', $shopTable->id)
            ->whereIn('status', ['active', 'ordering'])
            ->first();

        if ($activeSession) {
            // Check if expired
            if ($activeSession->expires_at && $activeSession->expires_at->isPast()) {
                // Mark as closed/expired explicitly if needed, or just create new one
                // Ideally, we should update status to avoid multiple "active" sessions logic confusion,
                // though the query above filters by active/ordering.

                // If it was "ordering", we might want to be careful, but if it's expired, it's expired.
                $activeSession->update(['status' => 'closed']);
            } else {
                // Valid session, return it
                return $activeSession->load('shopTable.shop');
            }
        }

        // Create new session
        $session = TableSession::create([
            'shop_table_id' => $shopTable->id,
            'session_token' => Str::random(100),
            'status' => 'active',
            'expires_at' => now()->addMinutes((int) env('TABLE_SESSION_LIFETIME', 120)),
        ]);

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
