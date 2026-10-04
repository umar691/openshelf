<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['instructor', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:50000'],
            'subject' => ['required', 'string', 'max:100'],
            'difficulty' => ['required', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'cover_image' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
