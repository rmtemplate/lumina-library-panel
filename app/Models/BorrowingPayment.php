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

    // What markPaid() should do once this payment is confirmed paid — see
    // BorrowingPaymentService::markPaid() and docs/borrowing-lifecycle.md.
    public const PURPOSE_OVERDUE_FINE = 'overdue_fine';
    public const PURPOSE_RETURN_CONDITION = 'return_condition';
    public const PURPOSE_PICKUP_NO_SHOW = 'pickup_no_show';

    protected $table = 'borrowing_payments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'borrowing_id', 'user_id', 'total_amount', 'midtrans_order_id',
        'snap_token', 'payment_url', 'midtrans_transaction_id',
        'payment_type', 'payment_channel', 'payment_method', 'va_number',
        'qr_string', 'actions', 'status', 'purpose', 'paid_at', 'expired_at',
        'midtrans_raw', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'midtrans_raw' => 'array',
        'actions' => 'array',
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

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, 'borrowing_payment_id');
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
