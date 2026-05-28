<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookStock extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'book_stocks';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'book_id',
        'library_branch_id',
        'shelf_location',
        'total_stock',
        'available_stock',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_stock' => 'integer',
        'available_stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


public function book(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Book::class, 'book_id');
}

public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(LibraryBranch::class, 'library_branch_id');
}
}
