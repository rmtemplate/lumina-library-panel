<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BorrowingRenewal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'borrowing_renewals';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'borrowing_item_id',
        'previous_due_date',
        'new_due_date',
        'renewal_count',
        'renewed_at',
        'renewed_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'previous_due_date' => 'date',
        'new_due_date' => 'date',
        'renewal_count' => 'integer',
        'renewed_at' => 'datetime',
    ];
}
