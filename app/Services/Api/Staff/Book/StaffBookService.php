<?php

namespace App\Services\Api\Staff\Book;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\User;
use App\Services\Api\Qr\QrImageService;
use App\Services\Api\Staff\Broadcast\StaffBroadcastService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Staff book registration — creates the catalog `Book` row plus `quantity`
 * physical `BookItem` copies, each with its own item_code/barcode and a
 * static QR image (no signature/expiry — unlike borrowing pickup/return
 * QRs, a book's QR is a permanent inventory label scanned over and over,
 * so it doesn't go through QrPayloadService's signed-payload machinery;
 * StaffQrScanService::scanBook() already resolves these by plain
 * barcode/item_code lookup, see its `findBookItem()` fallback).
 */
class StaffBookService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly QrImageService $qrImageService,
        private readonly StaffBroadcastService $staffBroadcastService
    ) {
    }

    public function register(User $staff, array $payload, ?UploadedFile $cover = null): array
    {
        return DB::transaction(function () use ($staff, $payload, $cover) {
            $branchId = $this->staffAccessService->branchId($staff);

            $book = Book::create([
                'title' => $payload['title'],
                'author' => $payload['author'],
                'isbn' => $payload['isbn'] ?? null,
                'publisher' => $payload['publisher'] ?? null,
                'category_id' => $payload['category_id'],
                'is_active' => true,
                'created_by' => $staff->id,
                'updated_by' => $staff->id,
            ]);

            if ($cover) {
                $path = $cover->store('books/' . $book->id, ['disk' => 'public']);
                $book->forceFill(['cover_path' => $path, 'cover_is_external' => false])->save();
            }

            $quantity = (int) $payload['quantity'];
            $bookCode = (string) $payload['book_code'];
            $items = [];

            for ($i = 1; $i <= $quantity; $i++) {
                $itemCode = $quantity > 1 ? "{$bookCode}-{$i}" : $bookCode;
                $barcode = $this->generateBarcode();

                $bookItem = BookItem::create([
                    'book_id' => $book->id,
                    'branch_id' => $branchId,
                    'rack_id' => $payload['rack_id'] ?? null,
                    'item_code' => $itemCode,
                    'barcode' => $barcode,
                    'acquisition_date' => now()->toDateString(),
                    'status' => BookItem::AVAILABLE,
                    'is_active' => true,
                    'created_by' => $staff->id,
                    'updated_by' => $staff->id,
                ]);

                $items[] = [
                    'id' => $bookItem->id,
                    'item_code' => $bookItem->item_code,
                    'barcode' => $bookItem->barcode,
                    'qr_image' => $this->qrImageService->makeDataUri([
                        'type' => 'book_intake',
                        'book_item_id' => $bookItem->id,
                        'item_code' => $bookItem->item_code,
                        'barcode' => $bookItem->barcode,
                        'branch_id' => $branchId,
                    ]),
                ];
            }

            $this->staffBroadcastService->bookRegistered($book, $branchId, $quantity);

            return [
                'book' => $book->refresh()->load('category'),
                'items' => $items,
            ];
        }, 3);
    }

    /**
     * GET /staff/books/search — title / ISBN / barcode / book code / author.
     * Returns BookItem rows (the unit staff actually pick for the Borrow
     * Cart) scoped to their branch, including only available copies.
     */
    public function search(User $staff, string $query): \Illuminate\Support\Collection
    {
        $branchId = $this->staffAccessService->branchId($staff);
        $keyword = '%' . addcslashes(trim($query), '\\%_') . '%';

        return BookItem::query()
            ->with(['book.category', 'rack'])
            ->where('branch_id', $branchId)
            ->where('status', BookItem::AVAILABLE)
            ->where(function ($q) use ($keyword) {
                $q->where('item_code', 'like', $keyword)
                    ->orWhere('barcode', 'like', $keyword)
                    ->orWhereHas('book', function ($q) use ($keyword) {
                        $q->where('title', 'like', $keyword)
                            ->orWhere('author', 'like', $keyword)
                            ->orWhere('isbn', 'like', $keyword);
                    });
            })
            ->limit(30)
            ->get();
    }

    private function generateBarcode(): string
    {
        do {
            $barcode = now()->format('ymdHis') . random_int(100, 999);
        } while (BookItem::query()->where('barcode', $barcode)->exists());

        return $barcode;
    }
}
