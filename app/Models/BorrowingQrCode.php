<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BorrowingQrCode extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const MINJAM = 'minjam';
    public const KEMBALIKAN = 'kembalikan';
    public const TYPE_BORROW = 'minjam';
    public const TYPE_RETURN = 'kembalikan';
    public const ACTIVE = 'active';
    public const USED = 'used';
    public const EXPIRED = 'expired';
    public const REVOKED = 'revoked';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $table = 'borrowing_qr_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'borrowing_id',
        'qr_code',
        'qr_type',
        'status',
        'payload',
        'expires_at',
        'used_at',
        'used_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
