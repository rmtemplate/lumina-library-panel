<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StaffCreateBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'string', 'exists:users,id'],
            'book_item_ids' => ['required', 'array', 'min:1'],
            'book_item_ids.*' => ['required', 'string', 'distinct'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Member wajib dipilih sebelum konfirmasi peminjaman.',
            'member_id.exists' => 'Member tidak ditemukan.',
            'book_item_ids.required' => 'Keranjang peminjaman masih kosong.',
            'book_item_ids.min' => 'Tambahkan minimal satu buku ke keranjang.',
        ];
    }
}
