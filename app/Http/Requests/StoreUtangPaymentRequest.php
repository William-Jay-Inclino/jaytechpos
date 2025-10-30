<?php

namespace App\Http\Requests;

use App\Traits\HandlesTimezone;
use Illuminate\Foundation\Http\FormRequest;

class StoreUtangPaymentRequest extends FormRequest
{
    use HandlesTimezone;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id,user_id,'.auth()->id(),
            ],
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'payment_amount.required' => 'Payment amount is required.',
            'payment_amount.numeric' => 'Payment amount must be a number.',
            'payment_amount.min' => 'Payment amount must be at least ₱0.01.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',
            'notes.max' => 'Notes cannot be more than 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Laravel handles timezone conversion automatically when app timezone is set
        // Just ensure the datetime-local format is converted to proper datetime format
        $this->merge([
            'payment_date' => str_replace('T', ' ', $this->payment_date).':00', // Convert 2025-10-30T11:08 to 2025-10-30 11:08:00
        ]);
    }
}
