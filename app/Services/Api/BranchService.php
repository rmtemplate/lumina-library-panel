<?php

namespace App\Services\Api;

use App\Models\BookItem;
use App\Models\BookStock;
use App\Models\LibraryBranch;
use App\Models\User;
use App\Services\Api\Book\BookService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchService
{
    // Average speeds used for travel estimates.
    private const WALK_SPEED_KMH = 5.0;
    private const DRIVE_SPEED_KMH = 30.0;

    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    /**
     * Return all active branches, optionally sorted by proximity.
     * Attaches distance, estimated travel times, and open/closed status.
     */
    public function getNearby(
        ?float $latitude,
        ?float $longitude,
        ?float $maxDistanceKm,
        string $sort = 'nearest'
    ): Collection {
        $query = LibraryBranch::query()->where('is_active', true);

        if ($latitude !== null && $longitude !== null) {
            $distanceSql = $this->distanceSql();
            $query->selectRaw('*, ' . $distanceSql . ' as distance_km', [$latitude, $longitude, $latitude]);

            if ($maxDistanceKm !== null) {
                $query->havingRaw('distance_km <= ?', [$maxDistanceKm]);
            }

            if ($sort === 'nearest') {
                $query->orderBy('distance_km');
            }
        } else {
            $query->orderBy('name');
        }

        return $query->get()->map(fn (LibraryBranch $branch) => $this->formatBranch(
            branch: $branch,
            latitude: $latitude,
            longitude: $longitude,
            distanceKm: isset($branch->distance_km) ? (float) $branch->distance_km : null
        ));
    }

    /**
     * Return a single branch's full detail with distance and schedule.
     */
    public function getDetail(string $branchId, ?float $latitude, ?float $longitude): array
    {
        $query = LibraryBranch::query()->where('is_active', true)->where('id', $branchId);

        if ($latitude !== null && $longitude !== null) {
            $distanceSql = $this->distanceSql();
            $query->selectRaw('*, ' . $distanceSql . ' as distance_km', [$latitude, $longitude, $latitude]);
        }

        $branch = $query->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch' => ['Cabang perpustakaan tidak ditemukan atau tidak aktif.'],
            ]);
        }

        $distanceKm = isset($branch->distance_km) ? (float) $branch->distance_km : null;

        $stockSummary = BookStock::query()
            ->where('library_branch_id', $branchId)
            ->selectRaw('SUM(total_stock) as total, SUM(available_stock) as available')
            ->first();

        $itemCounts = BookItem::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return array_merge(
            $this->formatBranch($branch, $latitude, $longitude, $distanceKm),
            [
                'stock_summary' => [
                    'total_stock' => (int) ($stockSummary?->total ?? 0),
                    'available_stock' => (int) ($stockSummary?->available ?? 0),
                ],
                'book_item_summary' => [
                    'available' => (int) ($itemCounts['available'] ?? 0),
                    'borrowed' => (int) ($itemCounts['borrowed'] ?? 0),
                    'reserved' => (int) ($itemCounts['reserved'] ?? 0),
                    'lost' => (int) ($itemCounts['lost'] ?? 0),
                    'damaged' => (int) ($itemCounts['damaged'] ?? 0),
                ],
            ]
        );
    }

    /**
     * Return books available at a specific branch (reuses BookService with branch_id injected).
     */
    public function getBranchBooks(string $branchId, array $filters, ?User $user): LengthAwarePaginator
    {
        // Validate the branch exists first.
        $branchExists = LibraryBranch::where('id', $branchId)->where('is_active', true)->exists();

        if (! $branchExists) {
            throw ValidationException::withMessages([
                'branch' => ['Cabang perpustakaan tidak ditemukan atau tidak aktif.'],
            ]);
        }

        // Force branch_id filter regardless of what caller passes.
        $filters['branch_id'] = $branchId;

        return $this->bookService->getBooks(filters: $filters, user: $user);
    }

    // ── Internals ─────────────────────────────────────────────────────────

    private function formatBranch(
        LibraryBranch $branch,
        ?float $latitude,
        ?float $longitude,
        ?float $distanceKm
    ): array {
        $scheduleInfo = $this->resolveSchedule($branch);
        $travelEstimate = $distanceKm !== null ? $this->travelEstimate($distanceKm) : null;

        return [
            'id' => $branch->id,
            'code' => $branch->code,
            'name' => $branch->name,
            'address' => $branch->address,
            'description' => $branch->description,
            'phone' => $branch->phone,
            'email' => $branch->email,
            'image_url' => $branch->image_url,
            'coordinates' => [
                'latitude' => $branch->latitude !== null ? (float) $branch->latitude : null,
                'longitude' => $branch->longitude !== null ? (float) $branch->longitude : null,
            ],
            'schedule' => $scheduleInfo,
            'distance' => $distanceKm !== null ? [
                'km' => round($distanceKm, 2),
                'label' => $this->distanceLabel($distanceKm),
            ] : null,
            'travel_estimate' => $travelEstimate,
            'user_location' => ($latitude !== null && $longitude !== null) ? [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ] : null,
        ];
    }

    private function resolveSchedule(LibraryBranch $branch): array
    {
        $openTime = $branch->open_time;
        $closeTime = $branch->close_time;

        $isOpen = false;
        $opensAt = null;
        $closesAt = null;
        $minutesUntilOpen = null;
        $minutesUntilClose = null;

        if ($openTime && $closeTime) {
            $now = Carbon::now();
            $todayOpen = Carbon::today()->setTimeFromTimeString((string) $openTime);
            $todayClose = Carbon::today()->setTimeFromTimeString((string) $closeTime);

            $opensAt = $todayOpen->format('H:i');
            $closesAt = $todayClose->format('H:i');

            if ($now->between($todayOpen, $todayClose)) {
                $isOpen = true;
                $minutesUntilClose = (int) $now->diffInMinutes($todayClose, false);
            } elseif ($now->lt($todayOpen)) {
                $minutesUntilOpen = (int) $now->diffInMinutes($todayOpen, false);
            }
        }

        return [
            'is_open' => $isOpen,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'description' => $branch->schedule_description,
            'minutes_until_open' => $minutesUntilOpen,
            'minutes_until_close' => $minutesUntilClose,
            'status_label' => $isOpen
                ? ($minutesUntilClose !== null && $minutesUntilClose <= 30 ? 'Tutup dalam ' . $minutesUntilClose . ' menit' : 'Buka')
                : ($minutesUntilOpen !== null ? 'Buka dalam ' . $minutesUntilOpen . ' menit' : 'Tutup'),
        ];
    }

    private function travelEstimate(float $distanceKm): array
    {
        $walkMinutes = (int) ceil($distanceKm / self::WALK_SPEED_KMH * 60);
        $driveMinutes = (int) ceil($distanceKm / self::DRIVE_SPEED_KMH * 60);

        return [
            'walking' => [
                'minutes' => $walkMinutes,
                'label' => $this->minuteLabel($walkMinutes),
            ],
            'driving' => [
                'minutes' => $driveMinutes,
                'label' => $this->minuteLabel($driveMinutes),
            ],
        ];
    }

    private function distanceLabel(float $km): string
    {
        if ($km < 1.0) {
            return (int) round($km * 1000) . ' m';
        }

        return number_format($km, 1) . ' km';
    }

    private function minuteLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} menit";
        }

        $hours = (int) floor($minutes / 60);
        $remaining = $minutes % 60;

        return $remaining > 0 ? "{$hours} jam {$remaining} menit" : "{$hours} jam";
    }

    private function distanceSql(): string
    {
        return "
            6371 * ACOS(
                LEAST(1, GREATEST(-1,
                    COS(RADIANS(?))
                    * COS(RADIANS(library_branches.latitude))
                    * COS(RADIANS(library_branches.longitude) - RADIANS(?))
                    + SIN(RADIANS(?))
                    * SIN(RADIANS(library_branches.latitude))
                ))
            )
        ";
    }
}
