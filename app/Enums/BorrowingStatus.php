<?php

namespace App\Enums;

enum BorrowingStatus: string
{
    case Pending = 'pending';
    case AwaitingPickup = 'awaiting_pickup';
    case Active = 'active';
    case Returned = 'returned';
    case ReturnedWithFine = 'returned_with_fine';
    case WaitingPayment = 'waiting_payment';
    case Overdue = 'overdue';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
