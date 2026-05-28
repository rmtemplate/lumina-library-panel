<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cart_items';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'cart_id',
        'book_id',
        'library_branch_id',
        'is_selected',
        'added_at',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
        'added_at' => 'datetime',
    ];
}
