<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RackRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canAccessAdminPanel() ?? false; }
    public function rules(): array
    {
        $rackId = $this->route('rack')?->id;
        return [
            'branch_id' => ['required', 'uuid', 'exists:library_branches,id'],
            'rack_code' => ['required', 'string', 'max:50', Rule::unique('racks', 'rack_code')->where('branch_id', $this->input('branch_id'))->ignore($rackId)],
            'name' => ['required', 'string', 'max:150'],
            'section_name' => ['nullable', 'string', 'max:100'],
            'floor_label' => ['nullable', 'string', 'max:50'],
            'area_label' => ['nullable', 'string', 'max:100'],
            'aisle_code' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'is_basement' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
