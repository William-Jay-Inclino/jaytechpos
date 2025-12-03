<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteActivityLogsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select a start date.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.required' => 'Please select an end date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
