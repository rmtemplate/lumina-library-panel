<?php

namespace App\Enums;

enum BorrowingStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Returned = 'returned';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}
