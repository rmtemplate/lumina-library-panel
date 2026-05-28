<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug') ?: $this->input('code') ?: $this->input('name');
        $this->merge([
            'slug' => Str::slug((string) $slug),
            'code' => Str::slug((string) ($this->input('code') ?: $slug), '_'),
        ]);
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('book_categories', 'code')->ignore($categoryId)],
            'slug' => ['required', 'string', 'max:80', Rule::unique('book_categories', 'slug')->ignore($categoryId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'icon_name' => ['nullable', 'string', 'max:50'],
            'image_file' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'image_file' => 'category icon/image',
        ];
    }
}
