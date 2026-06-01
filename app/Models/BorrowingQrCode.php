<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BorrowingQrCode extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const MINJAM = 'minjam';
    public const KEMBALIKAN = 'kembalikan';
    public const TYPE_BORROW = 'minjam';
    public const TYPE_RETURN = 'kembalikan';
    public const TYPE_BORROWING_PICKUP = 'borrowing_pickup';
    public const TYPE_BORROWING_RETURN = 'borrowing_return';
    public const TYPE_BOOK_INTAKE = 'book_intake';
    public const ACTIVE = 'active';
    public const USED = 'used';
    public const EXPIRED = 'expired';
    public const REVOKED = 'revoked';
    public const REFRESHED = 'refreshed';
    public const IN_REVIEW = 'in_review';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $table = 'borrowing_qr_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'refreshed_from_id',
        'borrowing_id',
        'qr_code',
        'qr_type',
        'status',
        'refresh_count',
        'payload',
        'expires_at',
        'used_at',
        'used_by',
        'last_refreshed_at',
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
        'last_refreshed_at' => 'datetime',
        'refresh_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class, 'borrowing_id');
    }

    public function refreshedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'refreshed_from_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
