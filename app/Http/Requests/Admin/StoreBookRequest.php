<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canAccessAdminPanel() ?? false; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'author' => ['required', 'string', 'max:300'],
            'isbn' => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'publisher' => ['nullable', 'string', 'max:200'],
            'publish_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'category_id' => ['required', 'uuid', 'exists:book_categories,id'],
            'language' => ['nullable', 'string', 'max:50'],
            'page_count' => ['nullable', 'integer', 'min:1'],
            'edition' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
