<?php

namespace App\Http\Requests\Api\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname'         => ['sometimes', 'string', 'min:2', 'max:100'],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'institution_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'major_name'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'current_level'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'staff_position'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'address'          => ['sometimes', 'nullable', 'string', 'max:500'],
            'date_of_birth'    => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender'           => ['sometimes', 'nullable', 'string', 'in:male,female,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.string'        => 'Nama lengkap harus berupa teks.',
            'fullname.min'           => 'Nama lengkap minimal 2 karakter.',
            'fullname.max'           => 'Nama lengkap maksimal 100 karakter.',
            'phone.regex'            => 'Nomor telepon tidak valid.',
            'phone.max'              => 'Nomor telepon maksimal 20 karakter.',
            'institution_name.max'   => 'Nama institusi maksimal 150 karakter.',
            'major_name.max'         => 'Nama jurusan maksimal 100 karakter.',
            'current_level.max'      => 'Tingkat/semester maksimal 50 karakter.',
            'staff_position.max'     => 'Jabatan staff maksimal 100 karakter.',
            'address.max'            => 'Alamat maksimal 500 karakter.',
            'date_of_birth.date'     => 'Tanggal lahir tidak valid.',
            'date_of_birth.before'   => 'Tanggal lahir harus sebelum hari ini.',
            'gender.in'              => 'Jenis kelamin tidak valid.',
        ];
    }
}
