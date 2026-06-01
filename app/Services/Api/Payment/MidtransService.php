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
