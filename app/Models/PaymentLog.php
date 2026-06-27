<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pure audit trail — see migration 2026_06_27_190100_create_payment_logs_table.
 * Never read from for business logic; borrowing_payments is the source of truth.
 */
class PaymentLog extends Model
{
    use HasUuids;

    public const SOURCE_WEBHOOK = 'webhook';
    public const SOURCE_SYNC = 'sync';
    public const SOURCE_CHARGE = 'charge';
    public const SOURCE_CANCEL = 'cancel';

    protected $table = 'payment_logs';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'borrowing_payment_id', 'source', 'transaction_status', 'raw_payload', 'created_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(BorrowingPayment::class, 'borrowing_payment_id');
    }
}
