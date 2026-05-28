<?php

namespace App\Enums;

enum BorrowingQrStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
