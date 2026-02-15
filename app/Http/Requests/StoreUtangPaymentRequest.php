<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreUtangPaymentRequest extends FormRequest
{
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
                'exists:customers,id,user_id,'.Auth::id(),
            ],
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('customer_id') || $validator->errors()->has('payment_amount')) {
                return;
            }

            $customer = Customer::find($this->customer_id);

            if (! $customer) {
                return;
            }

            $currentBalance = $customer->running_utang_balance ?? 0;
            $paymentAmount = (float) $this->payment_amount;

            if ($paymentAmount > $currentBalance) {
                $validator->errors()->add(
                    'payment_amount',
                    'Payment amount cannot exceed the current balance of ₱'.number_format($currentBalance, 2)
                );
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid or does not belong to you.',
            'payment_amount.required' => 'Please enter a payment amount.',
            'payment_amount.numeric' => 'Payment amount must be a valid number.',
            'payment_amount.min' => 'Payment amount must be at least ₱0.01.',
            'payment_date.required' => 'Please select a payment date.',
            'payment_date.date' => 'Please enter a valid date.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
