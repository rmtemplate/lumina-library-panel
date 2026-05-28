<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserSession extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'device_info',
        'ip_address',
        'expires_at',
        'last_activity_at',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];
}
