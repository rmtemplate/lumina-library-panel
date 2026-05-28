<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canAccessAdminPanel() ?? false; }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        return [
            'branch_id' => ['nullable', 'uuid', 'exists:library_branches,id'],
            'fullname' => ['required', 'string', 'min:3', 'max:200'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'min:8', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'member_code' => ['required', 'string', 'max:50', Rule::unique('users', 'member_code')->ignore($userId)],
            'profile_type_id' => ['required', 'uuid', 'exists:profile_types,id'],
            'role_ids' => ['nullable', 'array', 'min:1'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'institution_name' => ['nullable', 'string', 'max:200'],
            'major_name' => ['nullable', 'string', 'max:200'],
            'current_level' => ['nullable', 'string', 'max:100'],
            'staff_position' => ['nullable', 'string', 'max:100'],
            'is_verified' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
