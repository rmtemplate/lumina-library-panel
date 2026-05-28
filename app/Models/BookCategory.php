<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BookCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'book_categories';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'slug',
        'name',
        'description',
        'icon_name',
        'image_path',
        'image_is_external',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'image_is_external' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'category_id');
    }

    public function studyPrograms(): BelongsToMany
    {
        return $this->belongsToMany(StudyProgram::class, 'study_program_book_categories', 'book_category_id', 'study_program_id')
            ->withPivot(['weight'])
            ->withTimestamps();
    }

    public function userPreferences(): HasMany
    {
        return $this->hasMany(UserBookPreference::class, 'book_category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return $this->image_is_external ? $this->image_path : Storage::url($this->image_path);
    }
}
