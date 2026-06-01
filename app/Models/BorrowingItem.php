<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BorrowingItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const PENDING = 'pending';
    public const ACTIVE = 'active';
    public const RETURNED = 'returned';
    public const OVERDUE = 'overdue';
    public const LOST = 'lost';
    public const DAMAGED = 'damaged';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    protected $table = 'borrowing_items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['borrowing_id', 'book_id', 'book_item_id', 'status', 'return_date', 'return_condition', 'notes'];
    protected $casts = ['return_date' => 'date', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function borrowing(): BelongsTo { return $this->belongsTo(Borrowing::class, 'borrowing_id'); }
    public function book(): BelongsTo { return $this->belongsTo(Book::class, 'book_id'); }
    public function bookItem(): BelongsTo { return $this->belongsTo(BookItem::class, 'book_item_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(BorrowingStatusHistory::class, 'borrowing_item_id'); }
    public function fines(): HasMany { return $this->hasMany(Fine::class, 'borrowing_item_id'); }
}
