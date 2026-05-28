<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationEvent extends Model
{
    use HasUuids;

    protected $table = 'recommendation_events';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['user_id', 'book_id', 'event_type', 'score_delta', 'metadata', 'created_at'];
    protected $casts = ['score_delta' => 'decimal:2', 'metadata' => 'array', 'created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function book(): BelongsTo { return $this->belongsTo(Book::class); }
}
