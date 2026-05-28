<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'books';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'title', 'author', 'isbn', 'publisher', 'publish_year', 'description',
        'cover_path', 'cover_is_external', 'category_id', 'language', 'page_count',
        'edition', 'rating', 'rating_count', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'publish_year' => 'integer',
        'page_count' => 'integer',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'cover_is_external' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function category(): BelongsTo { return $this->belongsTo(BookCategory::class, 'category_id'); }
    public function items(): HasMany { return $this->hasMany(BookItem::class, 'book_id'); }
    public function activeItems(): HasMany { return $this->hasMany(BookItem::class, 'book_id')->where('is_active', true); }
    public function borrowingItems(): HasMany { return $this->hasMany(BorrowingItem::class, 'book_id'); }
    public function reviews(): HasMany { return $this->hasMany(BookReview::class, 'book_id'); }
    public function views(): HasMany { return $this->hasMany(BookView::class, 'book_id'); }
    public function reservations(): HasMany { return $this->hasMany(BookReservation::class, 'book_id'); }

    // Backward-compatible relation for older reports; operational stock is now book_items.
    public function stocks(): HasMany { return $this->hasMany(BookStock::class, 'book_id'); }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover_path) return null;
        return $this->cover_is_external ? $this->cover_path : asset('storage/'.$this->cover_path);
    }
}
