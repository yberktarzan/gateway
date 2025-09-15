<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @bodyParam name string The user's display name. Example: John Doe
 * @bodyParam first_name string The user's first name. Example: John
 * @bodyParam last_name string The user's last name. Example: Doe
 * @bodyParam email string The user's email address. Must be unique. Example: john@example.com
 * @bodyParam phone string The user's phone number. Must be unique if provided. Example: +1234567890
 * @bodyParam country_code string The user's country code (2 characters). Example: US
 * @bodyParam address string The user's address. Example: 123 Main St, City, State
 */
class UpdateProfileRequest extends FormRequest
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
        $userId = Auth::id();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('users')->ignore($userId)],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => __('response.validation.name_string'),
            'name.max' => __('response.validation.name_max'),
            'first_name.string' => __('response.validation.first_name_string'),
            'first_name.max' => __('response.validation.first_name_max'),
            'last_name.string' => __('response.validation.last_name_string'),
            'last_name.max' => __('response.validation.last_name_max'),
            'email.email' => __('response.validation.email_email'),
            'email.unique' => __('response.validation.email_unique'),
            'email.max' => __('response.validation.email_max'),
            'phone.unique' => __('response.validation.phone_unique'),
            'phone.max' => __('response.validation.phone_max'),
            'country_code.size' => __('response.validation.country_code_size'),
            'address.max' => __('response.validation.address_max'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('response.attributes.name'),
            'first_name' => __('response.attributes.first_name'),
            'last_name' => __('response.attributes.last_name'),
            'email' => __('response.attributes.email'),
            'phone' => __('response.attributes.phone'),
            'country_code' => __('response.attributes.country_code'),
            'address' => __('response.attributes.address'),
        ];
    }
}
