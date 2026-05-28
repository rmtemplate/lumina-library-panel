<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserWishlist extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_wishlists';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_id',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];
}
