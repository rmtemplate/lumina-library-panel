<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'banners';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id', 'title', 'subtitle', 'image_path', 'image_is_external', 'action_url',
        'sort_order', 'is_active', 'start_date', 'end_date', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'image_is_external' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) return null;
        return $this->image_is_external ? $this->image_path : asset('storage/'.$this->image_path);
    }
}
