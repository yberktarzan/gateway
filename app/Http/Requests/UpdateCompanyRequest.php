<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'country_code' => ['sometimes', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'description' => ['sometimes', 'array'],
            'description.en' => ['required_with:description', 'string', 'max:1000'],
            'description.tr' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_vip' => ['boolean'], // Will be filtered in service layer
            'status' => [Rule::in(['pending', 'active', 'inactive'])], // Will be filtered in service layer
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => __('response.validation.company_name_string'),
            'name.max' => __('response.validation.company_name_max'),
            'country_code.size' => __('response.validation.country_code_size'),
            'country_code.regex' => __('response.validation.country_code_format'),
            'description.array' => __('response.validation.description_array'),
            'description.en.required_with' => __('response.validation.description_en_required'),
            'description.en.string' => __('response.validation.description_en_string'),
            'description.en.max' => __('response.validation.description_en_max'),
            'description.tr.string' => __('response.validation.description_tr_string'),
            'description.tr.max' => __('response.validation.description_tr_max'),
            'logo.file' => __('response.validation.logo_file'),
            'logo.image' => __('response.validation.logo_image'),
            'logo.mimes' => __('response.validation.logo_mimes'),
            'logo.max' => __('response.validation.logo_max'),
            'website.url' => __('response.validation.website_url'),
            'website.max' => __('response.validation.website_max'),
            'is_vip.boolean' => __('response.validation.is_vip_boolean'),
            'status.in' => __('response.validation.status_invalid'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('response.attributes.company_name'),
            'country_code' => __('response.attributes.country_code'),
            'description' => __('response.attributes.description'),
            'description.en' => __('response.attributes.description_en'),
            'description.tr' => __('response.attributes.description_tr'),
            'logo' => __('response.attributes.logo'),
            'website' => __('response.attributes.website'),
            'is_vip' => __('response.attributes.is_vip'),
            'status' => __('response.attributes.status'),
        ];
    }
}
