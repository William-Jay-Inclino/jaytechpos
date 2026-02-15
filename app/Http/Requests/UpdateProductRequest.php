<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $userId = $this->user()?->id ?? Auth::id();

        // Determine the current product id from route (can be model instance or id)
        $productParam = $this->route('product') ?? $this->route('id');
        if ($productParam instanceof \App\Models\Product) {
            $productId = $productParam->id;
        } else {
            $productId = $productParam;
        }

        return [
            'product_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_name')
                    ->ignore($productId)
                    ->where(function ($query) use ($userId) {
                        return $query->where('user_id', $userId);
                    }),
            ],
            'description' => 'nullable|string',
            'unit_id' => 'required|exists:units,id',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'product_name.required' => 'Product name is required.',
            'product_name.unique' => 'A product with this name already exists.',
            'unit_id.required' => 'Please select a unit.',
            'unit_id.exists' => 'The selected unit is invalid.',
            'unit_price.required' => 'Unit price is required.',
            'unit_price.numeric' => 'Unit price must be a valid number.',
            'unit_price.min' => 'Unit price must be at least 0.',
            'cost_price.required' => 'Cost price is required.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'cost_price.min' => 'Cost price must be at least 0.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
