<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const USER_AVATAR = 'user_avatar';
    public const BOOK_COVER = 'book_cover';
    public const BANNER_IMAGE = 'banner_image';
    public const LIBRARY_IMAGE = 'library_image';
    public const DOCUMENT = 'document';

    protected $table = 'attachments';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'sort_order',
        'is_primary',
        'uploaded_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
