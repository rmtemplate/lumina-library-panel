<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBookPreference extends Model
{
    use HasUuids;

    protected $table = 'user_book_preferences';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'book_category_id', 'weight', 'source'];
    protected $casts = ['weight' => 'decimal:2'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(BookCategory::class, 'book_category_id'); }
}
