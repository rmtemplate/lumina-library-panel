<?php

namespace App\Enums;

enum BorrowingQrType: string
{
    case Borrow = 'minjam';
    case Return = 'kembalikan';
    case BorrowingPickup = 'borrowing_pickup';
    case BorrowingReturn = 'borrowing_return';
    case BookIntake = 'book_intake';
}
