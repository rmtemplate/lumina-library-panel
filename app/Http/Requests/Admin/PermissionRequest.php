<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isAdmin() ?? false; }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id;
        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('permissions', 'code')->ignore($permissionId)],
            'name' => ['required', 'string', 'max:150'],
            'module' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
