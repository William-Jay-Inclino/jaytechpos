<?php

namespace App\Http\Requests;

use App\Models\Inventory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLowStockThresholdRequest extends FormRequest
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
            'product_id' => 'required|exists:products,id',
            'low_stock_threshold' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $inventory = Inventory::where('product_id', $this->product_id)->first();

                    if (! $inventory) {
                        $fail('Product has no inventory record.');

                        return;
                    }

                    if ($inventory->quantity <= 0) {
                        $fail('Cannot set threshold for product with zero quantity.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID is required.',
            'product_id.exists' => 'Product not found.',
            'low_stock_threshold.required' => 'Low stock threshold is required.',
            'low_stock_threshold.numeric' => 'Low stock threshold must be a number.',
            'low_stock_threshold.min' => 'Low stock threshold must be at least 0.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
