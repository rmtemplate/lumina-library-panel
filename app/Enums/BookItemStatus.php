<?php

namespace App\Enums;

enum BookItemStatus: string
{
    case AVAILABLE = 'available';
    case BORROWED = 'borrowed';
    case LOST = 'lost';
    case DAMAGED = 'damaged';
    case MAINTENANCE = 'maintenance';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
