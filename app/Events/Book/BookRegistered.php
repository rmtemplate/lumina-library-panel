<?php

namespace App\Events\Book;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when staff register a new book (+ its physical copies) at their
 * branch. Branch-scoped — every staff dashboard there should reflect the
 * new "Total Books" count without a manual refresh.
 */
class BookRegistered implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $branchId,
        public readonly string $bookId,
        public readonly string $title,
        public readonly int $quantity
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('branch.' . $this->branchId)];
    }

    public function broadcastAs(): string
    {
        return 'book.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'book_registered',
            'book_id' => $this->bookId,
            'title' => $this->title,
            'quantity' => $this->quantity,
        ];
    }
}
