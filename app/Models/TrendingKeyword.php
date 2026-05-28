<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrendingKeyword extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'trending_keywords';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'keyword',
        'search_count',
        'period_start',
        'period_end',
        'rank_position',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'search_count' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'rank_position' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
