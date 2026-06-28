<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StaffRegisterMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'string', 'max:50', 'unique:users,member_code'],
            'fullname' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'faculty' => ['required', 'string', 'max:150'],
            'study_program' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Student ID (NIM) wajib diisi.',
            'student_id.unique' => 'Student ID (NIM) sudah terdaftar.',
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon tidak valid.',
            'faculty.required' => 'Faculty wajib diisi.',
            'study_program.required' => 'Study Program wajib diisi.',
            'gender.required' => 'Gender wajib diisi.',
            'gender.in' => 'Gender tidak valid.',
            'photo.image' => 'Foto profil harus berupa gambar.',
            'photo.max' => 'Ukuran foto profil maksimal 2MB.',
        ];
    }
}
