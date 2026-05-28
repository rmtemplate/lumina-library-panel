<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isSuperAdmin() ?? false; }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('roles', 'code')->ignore($roleId)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'scope' => ['nullable', 'string', 'in:global,branch'],
            'is_admin_role' => ['nullable', 'boolean'],
            'is_staff_role' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'permission_ids' => 'permissions',
            'permission_ids.*' => 'permission',
        ];
    }
}
