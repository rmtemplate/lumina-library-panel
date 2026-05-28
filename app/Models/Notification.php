<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory, HasUuids;

    public const BORROWING_REMINDER = 'borrowing_reminder';
    public const OVERDUE_ALERT = 'overdue_alert';
    public const RETURN_CONFIRMATION = 'return_confirmation';
    public const FINE_NOTICE = 'fine_notice';
    public const SYSTEM_ANNOUNCEMENT = 'system_announcement';
    public const GENERAL = 'general';

    protected $table = 'notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'reference_type',
        'reference_id',
        'is_read',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
