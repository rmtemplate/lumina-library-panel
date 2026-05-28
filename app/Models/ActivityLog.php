<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    public const BORROW = 'borrow';
    public const RETURN = 'return';
    public const OVERDUE = 'overdue';
    public const REGISTER = 'register';
    public const FINE = 'fine';
    public const SCAN = 'scan';
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';

    protected $table = 'activity_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'user_id',
        'activity_type',
        'title',
        'subtitle',
        'reference_type',
        'reference_id',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
