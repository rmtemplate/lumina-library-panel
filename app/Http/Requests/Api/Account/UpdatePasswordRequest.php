<?php

namespace App\Http\Requests\Api\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'  => 'Password lama wajib diisi.',
            'current_password.string'    => 'Password lama tidak valid.',
            'password.required'          => 'Password baru wajib diisi.',
            'password.string'            => 'Password baru tidak valid.',
            'password.min'               => 'Password baru minimal 8 karakter.',
            'password.confirmed'         => 'Konfirmasi password tidak cocok.',
        ];
    }
}
