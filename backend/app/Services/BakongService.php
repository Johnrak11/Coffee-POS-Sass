<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BakongService
{
    protected $baseUrl;
    protected $secretKey;

    public function __construct()
    {
        // Get base URL and remove trailing slash. Should be e.g. http://localhost:8000
        $this->baseUrl = rtrim(config('services.bakong.base_url', 'https://portfolio.johnrak.online'), '/');
        $this->secretKey = config('services.bakong.secret_key');
    }

    /**
     * Generate KHQR
     *
     * @param float $amount
     * @param string $currency 'USD' or 'KHR'
     * @param array $options Additional fields (merchant_name, etc.)
     */
    public function generateQr(float $amount, string $currency, array $options = [])
    {
        $payload = array_merge([
            'amount' => $amount,
            'currency' => $currency,
            'source_info' => [
                'appIconUrl' => 'https://coffee-pos-saas.com/logo.png', // Update with real logo
                'appName' => 'Coffee POS',
                'appDeepLinkCallback' => 'https://coffee-pos-saas.com/callback'
            ]
        ], $options);

        // Send raw payload to /api/external/generate-qr
        $response = Http::timeout(10)->withHeaders([
            'X-SnapOrder-Key' => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post("{$this->baseUrl}/api/external/generate-qr", $payload);

        if ($response->failed()) {
            Log::error('Bakong Generate QR Failed', ['body' => $response->body(), 'status' => $response->status()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Check Single Transaction Status
     *
     * @param string $md5
     * @param string|null $telegramChatId
     */
    public function checkTransactionStatus(string $md5, ?string $telegramChatId = null)
    {
        $payload = [
            'md5' => $md5,
            'telegram_chat_id' => $telegramChatId
        ];

        $response = Http::withHeaders([
            'X-SnapOrder-Key' => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post("{$this->baseUrl}/api/external/check-status", $payload);

        if ($response->failed()) {
            Log::error('Bakong Check Status Failed', ['body' => $response->body(), 'status' => $response->status()]);
            return null;
        }

        return $response->json();
    }

    /**
     * Check Transaction Status Batch
     *
     * @param array $md5List List of MD5 hashes
     */
    public function checkStatusBatch(array $md5List)
    {
        $payload = ['md5_list' => $md5List];

        $response = Http::withHeaders([
            'X-SnapOrder-Key' => $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post("{$this->baseUrl}/api/external/check-status-batch", $payload);

        if ($response->failed()) {
            Log::error('Bakong Check Status Failed', ['body' => $response->body(), 'status' => $response->status()]);
            return null;
        }

        return $response->json();
    }
}
