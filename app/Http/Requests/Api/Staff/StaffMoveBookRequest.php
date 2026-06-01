<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffMoveBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_rack_id' => ['required', 'uuid', Rule::exists('racks', 'id')],
            'reason' => ['required', Rule::in([
                'reorganization', 'new_collection', 'repair', 'capacity_adjustment', 'other',
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_rack_id.required' => 'Rak tujuan wajib dipilih.',
            'target_rack_id.uuid' => 'Format rak tujuan tidak valid.',
            'target_rack_id.exists' => 'Rak tujuan tidak ditemukan.',
            'reason.required' => 'Alasan pemindahan wajib dipilih.',
            'reason.in' => 'Alasan pemindahan tidak valid.',
        ];
    }
}
