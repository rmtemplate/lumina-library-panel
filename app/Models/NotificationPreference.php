<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationPreference extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notification_preferences';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'borrowing_reminder',
        'overdue_alert',
        'return_confirmation',
        'fine_notice',
        'reservation_available',
        'system_announcement',
        'push_enabled',
        'email_enabled',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'borrowing_reminder' => 'boolean',
        'overdue_alert' => 'boolean',
        'return_confirmation' => 'boolean',
        'fine_notice' => 'boolean',
        'reservation_available' => 'boolean',
        'system_announcement' => 'boolean',
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
