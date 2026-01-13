<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use BahtText\BahtText;

class KhqrService
{
    /**
     * Generate KHQR code for order
     */
    public function generateKhqr(Order $order): array
    {
        $shop = $order->shop;

        // Build KHQR string following Bakong specification
        $khqrString = $this->buildKhqrString(
            $shop->bakong_wallet_id,
            $order->total_amount,
            $order->order_number
        );

        // Generate MD5 hash (lowercase for Bakong API)
        $md5Hash = strtolower(md5($khqrString));

        // Store transaction
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'khqr_string' => $khqrString,
            'md5_hash' => $md5Hash,
        ]);

        return [
            'khqr_string' => $khqrString,
            'md5_hash' => $md5Hash,
            'transaction_id' => $transaction->id,
        ];
    }

    /**
     * Build KHQR string according to Bakong specification
     */
    protected function buildKhqrString(string $bakongAccountId, string|float $amount, string $merchantInvoice): string
    {
        // This is a simplified version - you'll need to implement full KHQR spec
        // Tag 29 with Bakong Account ID in Subtag 00

        $payload = '';

        // Payload Format Indicator (Tag 00)
        $payload .= $this->tag('00', '01');

        // Point of Initiation Method (Tag 01) - Dynamic
        $payload .= $this->tag('01', '12');

        // Merchant Account Information (Tag 29 - Bakong)
        $bakongData = $this->tag('00', $bakongAccountId); // Subtag 00: Bakong Account ID
        $payload .= $this->tag('29', $bakongData);

        // Transaction Currency (Tag 53) - USD: 840, KHR: 116
        $payload .= $this->tag('53', '840'); // USD

        // Transaction Amount (Tag 54)
        $payload .= $this->tag('54', number_format($amount, 2, '.', ''));

        // Country Code (Tag 58)
        $payload .= $this->tag('58', 'KH');

        // Merchant Name (Tag 59) - Optional
        // $payload .= $this->tag('59', substr($merchantName, 0, 25));

        // Additional Data Field (Tag 62)
        $additionalData = $this->tag('01', substr($merchantInvoice, 0, 25)); // Bill Number
        $payload .= $this->tag('62', $additionalData);

        // CRC (Tag 63) - Placeholder, will be calculated
        $payload .= '6304';
        $crc = $this->calculateCRC($payload);
        $payload .= $crc;

        return $payload;
    }

    /**
     * Build EMV tag
     */
    protected function tag(string $id, string $value): string
    {
        $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $length . $value;
    }

    /**
     * Calculate CRC-16 CCITT for KHQR
     */
    protected function calculateCRC(string $data): string
    {
        $polynomial = 0x1021;
        $crc = 0xFFFF;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);

            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) != 0) {
                    $crc = (($crc << 1) ^ $polynomial);
                } else {
                    $crc <<= 1;
                }
            }
        }

        $crc &= 0xFFFF;
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
