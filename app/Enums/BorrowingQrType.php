<?php

namespace App\Enums;

enum BorrowingQrType: string
{
    case Borrow = 'minjam';
    case Return = 'kembalikan';
}
