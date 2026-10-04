<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'billing_address' => ['nullable', 'array'],
            'billing_address.name' => ['required_with:billing_address', 'string', 'max:120'],
            'billing_address.line1' => ['required_with:billing_address', 'string', 'max:180'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'size:2'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.name' => ['required_with:shipping_address', 'string', 'max:120'],
            'shipping_address.line1' => ['required_with:shipping_address', 'string', 'max:180'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.country' => ['required_with:shipping_address', 'string', 'size:2'],
        ];
    }
}
