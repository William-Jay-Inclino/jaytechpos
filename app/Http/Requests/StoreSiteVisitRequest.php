<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteVisitRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'session_id'    => ['required', 'string', 'max:255'],
            'user_agent'    => ['required', 'string', 'max:255'],
            'referer'       => ['nullable', 'string', 'max:255'],
            'page_url'      => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID is required.',
            'ip_address.required' => 'IP address is required.',
            'user_agent.required' => 'User agent is required.',
            'page_url.required'   => 'Page URL is required.',
        ];
    }
}
