<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddBookToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'format' => ['required', Rule::in(['pdf', 'epub', 'audio', 'print'])],
            'fulfillment' => ['required', Rule::in(['digital', 'physical'])],
        ];
    }
}
