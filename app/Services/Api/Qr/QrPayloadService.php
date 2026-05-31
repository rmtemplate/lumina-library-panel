<?php

namespace App\Services\Api\Qr;

use App\Enums\QrCodeType;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrPayloadService
{
    public function makePayload(QrCodeType $type, array $data): array
    {
        $payload = [
            'iss' => config('lumina.qr.issuer'),
            'aud' => config('lumina.qr.audience'),
            'ver' => config('lumina.qr.version', '1.0'),

            'type' => $type->value,
            'source' => 'lumina_library',
            'brand' => 'Lumina Library',
            'owner' => 'RM Template',

            'jti' => $data['qr_code'],
            'borrowing_id' => $data['borrowing_id'] ?? null,
            'borrowing_code' => $data['borrowing_code'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,

            'iat' => now()->timestamp,
            'exp' => $data['expires_at']->timestamp,
            'nonce' => (string) Str::uuid(),
        ];

        $payload = array_filter($payload, fn ($value) => $value !== null);

        $payload['sig'] = $this->sign($payload);

        return $payload;
    }

    public function verify(array $payload, ?QrCodeType $expectedType = null): array
    {
        if (($payload['iss'] ?? null) !== config('lumina.qr.issuer')) {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak valid. Issuer tidak dikenali.'],
            ]);
        }

        if (($payload['aud'] ?? null) !== config('lumina.qr.audience')) {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak valid. Audience tidak sesuai.'],
            ]);
        }

        if (($payload['source'] ?? null) !== 'lumina_library') {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak valid. Source tidak dikenali.'],
            ]);
        }

        if (($payload['owner'] ?? null) !== 'RM Template') {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak valid. Owner tidak sesuai.'],
            ]);
        }

        if ($expectedType && ($payload['type'] ?? null) !== $expectedType->value) {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak sesuai dengan proses yang sedang dilakukan.'],
            ]);
        }

        if (empty($payload['jti']) || empty($payload['sig'])) {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak valid. Token atau signature tidak ditemukan.'],
            ]);
        }

        $expectedSignature = $this->sign(Arr::except($payload, ['sig']));

        if (! hash_equals($expectedSignature, (string) $payload['sig'])) {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak valid. Signature tidak cocok.'],
            ]);
        }

        if (! empty($payload['exp']) && now()->timestamp > (int) $payload['exp']) {
            throw ValidationException::withMessages([
                'qr' => ['QR sudah kedaluwarsa. Silakan refresh QR.'],
            ]);
        }

        return $payload;
    }

    public function decode(string $rawQr): array
    {
        $payload = json_decode($rawQr, true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'qr' => ['Format QR tidak valid.'],
            ]);
        }

        return $payload;
    }

    private function sign(array $payload): string
    {
        return hash_hmac(
            'sha256',
            $this->canonicalJson($payload),
            (string) config('lumina.qr.signing_secret')
        );
    }

    private function canonicalJson(array $payload): string
    {
        return json_encode(
            $this->sortRecursive($payload),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    private function sortRecursive(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sortRecursive($value);
            }
        }

        return $payload;
    }
}