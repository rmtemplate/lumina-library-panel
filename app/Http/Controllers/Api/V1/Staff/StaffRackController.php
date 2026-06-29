<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\StaffMoveBookRequest;
use App\Http\Requests\Api\Staff\StaffRackRequest;
use App\Models\BookMoveHistory;
use App\Services\Api\Staff\Rack\StaffRackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffRackController extends ApiController
{
    public function __construct(
        private readonly StaffRackService $rackService
    ) {
    }

    /**
     * GET /api/v1/staff/racks
     * List all active racks in the staff's branch, with book counts.
     */
    public function index(Request $request): JsonResponse
    {
        $racks = $this->rackService->listRacks($request->user());

        return $this->success(data: $racks, message: 'Data rak berhasil diambil.', code: 'RACKS_FOUND');
    }

    /**
     * POST /api/v1/staff/racks
     * Create a new rack in the staff's branch.
     */
    public function store(StaffRackRequest $request): JsonResponse
    {
        $rack = $this->rackService->createRack($request->user(), $request->validated());

        return $this->success(data: $rack, message: 'Rak berhasil dibuat.', code: 'RACK_CREATED', httpCode: 201);
    }

    /**
     * GET /api/v1/staff/racks/{rack}
     * Rack detail with books inside it.
     */
    public function show(Request $request, string $rackId): JsonResponse
    {
        $data = $this->rackService->showRack($request->user(), $rackId);

        return $this->success(data: $data, message: 'Detail rak berhasil diambil.', code: 'RACK_FOUND');
    }

    /**
     * PUT /api/v1/staff/racks/{rack}
     * Update rack metadata.
     */
    public function update(StaffRackRequest $request, string $rackId): JsonResponse
    {
        $rack = $this->rackService->updateRack($request->user(), $rackId, $request->validated());

        return $this->success(data: $rack, message: 'Rak berhasil diperbarui.', code: 'RACK_UPDATED');
    }

    /**
     * DELETE /api/v1/staff/racks/{rack}
     * Deactivate (soft-delete) a rack.
     */
    public function destroy(Request $request, string $rackId): JsonResponse
    {
        $this->rackService->deleteRack($request->user(), $rackId);

        return $this->success(data: null, message: 'Rak berhasil dihapus.', code: 'RACK_DELETED');
    }

    /**
     * GET /api/v1/staff/book-items/{bookItem}/rack
     * Current rack info for a book item.
     */
    public function showBookItemRack(Request $request, string $bookItemId): JsonResponse
    {
        $data = $this->rackService->getBookItemRack($request->user(), $bookItemId);

        return $this->success(data: $data, message: 'Data rak buku berhasil diambil.', code: 'BOOK_RACK_FOUND');
    }

    /**
     * POST /api/v1/staff/book-items/{bookItem}/move
     * Move a book item to a new rack.
     */
    public function move(StaffMoveBookRequest $request, string $bookItemId): JsonResponse
    {
        $result = $this->rackService->moveBookItem(
            staff: $request->user(),
            bookItemId: $bookItemId,
            targetRackId: $request->validated('target_rack_id'),
            reason: $request->validated('reason'),
            notes: $request->validated('notes')
        );

        return $this->success(
            data: $result,
            message: 'Buku berhasil dipindahkan ke rak baru.',
            code: 'BOOK_MOVED'
        );
    }

    /**
     * GET /api/v1/staff/book-items/{bookItem}/move-history
     * Paginated move history for a book item.
     */
    public function moveHistory(Request $request, string $bookItemId): JsonResponse
    {
        $paginator = $this->rackService->getMoveHistory($request->user(), $bookItemId);

        return $this->paginated(
            paginator: $paginator,
            data: $paginator->getCollection()->map(fn (BookMoveHistory $h) => [
                'id' => $h->id,
                'reason' => $h->reason,
                'notes' => $h->notes,
                'source_rack' => $h->sourceRack ? [
                    'id' => $h->sourceRack->id,
                    'rack_code' => $h->sourceRack->rack_code,
                    'name' => $h->sourceRack->name,
                ] : null,
                'destination_rack' => $h->destinationRack ? [
                    'id' => $h->destinationRack->id,
                    'rack_code' => $h->destinationRack->rack_code,
                    'name' => $h->destinationRack->name,
                ] : null,
                'moved_by' => $h->movedBy ? [
                    'id' => $h->movedBy->id,
                    'fullname' => $h->movedBy->fullname,
                ] : null,
                'moved_at' => $h->moved_at?->toISOString(),
            ]),
            message: 'Riwayat perpindahan buku berhasil diambil.',
            code: 'MOVE_HISTORY_FOUND'
        );
    }
}
