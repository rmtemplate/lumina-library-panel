<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinePayment extends Model
{
    use HasFactory, HasUuids;

    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';
    public const CASH = 'cash';
    public const BANK_TRANSFER = 'bank_transfer';
    public const E_WALLET = 'e_wallet';

    protected $table = 'fine_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'fine_id',
        'user_id',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_proof_url',
        'verified_by',
        'verified_at',
        'status',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
