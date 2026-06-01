<?php

namespace App\Http\Requests\Api\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // 2 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Foto profil wajib diunggah.',
            'avatar.image'    => 'Foto profil harus berupa gambar.',
            'avatar.mimes'    => 'Format foto profil harus jpg, jpeg, png, atau webp.',
            'avatar.max'      => 'Ukuran foto profil maksimal 2 MB.',
        ];
    }
}
