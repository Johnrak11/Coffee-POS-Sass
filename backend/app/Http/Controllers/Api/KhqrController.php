<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BakongService;
use Illuminate\Http\Request;

class KhqrController extends Controller
{
    protected $bakongService;

    public function __construct(BakongService $bakongService)
    {
        $this->bakongService = $bakongService;
    }

    /**
     * Generate KHQR for a transaction
     */
    public function generate(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,KHR'
        ]);

        $result = $this->bakongService->generateQr(
            $request->amount,
            $request->currency
        );

        if (!$result) {
            return response()->json(['message' => 'Failed to generate QR'], 500);
        }

        return response()->json($result);
    }

    /**
     * Check status of multiple transactions (MD5 hashes)
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'md5_list' => 'required|array',
            'md5_list.*' => 'string'
        ]);

        $result = $this->bakongService->checkStatusBatch($request->md5_list);

        if (!$result || !isset($result['data']) || !is_array($result['data'])) {
            return response()->json(['message' => 'Failed to check status'], 500);
        }

        // Process successful transactions
        foreach ($result['data'] as $tx) {
            if (isset($tx['status']) && $tx['status'] === 'SUCCESS' && isset($tx['md5'])) {
                $order = \App\Models\Order::where('khqr_md5', $tx['md5'])
                    ->where('payment_status', 'pending')
                    ->first();

                if ($order) {
                    $order->update([
                        'payment_status' => 'paid',
                        'payment_metadata' => $tx['data'] ?? null
                    ]);
                }
            }
        }

        return response()->json($result);
    }

    /**
     * Check SINGLE transaction status
     */
    public function checkStatusSingle(Request $request)
    {
        $request->validate([
            'md5' => 'required|string',
        ]);

        $md5 = $request->md5;

        // Find the order to get the shop's Telegram ID
        $order = \App\Models\Order::with('shop')
            ->where('khqr_md5', $md5)
            ->first();

        $telegramChatId = $order ? $order->shop->bakong_telegram_chat_id : null;

        $result = $this->bakongService->checkTransactionStatus($md5, $telegramChatId);

        if (!$result) {
            return response()->json(['message' => 'Failed to check status'], 500);
        }

        // Process successful transaction
        // External Single Check returns: { responseCode: 0, responseMessage: "Success", data: { ... } }
        if (isset($result['responseCode']) && $result['responseCode'] === 0) {
            if ($order && $order->payment_status === 'pending') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_metadata' => $result['data'] ?? null
                ]);
            }
        }

        return response()->json(['data' => [$result]]);
    }

    /**
     * Regenerate QR for existing order
     */
    public function regenerate(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = \App\Models\Order::with('shop')->findOrFail($request->order_id);

        // Ensure order is pending
        if ($order->payment_status !== 'pending') {
            return response()->json(['message' => 'Order is not pending'], 400);
        }

        // Generate QR
        $result = $this->bakongService->generateQr(
            (float) $order->total_amount,
            $order->payment_currency ?? 'USD', // Default to USD if null (legacy)
            [
                'merchant_name' => $order->shop->name,
                'merchant_city' => $order->shop->city ?? 'Phnom Penh',
                'telegram_chat_id' => $order->shop->bakong_telegram_chat_id
            ]
        );

        if (!$result || !isset($result['data'])) {
            return response()->json(['message' => 'Failed to regenerate QR'], 500);
        }

        // Update Order with new MD5/String
        $order->update([
            'khqr_string' => $result['data']['qr_string'],
            'khqr_md5' => $result['data']['md5'],
        ]);

        return response()->json($result);
    }
}
