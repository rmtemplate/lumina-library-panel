<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BorrowingStatusHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'borrowing_status_histories';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'borrowing_item_id',
        'from_status',
        'to_status',
        'changed_at',
        'changed_by',
        'notes',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
