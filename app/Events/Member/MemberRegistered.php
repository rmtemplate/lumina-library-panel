<?php

namespace App\Events\Member;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when staff register a new member on the spot (Staff Member
 * Registration flow). Branch-scoped only — the new member has no prior
 * session to receive a `user.{id}` broadcast on, and the only audience
 * that needs to know in realtime is the staff dashboard at that branch.
 */
class MemberRegistered implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $branchId,
        public readonly string $userId,
        public readonly string $memberCode,
        public readonly string $fullname
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('branch.' . $this->branchId)];
    }

    public function broadcastAs(): string
    {
        return 'member.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'member_registered',
            'user_id' => $this->userId,
            'member_code' => $this->memberCode,
            'fullname' => $this->fullname,
        ];
    }
}
