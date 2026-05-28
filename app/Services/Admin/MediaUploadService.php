<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;

class MediaUploadService
{
    public function store(?UploadedFile $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store($directory, 'public');
    }
}
