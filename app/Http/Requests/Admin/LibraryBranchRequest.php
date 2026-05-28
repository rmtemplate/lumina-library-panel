<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryBranchRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canAccessAdminPanel() ?? false; }

    public function rules(): array
    {
        $branchId = $this->route('branch')?->id;
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('library_branches', 'code')->ignore($branchId)],
            'name' => ['required', 'string', 'max:200'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'image_file' => [$branchId ? 'nullable' : 'nullable', 'image', 'max:2048'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'schedule_description' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
