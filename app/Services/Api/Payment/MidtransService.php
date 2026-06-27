<?php

namespace App\Services\Api\Payment;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    private string $serverKey;
    private string $snapUrl;
    private string $apiUrl;

    public function __construct()
    {
        $this->serverKey = (string) config('midtrans.server_key');
        $isProduction = (bool) config('midtrans.is_production');

        $this->snapUrl = $isProduction
            ? config('midtrans.snap.production_url')
            : config('midtrans.snap.sandbox_url');

        $this->apiUrl = $isProduction
            ? config('midtrans.api.production_url')
            : config('midtrans.api.sandbox_url');
    }

    /**
     * Create a Snap payment token and redirect URL.
     *
     * @deprecated Kept for backward compatibility only — new payment
     * creation uses the Core API charge*() methods below exclusively.
     * See docs/midtrans.md for why ("Do NOT use Snap Redirect").
     *
     * @return array{token: string, redirect_url: string}
     * @throws RuntimeException
     */
    public function createSnapToken(array $params): array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->post($this->snapUrl, $params);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Midtrans Snap gagal: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            if (empty($data['token'])) {
                throw new RuntimeException('Midtrans Snap tidak mengembalikan token.');
            }

            return $data;
        } catch (RequestException $e) {
            throw new RuntimeException('Koneksi ke Midtrans gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    // ── Core API (charge) ───────────────────────────────────────────────────
    //
    // One method per payment method this app supports (config('midtrans.
    // supported_methods')). Each builds the method-specific payload shape
    // Midtrans' Core API `/v2/charge` endpoint expects, then delegates to
    // the shared charge() request method below.

    /**
     * @return array Raw Midtrans charge response (status_code, transaction_id,
     *                qr_string/actions for QRIS, va_numbers for bank_transfer, ...)
     */
    public function chargeQris(string $orderId, int $grossAmount, array $itemDetails, array $customerDetails): array
    {
        return $this->charge([
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ]);
    }

    /**
     * Virtual Account via bank_transfer. $bank must be one of the keys in
     * config('midtrans.va_banks') — bca, bni, bri, permata.
     */
    public function chargeBankTransfer(string $orderId, int $grossAmount, string $bank, array $itemDetails, array $customerDetails): array
    {
        return $this->charge([
            'payment_type' => 'bank_transfer',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'bank_transfer' => ['bank' => $bank],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ]);
    }

    /**
     * Mandiri Bill Payment (echannel). bill_key/biller_code are returned BY
     * Midtrans in the response — not sent in the request.
     */
    public function chargeEchannel(string $orderId, int $grossAmount, array $itemDetails, array $customerDetails): array
    {
        return $this->charge([
            'payment_type' => 'echannel',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'echannel' => [
                'bill_info1' => 'Pembayaran',
                'bill_info2' => 'Lumina Library: ' . $orderId,
            ],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ]);
    }

    public function chargeGopay(string $orderId, int $grossAmount, array $itemDetails, array $customerDetails): array
    {
        return $this->charge([
            'payment_type' => 'gopay',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'gopay' => ['enable_callback' => true],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ]);
    }

    public function chargeShopeepay(string $orderId, int $grossAmount, array $itemDetails, array $customerDetails): array
    {
        return $this->charge([
            'payment_type' => 'shopeepay',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'shopeepay' => ['callback_url' => (string) config('midtrans.finish_redirect_url')],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ]);
    }

    /**
     * Cancels a still-pending transaction at Midtrans — used when the member
     * picks a different payment method (see BorrowingPaymentService) so the
     * old VA/QR can no longer be paid after a new one is issued.
     */
    public function cancelTransaction(string $orderId): array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->post("{$this->apiUrl}/{$orderId}/cancel");

            return $response->json() ?? [];
        } catch (RequestException $e) {
            // Cancellation failing isn't fatal — the old transaction will
            // simply expire on its own. Log via the caller, don't throw.
            return ['status_code' => '500', 'status_message' => $e->getMessage()];
        }
    }

    /**
     * Shared Core API charge request. $payload's exact shape depends on
     * `payment_type` — see the charge*() methods above.
     *
     * @throws RuntimeException
     */
    private function charge(array $payload): array
    {
        if ((bool) config('midtrans.sanitized', true)) {
            $payload = $this->sanitize($payload);
        }

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->acceptJson()
                ->post("{$this->apiUrl}/charge", $payload);

            $data = $response->json() ?? [];

            // Midtrans returns 200/201 for success and 200/201/4xx with a
            // status_code field for declines — always check status_code,
            // not just the HTTP status, per Midtrans' own documented
            // convention (status_code '200'/'201' = success).
            $statusCode = (string) ($data['status_code'] ?? $response->status());

            if (! in_array($statusCode, ['200', '201'], true)) {
                throw new RuntimeException(
                    'Midtrans charge gagal: ' . ($data['status_message'] ?? $response->body()),
                    (int) $statusCode
                );
            }

            return $data;
        } catch (RequestException $e) {
            throw new RuntimeException('Koneksi ke Midtrans gagal: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Strips characters Midtrans flags as potentially unsafe from free-text
     * fields (mirrors the "sanitization" option exposed by Midtrans' own
     * SDKs) — applied recursively to customer/item detail strings only.
     */
    private function sanitize(array $payload): array
    {
        $strip = static fn (string $value): string => preg_replace('/[<>"\']/', '', $value) ?? $value;

        $walk = function (array $data) use (&$walk, $strip): array {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = $strip($value);
                } elseif (is_array($value)) {
                    $data[$key] = $walk($value);
                }
            }

            return $data;
        };

        return $walk($payload);
    }

    /**
     * Verify Midtrans notification signature key.
     * Formula: SHA512(order_id + status_code + gross_amount + server_key)
     */
    public function verifySignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signatureKey
    ): bool {
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Fetch current transaction status from Midtrans API.
     */
    public function fetchTransactionStatus(string $orderId): array
    {
        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->get("{$this->apiUrl}/{$orderId}/status");

        return $response->json() ?? [];
    }

    public function clientKey(): string
    {
        return (string) config('midtrans.client_key');
    }
}
