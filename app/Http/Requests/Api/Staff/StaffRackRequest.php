<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StaffRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rack_code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'section_name' => ['nullable', 'string', 'max:100'],
            'floor_label' => ['nullable', 'string', 'max:100'],
            'area_label' => ['nullable', 'string', 'max:100'],
            'aisle_code' => ['nullable', 'string', 'max:50'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_basement' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rack_code.required' => 'Kode rak wajib diisi.',
            'name.required' => 'Nama rak wajib diisi.',
            'capacity.integer' => 'Kapasitas harus berupa angka.',
            'capacity.min' => 'Kapasitas minimal 1.',
        ];
    }
}
