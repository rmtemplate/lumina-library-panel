<?php

namespace App\Services\Api\MyBook;

use App\Enums\QrCodeType;
use App\Events\Qr\QrGenerated;
use App\Events\Qr\QrRefreshed;
use App\Models\Borrowing;
use App\Models\BorrowingQrCode;
use App\Models\User;
use App\Services\Api\Qr\QrImageService;
use App\Services\Api\Qr\QrPayloadService;
use App\Services\AppSettingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\Api\Borrowing\BorrowingOverdueService;
use App\Services\Api\Borrowing\BorrowingReservationExpiryService;
use App\Services\Api\Borrowing\FineCalculator;

class MyBookService
{
    public function __construct(
        private readonly AppSettingService $appSettingService,
        private readonly QrPayloadService $qrPayloadService,
        private readonly QrImageService $qrImageService,
        private readonly BorrowingReservationExpiryService $reservationExpiryService,
        private readonly FineCalculator $fineCalculator,
        private readonly BorrowingOverdueService $overdueService
    ) {
    }

    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        // Runtime backstops: self-heal stale awaiting_pickup/overdue rows on
        // read, regardless of whether the scheduler/cron is running.
        $this->reservationExpiryService->expireDueReservations(200);
        $this->overdueService->flagDueOverdueBorrowings(200);

        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 50);
        $status = $filters['status'] ?? null;

        $query = Borrowing::query()
            ->with($this->borrowingRelations())
            ->where('user_id', $user->id);

        $this->applyStatusFilter($query, $status);

        $borrowings = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachComputedFields($borrowings->getCollection());

        return $borrowings;
    }

    public function findDetail(User $user, string $borrowingId): Borrowing
    {
        // Targeted runtime backstops for this single row.
        $this->reservationExpiryService->expireOne($borrowingId);
        $this->overdueService->flagOne($borrowingId);

        $borrowing = Borrowing::query()
            ->with($this->borrowingRelations())
            ->where('id', $borrowingId)
            ->where('user_id', $user->id)
            ->first();

        if (! $borrowing) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Data peminjaman tidak ditemukan.'],
            ]);
        }

        $this->attachComputedFields(collect([$borrowing]));

        return $borrowing;
    }

    public function refreshReturnQr(User $user, string $borrowingId): array
    {
        return DB::transaction(function () use ($user, $borrowingId) {
            $borrowing = Borrowing::query()
                ->with($this->borrowingRelations())
                ->where('id', $borrowingId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $borrowing) {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['Data peminjaman tidak ditemukan.'],
                ]);
            }

            $this->attachComputedFields(collect([$borrowing]));

            $computedStatus = (string) $borrowing->getAttribute('computed_status');

            // Explicit, specific message for the payment-required case —
            // distinct from the generic "not currently borrowed" case below,
            // so the Flutter UI can tell the member exactly why (and route
            // them to the payment page instead of just retrying).
            if ($computedStatus === Borrowing::WAITING_PAYMENT) {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['Lunasi denda terlebih dahulu sebelum membuat QR pengembalian.'],
                ]);
            }

            if (! in_array($computedStatus, ['active', 'due_soon', 'overdue'], true)) {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['QR pengembalian hanya bisa dibuat untuk buku yang sedang dipinjam.'],
                ]);
            }

            $hadAnyPreviousQr = BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('qr_type', QrCodeType::BORROWING_RETURN->value)
                ->exists();

            $previousQr = BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('qr_type', QrCodeType::BORROWING_RETURN->value)
                ->where('status', 'active')
                ->lockForUpdate()
                ->latest('created_at')
                ->first();

            if ($previousQr) {
                $previousQr->forceFill([
                    'status' => 'refreshed',
                    'updated_at' => now(),
                    'updated_by' => $user->id,
                ])->save();
            }

            $ttlSeconds = (int) config('lumina.qr.return_ttl_seconds', 60);
            $expiresAt = now()->addSeconds($ttlSeconds);
            $qrCodeToken = $this->generateQrCodeToken();
            $branchId = $this->resolveBorrowingBranchId($borrowing);

            $payload = $this->qrPayloadService->makePayload(QrCodeType::BORROWING_RETURN, [
                'qr_code' => $qrCodeToken,
                'borrowing_id' => $borrowing->id,
                'borrowing_code' => $borrowing->borrowing_code,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'expires_at' => $expiresAt,
            ]);

            $qr = BorrowingQrCode::query()->create([
                'id' => (string) Str::uuid(),
                'refreshed_from_id' => $previousQr?->id,
                'borrowing_id' => $borrowing->id,
                'qr_code' => $qrCodeToken,
                'qr_type' => QrCodeType::BORROWING_RETURN->value,
                'status' => 'active',
                'refresh_count' => ((int) ($previousQr?->refresh_count ?? 0)) + 1,
                'payload' => $payload,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'used_by' => null,
                'last_refreshed_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('[QR][REFRESH] Return QR refreshed', [
                'borrowing_id' => $borrowing->id,
                'previous_qr_id' => $previousQr?->id,
                'new_qr_id' => $qr->id,
                'refresh_count' => $qr->refresh_count,
                'expires_at' => $qr->expires_at?->toISOString(),
                'user_id' => $user->id,
            ]);

            DB::afterCommit(function () use ($user, $qr, $previousQr, $hadAnyPreviousQr) {
                if ($hadAnyPreviousQr) {
                    QrRefreshed::dispatch($user->id, $qr, $previousQr?->id);
                    Log::info('[WS] Broadcast sent: qr.refreshed', ['qr_id' => $qr->id, 'channel' => 'user.' . $user->id]);
                } else {
                    QrGenerated::dispatch($user->id, $qr);
                    Log::info('[WS] Broadcast sent: qr.generated', ['qr_id' => $qr->id, 'channel' => 'user.' . $user->id]);
                }
            });

            return [
                'borrowing' => [
                    'id' => $borrowing->id,
                    'code' => $borrowing->borrowing_code,
                    'status' => $computedStatus,
                    'due_date' => $borrowing->due_date,
                    'days_remaining' => (int) $borrowing->getAttribute('days_remaining'),
                    'overdue_days' => (int) $borrowing->getAttribute('overdue_days'),
                    'estimated_fine' => (float) $borrowing->getAttribute('estimated_fine'),
                ],
                'qr' => [
                    'id' => $qr->id,
                    'code' => $qr->qr_code,
                    'type' => $qr->qr_type,
                    'type_label' => QrCodeType::BORROWING_RETURN->label(),
                    'status' => $qr->status,
                    'expires_at' => $qr->expires_at?->toISOString(),
                    'ttl_seconds' => $ttlSeconds,
                    'refresh_count' => (int) $qr->refresh_count,
                    'image' => $this->qrImageService->makeDataUri($payload),
                ],
            ];
        }, 3);
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        $today = now()->toDateString();
        $dueSoonMaxDate = now()
            ->addDays($this->returnReminderDays())
            ->toDateString();

        match ($status) {
            'active' => $query
                ->where('status', 'active')
                ->whereDate('due_date', '>', $dueSoonMaxDate),

            'due_soon' => $query
                ->where('status', 'active')
                ->whereDate('due_date', '>=', $today)
                ->whereDate('due_date', '<=', $dueSoonMaxDate),

            'overdue' => $query
                ->where('status', 'active')
                ->whereDate('due_date', '<', $today),

            'returned' => $query
                ->whereIn('status', ['returned', 'returned_with_fine']),

            // Default: tampilkan semua status — termasuk pending, awaiting_pickup, dll.
            default => null,
        };
    }

    private function attachComputedFields(Collection $borrowings): void
    {
        $today = now()->startOfDay();
        $reminderDays = $this->returnReminderDays();
        $finePerDay = $this->finePerDay();

        $borrowings->each(function (Borrowing $borrowing) use ($today, $reminderDays, $finePerDay) {
            $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();
            $rawDaysRemaining = (int) $today->diffInDays($dueDate, false);

            $daysRemaining = max(0, $rawDaysRemaining);
            $overdueDays = $rawDaysRemaining < 0 ? abs($rawDaysRemaining) : 0;

            $borrowedItemsCount = $borrowing->relationLoaded('items')
                ? $borrowing->items
                    ->whereIn('status', ['active', 'pending'])
                    ->count()
                : 1;

            $borrowedItemsCount = max($borrowedItemsCount, 1);

            $computedStatus = $this->computeStatus(
                rawStatus: (string) $borrowing->status,
                daysRemaining: $daysRemaining,
                overdueDays: $overdueDays,
                reminderDays: $reminderDays
            );

            $estimatedFine = $computedStatus === 'overdue'
                ? $overdueDays * $finePerDay * $borrowedItemsCount
                : 0;

            $borrowing->setAttribute('computed_status', $computedStatus);
            $borrowing->setAttribute('days_remaining', $daysRemaining);
            $borrowing->setAttribute('overdue_days', $overdueDays);
            $borrowing->setAttribute('estimated_fine', $estimatedFine);
            $borrowing->setAttribute('fine_per_day', $finePerDay);
            $borrowing->setAttribute('return_reminder_days', $reminderDays);
        });
    }

    private function computeStatus(
        string $rawStatus,
        int $daysRemaining,
        int $overdueDays,
        int $reminderDays
    ): string {
        if (in_array($rawStatus, ['returned', 'returned_with_fine'], true)) {
            return 'returned';
        }

        if ($rawStatus === 'active') {
            if ($overdueDays > 0) {
                return 'overdue';
            }

            if ($daysRemaining <= $reminderDays) {
                return 'due_soon';
            }

            return 'active';
        }

        return $rawStatus;
    }

    private function borrowingRelations(): array
    {
        return [
            $this->branchRelationName(),
            'items.book.category',
            'items.bookItem.rack',
            'items.bookItem.branch',
        ];
    }

    private function branchRelationName(): string
    {
        return Schema::hasColumn('borrowings', 'branch_id')
            ? 'branch'
            : 'libraryBranch';
    }

    private function resolveBorrowingBranchId(Borrowing $borrowing): ?string
    {
        return $borrowing->branch_id
            ?? $borrowing->library_branch_id
            ?? null;
    }

    private function returnReminderDays(): int
    {
        return (int) config('lumina.borrowing.return_reminder_days', 2);
    }

    private function finePerDay(): float
    {
        return $this->fineCalculator->finePerDay();
    }

    private function generateQrCodeToken(): string
    {
        // See QrRefreshService::generateQrCodeToken() — UUIDv4 avoids the
        // exists()-check-then-create TOCTOU race under concurrent refreshes.
        return 'QR-' . strtoupper(str_replace('-', '', (string) Str::uuid()));
    }
}