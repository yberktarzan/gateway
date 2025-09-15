<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * @bodyParam token string required The password reset token. Example: abcdef123456
 * @bodyParam email string required The user's email address. Example: john@example.com
 * @bodyParam password string required The user's new password. Must be confirmed. Example: newPassword123
 * @bodyParam password_confirmation string required Password confirmation. Must match password. Example: newPassword123
 */
class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
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
            'token.required' => __('response.validation.token_required'),
            'email.required' => __('response.validation.email_required'),
            'email.email' => __('response.validation.email_email'),
            'password.required' => __('response.validation.password_required'),
            'password.confirmed' => __('response.validation.password_confirmed'),
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
            'token' => __('response.attributes.token'),
            'email' => __('response.attributes.email'),
            'password' => __('response.attributes.password'),
        ];
    }
}
