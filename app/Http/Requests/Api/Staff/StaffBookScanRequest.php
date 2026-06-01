<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StaffBookScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'qr_raw' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'item_code' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('qr_raw') && ! $this->filled('barcode') && ! $this->filled('item_code')) {
                $validator->errors()->add('book', 'QR, barcode, atau kode item buku wajib dikirim.');
            }
        });
    }
}
