<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('label') && ! $this->filled('code')) {
            $this->merge(['code' => Str::slug($this->input('label'), '_')]);
        }
    }

    public function rules(): array
    {
        $profileTypeId = $this->route('profileType')?->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('profile_types', 'code')->ignore($profileTypeId)],
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'institution_label' => ['required', 'string', 'max:100'],
            'major_label' => ['required', 'string', 'max:100'],
            'level_label' => ['required', 'string', 'max:100'],
            'show_institution' => ['nullable', 'boolean'],
            'show_major' => ['nullable', 'boolean'],
            'show_level' => ['nullable', 'boolean'],
            'requires_institution' => ['nullable', 'boolean'],
            'requires_major' => ['nullable', 'boolean'],
            'requires_level' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
