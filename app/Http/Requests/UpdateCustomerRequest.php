<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && $this->route('customer')->user_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'name.max' => 'Customer name must not exceed 255 characters.',
            'mobile_number.max' => 'Mobile number must not exceed 20 characters.',
            'remarks.max' => 'Remarks must not exceed 1000 characters.',
            'interest_rate.numeric' => 'Interest rate must be a valid number.',
            'interest_rate.min' => 'Interest rate must be at least 0%.',
            'interest_rate.max' => 'Interest rate must not exceed 100%.',
        ];
    }
}
