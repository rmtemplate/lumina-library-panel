<?php

namespace App\Services\Api\Checkout;

use App\Enums\QrCodeType;
use App\Events\Qr\QrGenerated;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingQrCode;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Fine;
use App\Models\LibraryBranch;
use App\Models\User;
use App\Services\Api\Qr\QrImageService;
use App\Services\Api\Qr\QrPayloadService;
use App\Services\AppSettingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly AppSettingService $appSettingService,
        private readonly QrPayloadService $qrPayloadService,
        private readonly QrImageService $qrImageService
    ) {
    }

    public function checkout(User $user, array $payload): array
    {
        return DB::transaction(function () use ($user, $payload) {
            $this->assertNoUnpaidNoShowFine($user);

            $branchId = (string) $payload['branch_id'];

            $cartItemIds = collect($payload['cart_item_ids'] ?? [])
                ->filter()
                ->values();

            $startDate = Carbon::parse($payload['start_date'])->startOfDay();
            $endDate = Carbon::parse($payload['end_date'])->startOfDay();

            $this->validateDates($startDate, $endDate);

            $branch = $this->findActiveBranch($branchId);

            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => ['Keranjang tidak ditemukan.'],
                ]);
            }

            $cartItemsQuery = CartItem::query()
                ->with(['book'])
                ->where('cart_id', $cart->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate();

            if ($cartItemIds->isNotEmpty()) {
                $cartItemsQuery->whereIn('id', $cartItemIds->all());
            }

            $cartItems = $cartItemsQuery
                ->orderBy('added_at')
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Tidak ada buku pada keranjang untuk perpustakaan yang dipilih.'],
                ]);
            }

            if ($cartItemIds->isNotEmpty() && $cartItems->count() !== $cartItemIds->count()) {
                throw ValidationException::withMessages([
                    'cart_item_ids' => ['Beberapa item cart tidak valid atau tidak berada pada perpustakaan yang dipilih.'],
                ]);
            }

            $maxBorrowBooks = $this->appSettingService->getInt('max_borrow_books', 5);

            if ($cartItems->count() > $maxBorrowBooks) {
                throw ValidationException::withMessages([
                    'cart' => ["Maksimal buku yang bisa dipinjam sekaligus adalah {$maxBorrowBooks} buku."],
                ]);
            }

            $bookIds = $cartItems
                ->pluck('book_id')
                ->unique()
                ->values();

            $availableBookItems = BookItem::query()
                ->with(['book', 'rack'])
                ->where('branch_id', $branchId)
                ->whereIn('book_id', $bookIds)
                ->where('status', BookItem::AVAILABLE)
                ->where('is_active', true)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get()
                ->groupBy('book_id');

            $selectedItems = $this->matchCartItemsWithLockedStock(
                cartItems: $cartItems,
                availableBookItems: $availableBookItems
            );

            $borrowing = $this->createBorrowing(
                user: $user,
                branchId: $branchId,
                startDate: $startDate,
                endDate: $endDate
            );

            $borrowingItems = $this->createBorrowingItemsAndReserveStock(
                borrowing: $borrowing,
                selectedItems: $selectedItems,
                user: $user
            );

            $qr = $this->createBorrowingQr(
                borrowing: $borrowing,
                user: $user,
                branchId: $branchId,
                startDate: $startDate,
                endDate: $endDate
            );

            CartItem::query()
                ->whereIn('id', $cartItems->pluck('id')->all())
                ->delete();

            DB::afterCommit(function () use ($user, $qr) {
                QrGenerated::dispatch($user->id, $qr);

                Log::info('[WS] Broadcast sent: qr.generated', [
                    'qr_id' => $qr->id,
                    'borrowing_id' => $qr->borrowing_id,
                    'channel' => 'user.' . $user->id,
                ]);
            });

            return $this->buildCheckoutResponse(
                borrowing: $borrowing,
                borrowingItems: collect($borrowingItems),
                qr: $qr,
                branch: $branch,
                startDate: $startDate,
                endDate: $endDate
            );
        }, 3);
    }

    /**
     * Blocks new checkouts while the member has an unpaid pickup no-show
     * penalty (see BorrowingReservationExpiryService::expireOne()) —
     * paying it is what lifts this block. Deliberately does NOT try to
     * resurrect the expired borrowing/its reserved items (those may
     * already be gone) — see docs/borrowing-lifecycle.md.
     */
    private function assertNoUnpaidNoShowFine(User $user): void
    {
        $hasUnpaidNoShowFine = Fine::query()
            ->where('user_id', $user->id)
            ->where('fine_type', Fine::TYPE_PICKUP_NO_SHOW)
            ->where('status', Fine::UNPAID)
            ->exists();

        if ($hasUnpaidNoShowFine) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Anda memiliki denda tidak mengambil pesanan yang belum dibayar. Selesaikan pembayaran sebelum melakukan checkout baru.'],
            ]);
        }
    }

    private function validateDates(Carbon $startDate, Carbon $endDate): void
    {
        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => ['Tanggal selesai harus sama atau setelah tanggal mulai.'],
            ]);
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $maxBorrowDays = $this->appSettingService->getInt('max_borrow_days', 14);

        if ($totalDays > $maxBorrowDays) {
            throw ValidationException::withMessages([
                'end_date' => ["Maksimal durasi peminjaman adalah {$maxBorrowDays} hari."],
            ]);
        }
    }

    private function findActiveBranch(string $branchId): LibraryBranch
    {
        $branch = LibraryBranch::query()
            ->where('id', $branchId)
            ->where('is_active', true)
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch_id' => ['Perpustakaan tidak ditemukan atau tidak aktif.'],
            ]);
        }

        return $branch;
    }

    private function matchCartItemsWithLockedStock(Collection $cartItems, Collection $availableBookItems): array
    {
        $selected = [];

        foreach ($cartItems as $cartItem) {
            $candidates = $availableBookItems->get($cartItem->book_id, collect());

            if ($candidates->isEmpty()) {
                throw ValidationException::withMessages([
                    'book_id' => ["Buku '{$cartItem->book?->title}' sudah tidak tersedia untuk checkout."],
                ]);
            }

            $bookItem = $candidates->shift();

            $availableBookItems->put($cartItem->book_id, $candidates);

            $selected[] = [
                'cart_item' => $cartItem,
                'book_item' => $bookItem,
            ];
        }

        return $selected;
    }

    private function createBorrowing(
        User $user,
        string $branchId,
        Carbon $startDate,
        Carbon $endDate
    ): Borrowing {
        $branchColumn = Schema::hasColumn('borrowings', 'branch_id')
            ? 'branch_id'
            : 'library_branch_id';

        $reservationHoldHours = $this->appSettingService->getInt(
            'reservation_hold_hours',
            (int) config('lumina.borrowing.pickup_expired_after_hours', 24)
        );

        return Borrowing::query()->create([
            'id' => (string) Str::uuid(),
            'borrowing_code' => $this->generateBorrowingCode(),
            'user_id' => $user->id,
            $branchColumn => $branchId,
            'borrow_date' => $startDate->toDateString(),
            'due_date' => $endDate->toDateString(),
            'return_date' => null,
            'status' => 'awaiting_pickup',
            'notes' => null,
            'approved_by' => null,
            'approved_at' => null,
            'reservation_expires_at' => now()->addHours($reservationHoldHours),
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createBorrowingItemsAndReserveStock(
        Borrowing $borrowing,
        array $selectedItems,
        User $user
    ): array {
        $created = [];

        foreach ($selectedItems as $selected) {
            /** @var CartItem $cartItem */
            $cartItem = $selected['cart_item'];

            /** @var BookItem $bookItem */
            $bookItem = $selected['book_item'];

            $borrowingItem = BorrowingItem::query()->create([
                'id' => (string) Str::uuid(),
                'borrowing_id' => $borrowing->id,
                'book_id' => $cartItem->book_id,
                'book_item_id' => $bookItem->id,
                'status' => 'pending',
                'return_date' => null,
                'return_condition' => null,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bookItem->forceFill([
                'status' => BookItem::RESERVED,
                'updated_by' => $user->id,
                'updated_at' => now(),
            ])->save();

            $created[] = $borrowingItem->load(['book', 'bookItem.rack']);
        }

        return $created;
    }

    private function createBorrowingQr(
        Borrowing $borrowing,
        User $user,
        string $branchId,
        Carbon $startDate,
        Carbon $endDate
    ): BorrowingQrCode {
        $ttlSeconds = (int) config('lumina.qr.pickup_ttl_seconds', 60);
        $expiresAt = now()->addSeconds($ttlSeconds);
        $qrCodeToken = $this->generateQrCodeToken();

        $payload = $this->qrPayloadService->makePayload(QrCodeType::BORROWING_PICKUP, [
            'qr_code' => $qrCodeToken,
            'borrowing_id' => $borrowing->id,
            'borrowing_code' => $borrowing->borrowing_code,
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'expires_at' => $expiresAt,
        ]);

        return BorrowingQrCode::query()->create([
            'id' => (string) Str::uuid(),
            'refreshed_from_id' => null,
            'borrowing_id' => $borrowing->id,
            'qr_code' => $qrCodeToken,
            'qr_type' => QrCodeType::BORROWING_PICKUP->value,
            'status' => 'active',
            'refresh_count' => 0,
            'payload' => $payload,
            'expires_at' => $expiresAt,
            'used_at' => null,
            'used_by' => null,
            'last_refreshed_at' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buildCheckoutResponse(
        Borrowing $borrowing,
        Collection $borrowingItems,
        BorrowingQrCode $qr,
        LibraryBranch $branch,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        return [
            'borrowing' => [
                'id' => $borrowing->id,
                'code' => $borrowing->borrowing_code,
                'status' => $borrowing->status,
                'reservation_expires_at' => $borrowing->reservation_expires_at?->toISOString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $startDate->diffInDays($endDate) + 1,
                'branch' => [
                    'id' => $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                    'address' => $branch->address,
                ],
                'items' => $borrowingItems->map(function (BorrowingItem $item) {
                    return [
                        'id' => $item->id,
                        'book_id' => $item->book_id,
                        'book_item_id' => $item->book_item_id,
                        'status' => $item->status,
                        'book' => [
                            'id' => $item->book?->id,
                            'title' => $item->book?->title,
                            'author' => $item->book?->author,
                            'cover_url' => $item->book?->cover_url,
                        ],
                        'physical_item' => [
                            'id' => $item->bookItem?->id,
                            'item_code' => $item->bookItem?->item_code,
                            'barcode' => $item->bookItem?->barcode,
                            'status' => $item->bookItem?->status,
                            'rack' => $item->bookItem?->rack ? [
                                'id' => $item->bookItem->rack->id,
                                'code' => $item->bookItem->rack->rack_code,
                                'name' => $item->bookItem->rack->name,
                                'floor_label' => $item->bookItem->rack->floor_label,
                                'area_label' => $item->bookItem->rack->area_label,
                            ] : null,
                        ],
                    ];
                })->values(),
            ],
            'qr' => [
                'id' => $qr->id,
                'code' => $qr->qr_code,
                'type' => $qr->qr_type,
                'type_label' => QrCodeType::BORROWING_PICKUP->label(),
                'status' => $qr->status,
                'expires_at' => $qr->expires_at?->toISOString(),
                'ttl_seconds' => (int) config('lumina.qr.pickup_ttl_seconds', 60),
                'image' => $this->qrImageService->makeDataUri((array) $qr->payload),
            ],
            'rules' => [
                'max_borrow_days' => $this->appSettingService->getInt('max_borrow_days', 14),
                'max_borrow_books' => $this->appSettingService->getInt('max_borrow_books', 5),
                'reservation_hold_hours' => $this->appSettingService->getInt('reservation_hold_hours', 24),
            ],
        ];
    }

    private function generateBorrowingCode(): string
    {
        do {
            $code = 'BRW-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
        } while (
            Borrowing::query()->where('borrowing_code', $code)->exists()
        );

        return $code;
    }

    private function generateQrCodeToken(): string
    {
        do {
            $code = 'QR-' . strtoupper(Str::random(24));
        } while (
            BorrowingQrCode::query()->where('qr_code', $code)->exists()
        );

        return $code;
    }
}