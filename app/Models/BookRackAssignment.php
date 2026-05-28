<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookRackAssignment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'book_rack_assignments';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'book_id',
        'rack_id',
        'catalog_code',
        'assigned_at',
        'is_active',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


public function book(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Book::class, 'book_id');
}

public function rack(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(Rack::class, 'rack_id');
}
}
