<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StaffRegisterBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => ['nullable', 'string', 'max:32'],
            'book_code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:150'],
            'publisher' => ['nullable', 'string', 'max:150'],
            'category_id' => ['required', 'string', 'exists:book_categories,id'],
            'rack_id' => ['nullable', 'string', 'exists:racks,id'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'quantity' => ['required', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_code.required' => 'Book Code wajib diisi.',
            'title.required' => 'Title wajib diisi.',
            'author.required' => 'Author wajib diisi.',
            'category_id.required' => 'Category wajib dipilih.',
            'category_id.exists' => 'Category tidak ditemukan.',
            'rack_id.exists' => 'Shelf/Rack tidak ditemukan.',
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.max' => 'Ukuran cover maksimal 4MB.',
            'quantity.required' => 'Quantity wajib diisi.',
            'quantity.min' => 'Quantity minimal 1.',
            'quantity.max' => 'Quantity maksimal 200 per pendaftaran.',
        ];
    }
}
