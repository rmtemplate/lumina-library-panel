<?php

namespace App\Http\Requests\Api\Auth;

use App\Support\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_info' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'device_name.string' => 'Nama device harus berupa teks.',
            'device_name.max' => 'Nama device maksimal 100 karakter.',
            'device_info.string' => 'Info device harus berupa teks.',
            'device_info.max' => 'Info device maksimal 500 karakter.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validation(
                errors: $validator->errors()->toArray(),
                message: 'Validasi login gagal.',
                code: 'AUTH_LOGIN_VALIDATION_ERROR'
            )
        );
    }
}