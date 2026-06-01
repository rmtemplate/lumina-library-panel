<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowingPayment extends Model
{
    use HasUuids;

    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const EXPIRED = 'expired';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    protected $table = 'borrowing_payments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'borrowing_id', 'user_id', 'total_amount', 'midtrans_order_id',
        'snap_token', 'payment_url', 'midtrans_transaction_id',
        'payment_type', 'payment_channel', 'va_number', 'qr_string',
        'status', 'paid_at', 'expired_at', 'midtrans_raw', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'midtrans_raw' => 'array',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class, 'borrowing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function isExpiredOrStale(): bool
    {
        if ($this->status !== self::PENDING) {
            return false;
        }

        return $this->expired_at && $this->expired_at->isPast();
    }
}
