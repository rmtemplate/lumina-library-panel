<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffReturnConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'qr_code' => ['required', 'string', 'max:120'],
            'borrowing_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.borrowing_item_id' => ['required', 'uuid'],
            'items.*.book_item_id' => ['required', 'uuid'],
            'items.*.return_condition' => [
                'required',
                Rule::in(['good', 'minor_damage', 'major_damage', 'damaged', 'lost']),
            ],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'staff_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'qr_code.required' => 'Kode QR wajib dikirim.',
            'borrowing_id.required' => 'Data peminjaman wajib dikirim.',
            'borrowing_id.uuid' => 'Format peminjaman tidak valid.',
            'items.required' => 'Item pengembalian wajib dikirim.',
            'items.array' => 'Format item pengembalian tidak valid.',
            'items.min' => 'Minimal 1 buku harus dikonfirmasi.',
            'items.*.borrowing_item_id.required' => 'Item peminjaman wajib dikirim.',
            'items.*.borrowing_item_id.uuid' => 'Format item peminjaman tidak valid.',
            'items.*.book_item_id.required' => 'Item buku wajib dikirim.',
            'items.*.book_item_id.uuid' => 'Format item buku tidak valid.',
            'items.*.return_condition.required' => 'Kondisi pengembalian wajib dipilih.',
            'items.*.return_condition.in' => 'Kondisi pengembalian tidak valid.',
        ];
    }
}
