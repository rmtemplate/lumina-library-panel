<?php

namespace App\Models;

use App\Enums\BookItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const AVAILABLE = 'available';
    public const BORROWED = 'borrowed';
    public const LOST = 'lost';
    public const DAMAGED = 'damaged';
    public const MAINTENANCE = 'maintenance';

    protected $table = 'book_items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'book_id', 'branch_id', 'rack_id', 'item_code', 'barcode', 'acquisition_date',
        'source', 'condition_note', 'status', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function book(): BelongsTo { return $this->belongsTo(Book::class, 'book_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function rack(): BelongsTo { return $this->belongsTo(Rack::class, 'rack_id'); }
    public function borrowingItems(): HasMany { return $this->hasMany(BorrowingItem::class, 'book_item_id'); }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->status === BookItemStatus::AVAILABLE->value;
    }
}
