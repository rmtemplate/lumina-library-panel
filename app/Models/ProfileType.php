<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'profile_types';
    public $incrementing = false;
    protected $keyType = 'string';

    public const TK = 'tk';
    public const SD = 'sd';
    public const SMP = 'smp';
    public const SMA_SMK = 'sma_smk';
    public const MAHASISWA = 'mahasiswa';
    public const PEKERJA = 'pekerja';
    public const UMUM = 'umum';

    protected $fillable = [
        'code', 'label', 'description', 'institution_label', 'major_label', 'level_label',
        'show_institution', 'show_major', 'show_level', 'requires_institution', 'requires_major',
        'requires_level', 'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'show_institution' => 'boolean',
        'show_major' => 'boolean',
        'show_level' => 'boolean',
        'requires_institution' => 'boolean',
        'requires_major' => 'boolean',
        'requires_level' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'profile_type_id');
    }
}
