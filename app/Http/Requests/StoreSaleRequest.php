<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
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
        $rules = [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['required', 'string', 'in:cash,utang'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'deduct_from_balance' => ['nullable', 'numeric', 'min:0'],
        ];

        // Conditional validation based on payment type
        if ($this->input('payment_type') === 'utang') {
            $rules['customer_id'] = ['required', 'exists:customers,id'];
        } else {
            $rules['customer_id'] = ['nullable', 'exists:customers,id'];
        }

        // If deducting from balance, customer is required
        if ($this->input('deduct_from_balance') > 0) {
            $rules['customer_id'] = ['required', 'exists:customers,id'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required for utang payments.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'items.required' => 'At least one item is required for the sale.',
            'items.min' => 'At least one item is required for the sale.',
            'items.*.product_id.required' => 'Each item must have a valid product.',
            'items.*.product_id.exists' => 'Selected product does not exist.',
            'items.*.quantity.required' => 'Item quantity is required.',
            'items.*.quantity.min' => 'Item quantity must be greater than 0.',
            'items.*.unit_price.required' => 'Item price is required.',
            'items.*.unit_price.min' => 'Item price must be greater than or equal to 0.',
            'paid_amount.required' => 'Paid amount is required.',
            'paid_amount.min' => 'Paid amount must be greater than or equal to 0.',
            'payment_type.required' => 'Payment type is required.',
            'payment_type.in' => 'Payment type must be either cash or utang.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
