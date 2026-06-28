<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UniversalScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'raw.required' => 'Data QR/barcode wajib diisi.',
        ];
    }
}
