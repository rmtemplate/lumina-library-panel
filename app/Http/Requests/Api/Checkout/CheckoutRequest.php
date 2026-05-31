<?php

namespace App\Http\Requests\Api\Checkout;

use App\Support\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'uuid'],
            'cart_item_ids' => ['nullable', 'array', 'min:1'],
            'cart_item_ids.*' => ['required', 'uuid'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Perpustakaan wajib dipilih.',
            'branch_id.uuid' => 'Format perpustakaan tidak valid.',

            'cart_item_ids.array' => 'Format item checkout tidak valid.',
            'cart_item_ids.min' => 'Minimal pilih 1 buku untuk checkout.',
            'cart_item_ids.*.uuid' => 'Format item cart tidak valid.',

            'start_date.required' => 'Tanggal mulai pinjam wajib diisi.',
            'start_date.date' => 'Format tanggal mulai pinjam tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai pinjam tidak boleh kurang dari hari ini.',

            'end_date.required' => 'Tanggal akhir pinjam wajib diisi.',
            'end_date.date' => 'Format tanggal akhir pinjam tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir pinjam harus sama atau setelah tanggal mulai pinjam.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validation(
                errors: $validator->errors()->toArray(),
                message: 'Validasi checkout gagal.',
                code: 'CHECKOUT_VALIDATION_ERROR'
            )
        );
    }
}