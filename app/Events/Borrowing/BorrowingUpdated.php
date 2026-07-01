<?php

namespace App\Events\Borrowing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The SINGLE realtime event for the two-stage return flow (see
 * docs/return-flow.md). One event, many `action`s — the Flutter client reacts
 * by action, so no per-transition event class is needed.
 *
 * Actions:
 *   return_received          — staff scanned the Return QR, books received
 *   overdue_payment_created  — overdue fine outstanding, member must pay
 *   overdue_payment_paid     — overdue settled, staff may inspect
 *   damage_payment_created   — damage/manual charges outstanding, member must pay
 *   damage_payment_paid      — damage settled
 *   borrowing_completed      — loan fully closed
 *
 * payment_type: overdue_fine | damage_charge | null
 * payment_status: pending | paid | null
 */
class BorrowingUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly string $action,
        public readonly string $status,
        public readonly ?string $paymentType = null,
        public readonly ?string $paymentStatus = null,
        public readonly array $extra = []
    ) {
    }

    public function broadcastOn(): array
    {
        // user.{id}      → the student's app (borrow detail / my books)
        // borrowing.{id} → staff viewing this specific borrowing's inspection
        //                  (same-branch staff are authorized in routes/channels.php)
        return [
            new PrivateChannel('user.' . $this->userId),
            new PrivateChannel('borrowing.' . $this->borrowingId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'borrowing.updated';
    }

    public function broadcastWith(): array
    {
        return array_merge([
            'event' => 'borrowing_updated',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'action' => $this->action,
            'status' => $this->status,
            'payment_type' => $this->paymentType,
            'payment_status' => $this->paymentStatus,
        ], $this->extra);
    }
}
