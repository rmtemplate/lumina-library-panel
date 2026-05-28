<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rack extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'racks';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id', 'library_branch_id', 'rack_code', 'name', 'section_name', 'floor_label',
        'area_label', 'aisle_code', 'capacity', 'is_basement', 'notes', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_basement' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function libraryBranch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'library_branch_id'); }
    public function items(): HasMany { return $this->hasMany(BookItem::class, 'rack_id'); }
    public function bookAssignments(): HasMany { return $this->hasMany(BookRackAssignment::class, 'rack_id'); }
}
