<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Models\Notification;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffNotificationController extends ApiController
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService
    ) {
    }

    /**
     * GET /api/v1/staff/notifications
     * Returns notifications scoped to the staff's user_id AND branch_id.
     * Staff only sees their own notifications from their branch.
     */
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);
        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);
        $onlyUnread = filter_var($request->query('unread'), FILTER_VALIDATE_BOOLEAN);

        $query = Notification::query()
            ->where('user_id', $staff->id)
            ->where('branch_id', $branchId)
            ->when($onlyUnread, fn ($q) => $q->where('is_read', false))
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage)->withQueryString();

        return $this->paginated(
            paginator: $paginator,
            data: $paginator->getCollection()->map(fn (Notification $n) => $this->format($n)),
            message: 'Notifikasi staff berhasil diambil.',
            code: 'STAFF_NOTIFICATIONS_FOUND'
        );
    }

    /**
     * GET /api/v1/staff/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        $count = Notification::query()
            ->where('user_id', $staff->id)
            ->where('branch_id', $branchId)
            ->where('is_read', false)
            ->count();

        return $this->success(data: ['count' => $count], message: 'OK', code: 'UNREAD_COUNT');
    }

    /**
     * PUT /api/v1/staff/notifications/{id}/read
     */
    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        $notification = Notification::query()
            ->where('id', $notificationId)
            ->where('user_id', $staff->id)
            ->where('branch_id', $branchId)
            ->first();

        if (! $notification) {
            throw ValidationException::withMessages([
                'notification' => ['Notifikasi tidak ditemukan.'],
            ]);
        }

        $notification->forceFill(['is_read' => true, 'read_at' => now()])->save();

        return $this->success(data: $this->format($notification), message: 'Notifikasi ditandai dibaca.', code: 'NOTIFICATION_READ');
    }

    /**
     * PUT /api/v1/staff/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        Notification::query()
            ->where('user_id', $staff->id)
            ->where('branch_id', $branchId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->success(message: 'Semua notifikasi ditandai dibaca.', code: 'ALL_READ');
    }

    private function format(Notification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'reference_type' => $n->reference_type,
            'reference_id' => $n->reference_id,
            'is_read' => $n->is_read,
            'read_at' => $n->read_at?->toISOString(),
            'created_at' => $n->created_at?->toISOString(),
        ];
    }
}
