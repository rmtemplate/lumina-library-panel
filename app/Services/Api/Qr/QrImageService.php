<?php

namespace App\Services\Api\Qr;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\ByteMatrix;
use BaconQrCode\Encoder\Encoder;
use GdImage;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Renders QR codes as PNG.
 *
 * simplesoftwareio/simple-qrcode (and the bacon/bacon-qr-code renderer it
 * wraps) can only rasterize to PNG via BaconQrCode\Renderer\Image\ImagickImageBackEnd,
 * which hard-requires the `imagick` PHP extension. That extension is not
 * installed in this environment (only `gd` is), so `QrCode::format('png')`
 * would throw at runtime — which is why this previously fell back to
 * `format('svg')`. SVG is not a raster format and cannot be decoded by
 * Flutter's Image.memory().
 *
 * Instead of rasterizing through Imagick, we pull the raw QR bit-matrix
 * straight out of bacon/bacon-qr-code's encoder (BaconQrCode\Encoder\Encoder)
 * and draw it ourselves with GD, which is already available. This avoids
 * any new Composer dependency or PHP extension.
 */
class QrImageService
{
    /** Target image size in pixels (matches the previous SVG output size). */
    private const SIZE = 480;

    /** Quiet-zone margin, expressed in QR modules. */
    private const MARGIN_MODULES = 2;

    public function makeDataUri(array $payload): string
    {
        $rawPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        Log::debug('[QR][BACKEND] Generating QR PNG', [
            'payload_length' => strlen($rawPayload),
        ]);

        $png = $this->renderPng($rawPayload);

        $logoPath = public_path((string) config('lumina.qr.logo_path'));
        if (is_file($logoPath)) {
            $png = $this->injectLogoToPng($png, $logoPath);
        }

        $base64 = base64_encode($png);

        Log::debug('[QR][BACKEND] PNG generation result', [
            'format' => 'png',
            'base64_length' => strlen($base64),
            'base64_preview' => substr($base64, 0, 50),
            'png_success' => true,
        ]);

        return 'data:image/png;base64,' . $base64;
    }

    /**
     * Encodes $content into a QR bit-matrix via bacon/bacon-qr-code, then
     * rasterizes that matrix to PNG using GD (no Imagick required).
     */
    private function renderPng(string $content): string
    {
        $qrCode = Encoder::encode($content, ErrorCorrectionLevel::H());
        $matrix = $qrCode->getMatrix();
        $matrixSize = $matrix->getWidth();

        $totalModules = $matrixSize + (self::MARGIN_MODULES * 2);
        $moduleSize = max(1, (int) round(self::SIZE / $totalModules));
        $imageSize = $moduleSize * $totalModules;

        $image = imagecreatetruecolor($imageSize, $imageSize);
        if (! $image instanceof GdImage) {
            Log::error('[QR][BACKEND] imagecreatetruecolor() failed');
            throw new RuntimeException('Failed to allocate QR canvas.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $imageSize, $imageSize, $white);

        $this->paintMatrix($image, $matrix, $matrixSize, $moduleSize, self::MARGIN_MODULES, $black);

        $blob = $this->encodePng($image);
        imagedestroy($image);

        if ($blob === '') {
            Log::error('[QR][BACKEND] imagepng() produced empty output');
            throw new RuntimeException('Failed to render QR code as PNG.');
        }

        Log::debug('[QR][BACKEND] PNG raster generated', [
            'matrix_size' => $matrixSize,
            'image_size' => $imageSize,
            'png_bytes' => strlen($blob),
        ]);

        return $blob;
    }

    private function paintMatrix(
        GdImage $image,
        ByteMatrix $matrix,
        int $matrixSize,
        int $moduleSize,
        int $marginModules,
        int $color
    ): void {
        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $px = ($x + $marginModules) * $moduleSize;
                $py = ($y + $marginModules) * $moduleSize;

                imagefilledrectangle(
                    $image,
                    $px,
                    $py,
                    $px + $moduleSize - 1,
                    $py + $moduleSize - 1,
                    $color
                );
            }
        }
    }

    /**
     * Overlays the configured logo in a rounded white box at the center of
     * the QR. Any failure to read/decode the logo is logged and skipped —
     * the QR itself must still be returned and remain scannable.
     */
    private function injectLogoToPng(string $pngBlob, string $logoPath): string
    {
        $base = imagecreatefromstring($pngBlob);
        if (! $base instanceof GdImage) {
            Log::warning('[QR][BACKEND] Could not re-open generated PNG for logo overlay; returning QR without logo');
            return $pngBlob;
        }

        $logoBlob = file_get_contents($logoPath);
        $logo = $logoBlob !== false ? imagecreatefromstring($logoBlob) : false;
        if (! $logo instanceof GdImage) {
            Log::warning('[QR][BACKEND] Could not decode logo file for QR overlay; returning QR without logo', [
                'path' => $logoPath,
            ]);
            imagedestroy($base);
            return $pngBlob;
        }

        /*
         * QR size is 480. Logo box is sized to ~22% of the QR width so the
         * code remains scannable, matching the previous SVG overlay sizing.
         */
        $qrSize = imagesx($base);
        $logoSize = (int) round($qrSize * 0.22);
        $padding = (int) round($logoSize * 0.13);
        $boxSize = $logoSize + ($padding * 2);
        $boxX = (int) (($qrSize - $boxSize) / 2);
        $boxY = (int) (($qrSize - $boxSize) / 2);

        $white = imagecolorallocate($base, 255, 255, 255);
        $this->drawRoundedBox($base, $boxX, $boxY, $boxSize, $boxSize, 18, $white);

        imagecopyresampled(
            $base,
            $logo,
            $boxX + $padding,
            $boxY + $padding,
            0,
            0,
            $logoSize,
            $logoSize,
            imagesx($logo),
            imagesy($logo)
        );

        $blob = $this->encodePng($base);

        imagedestroy($base);
        imagedestroy($logo);

        if ($blob === '') {
            Log::warning('[QR][BACKEND] imagepng() failed while compositing logo; returning QR without logo');
            return $pngBlob;
        }

        return $blob;
    }

    private function drawRoundedBox(GdImage $image, int $x, int $y, int $width, int $height, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
        imagefilledrectangle($image, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
        imagefilledellipse($image, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
    }

    private function encodePng(GdImage $image): string
    {
        ob_start();
        $success = imagepng($image);
        $blob = ob_get_clean();

        if (! $success || $blob === false) {
            return '';
        }

        return $blob;
    }
}
