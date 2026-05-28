<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->canAccessAdminPanel() ?? false; }

    public function rules(): array
    {
        $itemId = $this->route('book_item')?->id;
        return [
            'book_id' => ['required', 'uuid', 'exists:books,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:library_branches,id'],
            'rack_id' => ['nullable', 'uuid', 'exists:racks,id'],
            'item_code' => ['required', 'string', 'max:80', Rule::unique('book_items', 'item_code')->ignore($itemId)],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('book_items', 'barcode')->ignore($itemId)],
            'acquisition_date' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:80'],
            'condition_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(BookItemStatus::values())],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
