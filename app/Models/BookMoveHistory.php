<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookMoveHistory extends Model
{
    use HasFactory, HasUuids;

    public const REORGANIZATION = 'reorganization';
    public const NEW_COLLECTION = 'new_collection';
    public const REPAIR = 'repair';
    public const CAPACITY_ADJUSTMENT = 'capacity_adjustment';
    public const OTHER = 'other';

    protected $table = 'book_move_histories';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'book_id',
        'book_item_id',
        'source_rack_id',
        'destination_rack_id',
        'reason',
        'notes',
        'moved_by',
        'moved_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function book(): BelongsTo { return $this->belongsTo(Book::class, 'book_id'); }
    public function bookItem(): BelongsTo { return $this->belongsTo(BookItem::class, 'book_item_id'); }
    public function sourceRack(): BelongsTo { return $this->belongsTo(Rack::class, 'source_rack_id'); }
    public function destinationRack(): BelongsTo { return $this->belongsTo(Rack::class, 'destination_rack_id'); }
    public function movedBy(): BelongsTo { return $this->belongsTo(User::class, 'moved_by'); }
}
