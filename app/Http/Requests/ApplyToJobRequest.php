<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyToJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['freelancer', 'student', 'job_seeker']) ?? false;
    }

    public function rules(): array
    {
        return [
            'cover_letter' => ['required', 'string', 'max:10000'],
            'resume' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
