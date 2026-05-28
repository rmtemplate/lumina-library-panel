<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fine extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const UNPAID = 'unpaid';
    public const PAID = 'paid';
    public const WAIVED = 'waived';

    protected $table = 'fines';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'branch_id', 'borrowing_item_id', 'fine_amount', 'overdue_days', 'status', 'paid_at', 'paid_amount', 'waived_reason', 'notes', 'created_by', 'updated_by'];
    protected $casts = ['fine_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'paid_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function borrowingItem(): BelongsTo { return $this->belongsTo(BorrowingItem::class, 'borrowing_item_id'); }
    public function payments(): HasMany { return $this->hasMany(FinePayment::class, 'fine_id'); }
}
