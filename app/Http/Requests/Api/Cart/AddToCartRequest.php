<?php

namespace App\Http\Requests\Api\Cart;

use App\Support\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'uuid',
                Rule::exists('books', 'id')->where('is_active', true),
            ],
            'branch_id' => [
                'required',
                'uuid',
                Rule::exists('library_branches', 'id')->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'Buku wajib dipilih.',
            'book_id.uuid' => 'Buku tidak valid.',
            'book_id.exists' => 'Buku tidak ditemukan atau tidak aktif.',

            'branch_id.required' => 'Perpustakaan wajib dipilih.',
            'branch_id.uuid' => 'Perpustakaan tidak valid.',
            'branch_id.exists' => 'Perpustakaan tidak ditemukan atau tidak aktif.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validation(
                errors: $validator->errors()->toArray(),
                message: 'Validasi cart gagal.',
                code: 'CART_VALIDATION_ERROR'
            )
        );
    }
}