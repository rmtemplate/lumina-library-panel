<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borrowing extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const PENDING = 'pending';
    public const ACTIVE = 'active';
    public const RETURNED = 'returned';
    public const OVERDUE = 'overdue';
    public const CANCELLED = 'cancelled';

    protected $table = 'borrowings';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'borrowing_code', 'user_id', 'branch_id', 'library_branch_id', 'borrow_date',
        'due_date', 'return_date', 'status', 'notes', 'approved_by', 'approved_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'borrow_date' => 'date', 'due_date' => 'date', 'return_date' => 'date',
        'approved_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function libraryBranch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'library_branch_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(BorrowingItem::class, 'borrowing_id'); }
    public function qrCodes(): HasMany { return $this->hasMany(BorrowingQrCode::class, 'borrowing_id'); }
}
