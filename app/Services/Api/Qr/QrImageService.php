<?php

namespace App\Services\Api\Qr;

use Illuminate\Support\Facades\File;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrImageService
{
    public function makeDataUri(array $payload): string
    {
        $rawPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $svg = QrCode::format('svg')
            ->size(480)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($rawPayload);

        $logoPath = public_path((string) config('lumina.qr.logo_path'));

        if (is_file($logoPath)) {
            $svg = $this->injectLogoToSvg($svg, $logoPath);
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function injectLogoToSvg(string $svg, string $logoPath): string
    {
        $logoBase64 = base64_encode((string) file_get_contents($logoPath));
        $mimeType = $this->resolveMimeType($logoPath);

        /*
         * QR size dibuat 480.
         * Logo dibuat sekitar 22% dari QR width agar tetap mudah discan.
         */
        $qrSize = 480;
        $logoSize = 106;
        $padding = 14;

        $boxSize = $logoSize + ($padding * 2);
        $boxX = (int) (($qrSize - $boxSize) / 2);
        $boxY = (int) (($qrSize - $boxSize) / 2);

        $logoX = $boxX + $padding;
        $logoY = $boxY + $padding;

        $logoSvg = <<<SVG
<rect x="{$boxX}" y="{$boxY}" width="{$boxSize}" height="{$boxSize}" rx="18" ry="18" fill="#ffffff"/>
<image x="{$logoX}" y="{$logoY}" width="{$logoSize}" height="{$logoSize}" href="data:{$mimeType};base64,{$logoBase64}" preserveAspectRatio="xMidYMid meet"/>
SVG;

        if (str_contains($svg, '</svg>')) {
            return str_replace('</svg>', $logoSvg . '</svg>', $svg);
        }

        return $svg;
    }

    private function resolveMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }
}