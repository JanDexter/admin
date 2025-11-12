<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^(\+639|09)\d{9}$/',
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
        ];
    }
    
    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be in the format 09XXXXXXXXX or +639XXXXXXXXX',
        ];
    }
}
