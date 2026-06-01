<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffQrScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'qr_raw' => ['required', 'string'],
            'return_condition' => ['nullable', Rule::in(['good', 'minor_damage', 'major_damage', 'damaged', 'lost'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'qr_raw.required' => 'Data QR wajib dikirim.',
            'qr_raw.string' => 'Data QR tidak valid.',
            'return_condition.in' => 'Kondisi pengembalian tidak valid.',
        ];
    }
}
