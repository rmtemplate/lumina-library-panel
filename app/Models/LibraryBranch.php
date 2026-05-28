<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryBranch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'library_branches';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code', 'name', 'address', 'description', 'phone', 'email', 'image_path', 'image_is_external',
        'latitude', 'longitude', 'open_time', 'close_time', 'schedule_description',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'image_is_external' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function users(): HasMany { return $this->hasMany(User::class, 'branch_id'); }
    public function admins(): HasMany { return $this->hasMany(User::class, 'branch_id')->whereHas('roles', fn ($q) => $q->where('code', Role::ADMIN_BRANCH)); }
    public function staff(): HasMany { return $this->hasMany(User::class, 'branch_id')->whereHas('roles', fn ($q) => $q->where('code', Role::STAFF)); }
    public function racks(): HasMany { return $this->hasMany(Rack::class, 'branch_id'); }
    public function bookItems(): HasMany { return $this->hasMany(BookItem::class, 'branch_id'); }
    public function borrowings(): HasMany { return $this->hasMany(Borrowing::class, 'branch_id'); }
    public function fines(): HasMany { return $this->hasMany(Fine::class, 'branch_id'); }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) return null;
        return $this->image_is_external ? $this->image_path : asset('storage/'.$this->image_path);
    }

    // Backward-compatible alias.
    public function stocks(): HasMany { return $this->hasMany(BookStock::class, 'library_branch_id'); }
}
