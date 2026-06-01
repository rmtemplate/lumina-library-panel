<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingReminderLog extends Model
{
    use HasUuids;

    // Reminder types (configurable — add new ones here without schema changes).
    public const DUE_MINUS_3 = 'due_minus_3';
    public const DUE_MINUS_1 = 'due_minus_1';
    public const DUE_TODAY = 'due_today';
    public const OVERDUE_DAY_1 = 'overdue_day_1';
    public const OVERDUE_DAY_3 = 'overdue_day_3';
    public const OVERDUE_DAY_7 = 'overdue_day_7';

    // Channels
    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_EMAIL = 'email';

    protected $table = 'borrowing_reminder_logs';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['borrowing_id', 'user_id', 'reminder_type', 'channel', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function borrowing(): BelongsTo { return $this->belongsTo(Borrowing::class, 'borrowing_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public static function alreadySent(string $borrowingId, string $reminderType, string $channel): bool
    {
        return self::where('borrowing_id', $borrowingId)
            ->where('reminder_type', $reminderType)
            ->where('channel', $channel)
            ->exists();
    }
}
