<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['author', 'vendor', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:50000'],
            'cover_image' => ['nullable', 'url', 'max:2048'],
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => ['required', Rule::in(['pdf', 'epub', 'audio', 'print'])],
            'price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'isbn' => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'category' => ['required', 'string', 'max:100'],
            'digital_available' => ['required', 'boolean'],
            'physical_available' => ['required', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
