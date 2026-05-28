<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookReview extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'book_reviews';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'review_text',
        'is_verified_borrower',
        'is_visible',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_borrower' => 'boolean',
        'is_visible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
