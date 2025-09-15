<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * @bodyParam name string required Kullanıcının tam adı. Example: John Doe
 * @bodyParam first_name string optional Kullanıcının adı. Example: John
 * @bodyParam last_name string optional Kullanıcının soyadı. Example: Doe
 * @bodyParam email string required Kullanıcının e-posta adresi. Example: john@example.com
 * @bodyParam phone string optional Kullanıcının telefon numarası. Example: +905551234567
 * @bodyParam country_code string optional Ülke kodu (2 karakter). Example: TR
 * @bodyParam address string optional Kullanıcının adresi. Example: İstanbul, Türkiye
 * @bodyParam password string required Kullanıcının şifresi (en az 8 karakter). Example: MySecurePassword123!
 * @bodyParam password_confirmation string required Şifre onayı. Example: MySecurePassword123!
 */
class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'address' => ['nullable', 'string', 'max:1000'],
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
            'name.required' => __('response.validation.name_required'),
            'name.string' => __('response.validation.name_string'),
            'name.max' => __('response.validation.name_max'),
            'first_name.string' => __('response.validation.first_name_string'),
            'first_name.max' => __('response.validation.first_name_max'),
            'last_name.string' => __('response.validation.last_name_string'),
            'last_name.max' => __('response.validation.last_name_max'),
            'email.required' => __('response.validation.email_required'),
            'email.email' => __('response.validation.email_email'),
            'email.unique' => __('response.validation.email_unique'),
            'email.max' => __('response.validation.email_max'),
            'phone.unique' => __('response.validation.phone_unique'),
            'phone.max' => __('response.validation.phone_max'),
            'country_code.size' => __('response.validation.country_code_size'),
            'address.max' => __('response.validation.address_max'),
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
            'name' => __('response.attributes.name'),
            'first_name' => __('response.attributes.first_name'),
            'last_name' => __('response.attributes.last_name'),
            'email' => __('response.attributes.email'),
            'phone' => __('response.attributes.phone'),
            'country_code' => __('response.attributes.country_code'),
            'address' => __('response.attributes.address'),
            'password' => __('response.attributes.password'),
        ];
    }
}
