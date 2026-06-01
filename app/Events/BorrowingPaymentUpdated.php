<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BorrowingPaymentUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $borrowingId,
        public readonly string $userId,
        public readonly array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('borrowing.' . $this->borrowingId),
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'borrowing.payment-updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
