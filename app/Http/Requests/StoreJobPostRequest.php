<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['employer', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:50000'],
            'requirements' => ['required', 'array', 'min:1', 'max:30'],
            'requirements.*' => ['required', 'string', 'max:500'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'internship'])],
            'location_type' => ['required', Rule::in(['remote', 'hybrid', 'onsite'])],
            'location' => ['nullable', 'string', 'max:180'],
            'salary_min_minor' => ['nullable', 'integer', 'min:0'],
            'salary_max_minor' => ['nullable', 'integer', 'gte:salary_min_minor'],
            'salary_currency' => ['required', 'string', 'size:3'],
            'closes_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
