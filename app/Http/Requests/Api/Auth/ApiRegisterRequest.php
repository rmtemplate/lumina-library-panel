<?php

namespace App\Http\Requests\Api\Auth;

use App\Models\ProfileType;
use App\Support\Responses\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ApiRegisterRequest extends FormRequest
{
    private ?ProfileType $resolvedProfileType = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'min:3', 'max:200'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],

            'phone' => ['nullable', 'string', 'min:8', 'max:20'],

            'profile_type_id' => [
                'required',
                'uuid',
                Rule::exists('profile_types', 'id')->where('is_active', true),
            ],

            'institution_name' => ['nullable', 'string', 'max:200'],
            'major_name' => ['nullable', 'string', 'max:200'],
            'current_level' => ['nullable', 'string', 'max:100'],

            'password' => ['required', 'string', 'min:6', 'confirmed'],

            'agreement' => ['accepted'],

            'device_name' => ['nullable', 'string', 'max:100'],
            'device_info' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'fullname.min' => 'Nama lengkap minimal 3 karakter.',
            'fullname.max' => 'Nama lengkap maksimal 200 karakter.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',

            'phone.min' => 'Nomor telepon minimal 8 digit.',
            'phone.max' => 'Nomor telepon maksimal 20 karakter.',

            'profile_type_id.required' => 'Profile type wajib dipilih.',
            'profile_type_id.uuid' => 'Profile type tidak valid.',
            'profile_type_id.exists' => 'Profile type tidak ditemukan atau tidak aktif.',

            'institution_name.max' => 'Nama instansi maksimal 200 karakter.',
            'major_name.max' => 'Jurusan/program maksimal 200 karakter.',
            'current_level.max' => 'Tingkat/status maksimal 100 karakter.',

            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',

            'agreement.accepted' => 'Kamu wajib menyetujui ketentuan pendaftaran.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator) {
            $profileType = $this->getResolvedProfileType();

            if (! $profileType) {
                return;
            }

            if ($profileType->requires_institution && blank($this->input('institution_name'))) {
                $validator->errors()->add(
                    'institution_name',
                    "{$profileType->institution_label} wajib diisi."
                );
            }

            if ($profileType->requires_major && blank($this->input('major_name'))) {
                $validator->errors()->add(
                    'major_name',
                    "{$profileType->major_label} wajib diisi."
                );
            }

            if ($profileType->requires_level && blank($this->input('current_level'))) {
                $validator->errors()->add(
                    'current_level',
                    "{$profileType->level_label} wajib diisi."
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validation(
                errors: $validator->errors()->toArray(),
                message: 'Validasi register gagal.',
                code: 'AUTH_REGISTER_VALIDATION_ERROR'
            )
        );
    }

    private function getResolvedProfileType(): ?ProfileType
    {
        if ($this->resolvedProfileType) {
            return $this->resolvedProfileType;
        }

        $profileTypeId = $this->input('profile_type_id');

        if (! $profileTypeId) {
            return null;
        }

        $this->resolvedProfileType = ProfileType::query()
            ->where('id', $profileTypeId)
            ->where('is_active', true)
            ->first();

        return $this->resolvedProfileType;
    }
}