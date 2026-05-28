<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppSetting extends Model
{
    use HasFactory, HasUuids;

    public const STRING = 'string';
    public const INT = 'int';
    public const DECIMAL = 'decimal';
    public const BOOLEAN = 'tinyint(1)';
    public const JSON = 'json';

    protected $table = 'app_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'data_type',
        'description',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
