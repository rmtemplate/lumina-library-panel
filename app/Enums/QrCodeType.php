<?php

namespace App\Enums;

enum QrCodeType: string
{
    case BORROWING_PICKUP = 'borrowing_pickup';
    case BORROWING_RETURN = 'borrowing_return';
    case BOOK_INTAKE = 'book_intake';

    public function label(): string
    {
        return match ($this) {
            self::BORROWING_PICKUP => 'Borrowing Pickup',
            self::BORROWING_RETURN => 'Borrowing Return',
            self::BOOK_INTAKE => 'Book Intake',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}