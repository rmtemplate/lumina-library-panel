<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookReservation extends Model
{
    use HasFactory, HasUuids;

    public const WAITING = 'waiting';
    public const AVAILABLE = 'available';
    public const FULFILLED = 'fulfilled';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    protected $table = 'book_reservations';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'book_id',
        'library_branch_id',
        'queue_position',
        'status',
        'reserved_at',
        'available_at',
        'expires_at',
        'fulfilled_at',
        'cancelled_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'queue_position' => 'integer',
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
