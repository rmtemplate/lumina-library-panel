<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrCodeType;
use App\Http\Controllers\Controller;
use App\Models\BookItem;
use App\Services\Admin\BranchAccessService;
use App\Services\Api\Qr\QrImageService;
use App\Services\Api\Qr\QrPayloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Printable QR labels for physical book copies.
 *
 * Book QR is a PERMANENT label: it reuses the same signed payload machinery
 * as the pickup/return QRs (QrPayloadService — iss/aud/source/owner/jti/sig)
 * but omits `exp`, so verify() never expires it. The Universal Scanner's
 * scanBook() verifies the signature against QrCodeType::BOOK_INTAKE and
 * resolves the copy from book_item_id/item_code/barcode — no raw-ID exposure,
 * no second payload format. Rendering is delegated to the shared
 * QrImageService, so style (ECC-H, centered logo, rounded white box) matches
 * every other QR in the system.
 */
class BookItemQrController extends Controller
{
    public function __construct(
        private readonly QrImageService $qrImageService,
        private readonly QrPayloadService $qrPayloadService,
        private readonly BranchAccessService $branchAccess
    ) {
    }

    /** On-screen preview with download/print actions. */
    public function preview(Request $request, BookItem $bookItem): View
    {
        $this->branchAccess->assertCanAccessBranch($request->user(), $bookItem->branch_id);
        $bookItem->load(['book', 'branch', 'rack']);

        return view('admin.book-items.qr.preview', [
            'item' => $bookItem,
            'qrDataUri' => $this->qrImageService->dataUriFromText($this->qrContent($bookItem)),
        ]);
    }

    /**
     * Render a single label as PNG. Defaults to an attachment download;
     * pass ?inline=1 to render inline (used as the modal's <img src>).
     */
    public function png(Request $request, BookItem $bookItem): Response
    {
        $this->branchAccess->assertCanAccessBranch($request->user(), $bookItem->branch_id);

        $disposition = $request->boolean('inline')
            ? 'inline'
            : 'attachment; filename="' . $this->fileBase($bookItem) . '.png"';

        return response($this->qrImageService->pngFromText($this->qrContent($bookItem)), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => $disposition,
        ]);
    }

    /** Download a single label as scalable SVG, named by item_code. */
    public function svg(Request $request, BookItem $bookItem): Response
    {
        $this->branchAccess->assertCanAccessBranch($request->user(), $bookItem->branch_id);

        return response($this->qrImageService->svgFromText($this->qrContent($bookItem)), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $this->fileBase($bookItem) . '.svg"',
        ]);
    }

    /** A4 printable sheet for a single copy. */
    public function print(Request $request, BookItem $bookItem): View
    {
        $this->branchAccess->assertCanAccessBranch($request->user(), $bookItem->branch_id);
        $bookItem->load(['book', 'branch', 'rack']);

        return view('admin.book-items.qr.print', [
            'labels' => [$this->label($bookItem)],
        ]);
    }

    /** A4 printable sheet for many selected copies. */
    public function bulkPrint(Request $request): View
    {
        $items = $this->resolveSelected($request);

        abort_if($items->isEmpty(), 404, 'Tidak ada copy terpilih untuk dicetak.');

        return view('admin.book-items.qr.print', [
            'labels' => $items->map(fn (BookItem $item) => $this->label($item))->all(),
        ]);
    }

    /** Bundle PNG labels for the selected copies into a downloadable ZIP. */
    public function bulkZip(Request $request): BinaryFileResponse|RedirectResponse
    {
        $items = $this->resolveSelected($request);

        if ($items->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu copy untuk diunduh.');
        }

        $zipPath = storage_path('app/' . Str::uuid() . '.zip');
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Gagal membuat arsip ZIP.');
        }

        $used = [];
        foreach ($items as $item) {
            $name = $this->fileBase($item);
            // Guard against duplicate item_codes/barcodes colliding in the zip.
            $suffix = isset($used[$name]) ? '-' . (++$used[$name]) : '';
            $used[$name] = $used[$name] ?? 0;

            $zip->addFromString(
                $name . $suffix . '.png',
                $this->qrImageService->pngFromText($this->qrContent($item))
            );
        }

        $zip->close();

        return response()
            ->download($zipPath, 'book-qr-labels-' . now()->format('Ymd-His') . '.zip')
            ->deleteFileAfterSend(true);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * The string the QR encodes: a signed, permanent BOOK_INTAKE payload
     * (no `exp`). Reuses QrPayloadService so signature/verification is
     * identical to pickup/return QRs — only the type differs and expiry is
     * omitted. The scanner reads book_item_id/item_code/barcode from the
     * verified payload, so no raw ID is exposed unsigned.
     */
    private function qrContent(BookItem $item): string
    {
        $payload = $this->qrPayloadService->makePayload(QrCodeType::BOOK_INTAKE, [
            'qr_code' => $item->id,          // jti — permanent reference id
            'book_item_id' => $item->id,
            'item_code' => $item->item_code,
            'barcode' => $item->barcode,
            'branch_id' => $item->branch_id,
            // no expires_at → permanent (verify() skips the expiry check)
        ]);

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function fileBase(BookItem $item): string
    {
        $base = $item->item_code ?: $item->barcode ?: $item->id;

        return Str::slug((string) $base) ?: 'book-item';
    }

    /** @return array<string,mixed> */
    private function label(BookItem $item): array
    {
        return [
            'qr' => $this->qrImageService->dataUriFromText($this->qrContent($item)),
            'title' => $item->book?->title ?? '-',
            'author' => $item->book?->author,
            'item_code' => $item->item_code,
            'barcode' => $item->barcode,
            'branch' => $item->branch?->name,
            'cover' => $item->book?->cover_url,
        ];
    }

    /** Branch-scoped fetch of the checkbox-selected copies. */
    private function resolveSelected(Request $request): Collection
    {
        $ids = collect($request->input('ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $query = BookItem::query()
            ->with(['book', 'branch', 'rack'])
            ->whereIn('id', $ids);

        // Non-super-admins only ever get their own branch's copies.
        $this->branchAccess->scope($query, $request->user());

        return $query->get();
    }
}
