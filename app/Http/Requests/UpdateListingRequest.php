<?php

namespace App\Http\Requests;

use App\Enums\ListingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
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
            'title' => ['sometimes', 'array'],
            'title.en' => ['required_with:title', 'string', 'max:255'],
            'title.tr' => ['nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'array'],
            'description.en' => ['required_with:description', 'string', 'max:10000'],
            'description.tr' => ['nullable', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'slug' => ['sometimes', 'array'],
            'slug.en' => ['required_with:slug', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'slug.tr' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'location' => ['sometimes', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'listing_type' => ['sometimes', 'string', Rule::enum(ListingType::class)],
            'country_code' => ['sometimes', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'], // Admin only field, will be filtered in service layer
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Title validation messages
            'title.array' => __('response.validation.listing_title_array'),
            'title.en.required_with' => __('response.validation.listing_title_en_required'),
            'title.en.string' => __('response.validation.listing_title_en_string'),
            'title.en.max' => __('response.validation.listing_title_en_max'),
            'title.tr.string' => __('response.validation.listing_title_tr_string'),
            'title.tr.max' => __('response.validation.listing_title_tr_max'),

            // Description validation messages
            'description.array' => __('response.validation.listing_description_array'),
            'description.en.required_with' => __('response.validation.listing_description_en_required'),
            'description.en.string' => __('response.validation.listing_description_en_string'),
            'description.en.max' => __('response.validation.listing_description_en_max'),
            'description.tr.string' => __('response.validation.listing_description_tr_string'),
            'description.tr.max' => __('response.validation.listing_description_tr_max'),

            // Cover image validation messages
            'cover_image.file' => __('response.validation.listing_cover_image_file'),
            'cover_image.image' => __('response.validation.listing_cover_image_image'),
            'cover_image.mimes' => __('response.validation.listing_cover_image_mimes'),
            'cover_image.max' => __('response.validation.listing_cover_image_max'),

            // Gallery images validation messages
            'images.array' => __('response.validation.listing_images_array'),
            'images.max' => __('response.validation.listing_images_max'),
            'images.*.file' => __('response.validation.listing_images_file'),
            'images.*.image' => __('response.validation.listing_images_image'),
            'images.*.mimes' => __('response.validation.listing_images_mimes'),
            'images.*.max' => __('response.validation.listing_images_max_size'),

            // Slug validation messages
            'slug.array' => __('response.validation.listing_slug_array'),
            'slug.en.required_with' => __('response.validation.listing_slug_en_required'),
            'slug.en.string' => __('response.validation.listing_slug_en_string'),
            'slug.en.max' => __('response.validation.listing_slug_en_max'),
            'slug.en.regex' => __('response.validation.listing_slug_en_format'),
            'slug.tr.string' => __('response.validation.listing_slug_tr_string'),
            'slug.tr.max' => __('response.validation.listing_slug_tr_max'),
            'slug.tr.regex' => __('response.validation.listing_slug_tr_format'),

            // Location validation messages
            'location.string' => __('response.validation.listing_location_string'),
            'location.max' => __('response.validation.listing_location_max'),

            // Price validation messages
            'price.numeric' => __('response.validation.listing_price_numeric'),
            'price.min' => __('response.validation.listing_price_min'),
            'price.max' => __('response.validation.listing_price_max'),

            // Listing type validation messages
            'listing_type.string' => __('response.validation.listing_type_string'),

            // Country code validation messages
            'country_code.size' => __('response.validation.country_code_size'),
            'country_code.regex' => __('response.validation.country_code_format'),

            // Category validation messages
            'category_id.integer' => __('response.validation.listing_category_integer'),
            'category_id.min' => __('response.validation.listing_category_min'),

            // Active status validation messages
            'is_active.boolean' => __('response.validation.listing_is_active_boolean'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => __('response.attributes.listing_title'),
            'title.en' => __('response.attributes.listing_title_en'),
            'title.tr' => __('response.attributes.listing_title_tr'),
            'description' => __('response.attributes.listing_description'),
            'description.en' => __('response.attributes.listing_description_en'),
            'description.tr' => __('response.attributes.listing_description_tr'),
            'cover_image' => __('response.attributes.listing_cover_image'),
            'images' => __('response.attributes.listing_images'),
            'slug' => __('response.attributes.listing_slug'),
            'slug.en' => __('response.attributes.listing_slug_en'),
            'slug.tr' => __('response.attributes.listing_slug_tr'),
            'location' => __('response.attributes.listing_location'),
            'price' => __('response.attributes.listing_price'),
            'listing_type' => __('response.attributes.listing_type'),
            'country_code' => __('response.attributes.country_code'),
            'category_id' => __('response.attributes.listing_category'),
            'is_active' => __('response.attributes.listing_is_active'),
        ];
    }
}
