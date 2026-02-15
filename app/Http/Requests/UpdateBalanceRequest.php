<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBalanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'balance' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'balance.required' => 'Balance amount is required.',
            'balance.numeric' => 'Balance must be a valid number.',
            'balance.min' => 'Balance must be at least 0.',
            'note.string' => 'Note must be a valid text.',
            'note.max' => 'Note must not exceed 100 characters.',
        ];
    }
}
