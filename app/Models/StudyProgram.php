<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyProgram extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'study_programs';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'description', 'keywords', 'is_active'];
    protected $casts = ['keywords' => 'array', 'is_active' => 'boolean'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BookCategory::class, 'study_program_book_categories', 'study_program_id', 'book_category_id')
            ->withPivot(['weight'])
            ->withTimestamps();
    }
}
