<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * @bodyParam current_password string required The user's current password. Example: currentPassword123
 * @bodyParam new_password string required The user's new password. Must be confirmed. Example: newPassword123
 * @bodyParam new_password_confirmation string required Password confirmation. Must match new_password. Example: newPassword123
 */
class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', PasswordRule::defaults()],
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
            'current_password.required' => __('response.validation.current_password_required'),
            'new_password.required' => __('response.validation.new_password_required'),
            'new_password.confirmed' => __('response.validation.new_password_confirmed'),
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
            'current_password' => __('response.attributes.current_password'),
            'new_password' => __('response.attributes.new_password'),
            'new_password_confirmation' => __('response.attributes.new_password_confirmation'),
        ];
    }
}
