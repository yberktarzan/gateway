<?php

namespace App\Http\Controllers;

use App\Enums\ListingType;
use App\Http\Requests\CreateListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Services\Listing\ListingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @group Listings
 *
 * APIs for managing listings (products, services, etc.)
 */
class ListingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ListingService $listingService
    ) {}

    /**
     * Get all listings
     *
     * Retrieve a list of listings with optional filtering.
     *
     * @queryParam listing_type string Filter by listing type (product_seller, product_buyer, service_giver, service_taker, other). Example: product_seller
     * @queryParam is_active boolean Filter by active status. Example: true
     * @queryParam country_code string Filter by country code. Example: US
     * @queryParam search string Search in title, description, location. Example: laptop
     * @queryParam location string Filter by location. Example: Istanbul
     * @queryParam min_price float Minimum price filter. Example: 100.00
     * @queryParam max_price float Maximum price filter. Example: 1000.00
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam created_after date Filter listings created after date. Example: 2025-01-01
     * @queryParam created_before date Filter listings created before date. Example: 2025-12-31
     * @queryParam per_page integer Items per page (max 50). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listings retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "title": {"en": "Gaming Laptop", "tr": "Oyun Laptopı"},
     *         "description": {"en": "High-end gaming laptop", "tr": "Üst düzey oyun laptopı"},
     *         "cover_image": "listing-cover-1640995200-abc123.jpg",
     *         "cover_image_url": "http://localhost:8000/listings/listing-cover-1640995200-abc123.jpg",
     *         "images": ["listing-gallery-1-1640995200-def456.jpg"],
     *         "image_urls": ["http://localhost:8000/listings/listing-gallery-1-1640995200-def456.jpg"],
     *         "slug": {"en": "gaming-laptop", "tr": "oyun-laptopu"},
     *         "location": "Istanbul, Turkey",
     *         "price": "1500.00",
     *         "formatted_price": "1,500.00 TL",
     *         "listing_type": "product_seller",
     *         "country_code": "TR",
     *         "is_active": true,
     *         "created_at": "2025-09-15T12:00:00.000000Z",
     *         "user": {"id": 1, "name": "John Doe"}
     *       }
     *     ],
     *     "total": 50,
     *     "per_page": 15,
     *     "current_page": 1,
     *     "last_page": 4
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "per_page": ["The per page field must not be greater than 50."]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 15), 50);
            $filters = $request->only([
                'listing_type', 'is_active', 'country_code', 'search',
                'location', 'min_price', 'max_price', 'category_id',
                'created_after', 'created_before',
            ]);

            $result = $this->listingService->getPaginated($filters, $perPage);

            return $this->successResponse(
                data: $result['listings'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Get active listings
     *
     * @queryParam listing_type string Filter by listing type. Example: product_seller
     * @queryParam country_code string Filter by country code. Example: TR
     * @queryParam search string Search in title, description, location. Example: laptop
     * @queryParam per_page integer Items per page (max 50). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Active listings retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "title": {"en": "Gaming Laptop", "tr": "Oyun Laptopı"},
     *         "is_active": true
     *       }
     *     ]
     *   }
     * }
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 15), 50);
            $filters = $request->only([
                'listing_type', 'country_code', 'search',
                'location', 'min_price', 'max_price', 'category_id',
            ]);

            $result = $this->listingService->getActive($filters, $perPage);

            return $this->successResponse(
                data: $result['listings'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Get featured listings only
     *
     * @queryParam country_code string Filter by country code. Example: TR
     * @queryParam search string Search in title, description, location. Example: laptop
     * @queryParam location string Filter by location. Example: Istanbul
     * @queryParam min_price number Minimum price filter. Example: 100
     * @queryParam max_price number Maximum price filter. Example: 5000
     * @queryParam listing_type string Filter by listing type. Example: product_seller
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam per_page integer Items per page (max 50). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Featured listings retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "title": {"en": "Premium Laptop", "tr": "Özel Laptop"},
     *         "is_featured": true,
     *         "is_active": true
     *       }
     *     ]
     *   }
     * }
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 15), 50);
            $filters = $request->only([
                'listing_type', 'country_code', 'search',
                'location', 'min_price', 'max_price', 'category_id',
            ]);

            $result = $this->listingService->getFeatured($filters, $perPage);

            return $this->successResponse(
                data: $result['listings'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Get listings by type
     *
     * @urlParam type string required Listing type (product_seller, product_buyer, service_giver, service_taker, other). Example: product_seller
     *
     * @queryParam country_code string Filter by country code. Example: TR
     * @queryParam search string Search in title, description, location. Example: laptop
     * @queryParam per_page integer Items per page (max 50). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listings by type retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": []
     *   }
     * }
     * @response 422 {
     *   "success": false,
     *   "message": "Invalid listing type"
     * }
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        try {
            $listingType = ListingType::tryFrom($type);

            if (! $listingType) {
                return $this->validationErrorResponse(
                    errors: ['type' => [__('response.listing.invalid_type')]],
                    message: __('response.error.validation')
                );
            }

            $perPage = min((int) $request->get('per_page', 15), 50);
            $filters = $request->only([
                'country_code', 'search', 'location', 'min_price', 'max_price', 'category_id',
            ]);

            $result = $this->listingService->getActive($filters + ['listing_type' => $type], $perPage);

            return $this->successResponse(
                data: $result['listings'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Search listings
     *
     * @queryParam q string required Search query. Example: laptop gaming
     * @queryParam listing_type string Filter by listing type. Example: product_seller
     * @queryParam country_code string Filter by country code. Example: TR
     * @queryParam per_page integer Items per page (max 50). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Search results retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": []
     *   }
     * }
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('q', '');

            if (empty($search)) {
                return $this->validationErrorResponse(
                    errors: ['q' => [__('response.listing.search_query_required')]],
                    message: __('response.error.validation')
                );
            }

            $perPage = min((int) $request->get('per_page', 15), 50);
            $filters = $request->only([
                'listing_type', 'country_code', 'location', 'min_price', 'max_price', 'category_id',
            ]);

            $result = $this->listingService->search($search, $filters, $perPage);

            return $this->successResponse(
                data: $result['listings'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.search_failed')
            );
        }
    }

    /**
     * Get listing statistics
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listing statistics retrieved successfully",
     *   "data": {
     *     "total": 150,
     *     "active": 120,
     *     "inactive": 30,
     *     "by_type": {
     *       "product_seller": 50,
     *       "product_buyer": 30,
     *       "service_giver": 25,
     *       "service_taker": 15,
     *       "other": 10
     *     },
     *     "recent": {
     *       "today": 5,
     *       "this_week": 20,
     *       "this_month": 45
     *     }
     *   }
     * }
     */
    public function statistics(): JsonResponse
    {
        try {
            $result = $this->listingService->getStatistics();

            return $this->successResponse(
                data: $result['statistics'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.statistics_failed')
            );
        }
    }

    /**
     * Create new listing
     *
     * @bodyParam title object required Listing title in multiple languages. Example: {"en": "Gaming Laptop", "tr": "Oyun Laptopı"}
     * @bodyParam title.en string required English title. Example: Gaming Laptop
     * @bodyParam title.tr string Turkish title. Example: Oyun Laptopı
     * @bodyParam description object required Listing description in multiple languages.
     * @bodyParam description.en string required English description. Example: High-end gaming laptop for sale
     * @bodyParam description.tr string Turkish description. Example: Üst düzey oyun laptopı satılık
     * @bodyParam cover_image file Cover image for the listing.
     * @bodyParam images file[] Gallery images for the listing (max 10).
     * @bodyParam slug object Listing slug in multiple languages.
     * @bodyParam slug.en string English slug. Example: gaming-laptop
     * @bodyParam slug.tr string Turkish slug. Example: oyun-laptopu
     * @bodyParam location string required Location of the listing. Example: Istanbul, Turkey
     * @bodyParam price number Listing price. Example: 1500.00
     * @bodyParam listing_type string required Type of listing. Example: product_seller
     * @bodyParam country_code string required Country code. Example: TR
     * @bodyParam category_id integer Category ID (for future use). Example: 1
     * @bodyParam is_active boolean Whether listing is active. Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Listing created successfully",
     *   "data": {
     *     "id": 1,
     *     "title": {"en": "Gaming Laptop", "tr": "Oyun Laptopı"},
     *     "cover_image_url": "http://localhost:8000/listings/listing-cover-1640995200-abc123.jpg",
     *     "user": {"id": 1, "name": "John Doe"}
     *   }
     * }
     */
    public function store(CreateListingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = Auth::user();

            $result = $this->listingService->create($data, $user);

            return $this->createdResponse(
                data: $result['listing'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.create_failed')
            );
        }
    }

    /**
     * Get listing by ID
     *
     * @urlParam id integer required Listing ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listing retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "title": {"en": "Gaming Laptop", "tr": "Oyun Laptopı"},
     *     "description": {"en": "High-end gaming laptop", "tr": "Üst düzey oyun laptopı"},
     *     "cover_image_url": "http://localhost:8000/listings/listing-cover-1640995200-abc123.jpg",
     *     "image_urls": ["http://localhost:8000/listings/listing-gallery-1-1640995200-def456.jpg"],
     *     "user": {"id": 1, "name": "John Doe"}
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Listing not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->listingService->getById($id);

            return $this->successResponse(
                data: $result['listing'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->notFoundResponse(
                message: $e->errors()['listing'][0] ?? __('response.listing.not_found')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Get listing by slug
     *
     * @urlParam slug string required Listing slug. Example: gaming-laptop
     *
     * @queryParam lang string Language for slug (en, tr). Example: en
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listing retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "title": {"en": "Gaming Laptop", "tr": "Oyun Laptopı"}
     *   }
     * }
     */
    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        try {
            $locale = $request->get('lang', app()->getLocale());
            $result = $this->listingService->getBySlug($slug, $locale);

            return $this->successResponse(
                data: $result['listing'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->notFoundResponse(
                message: $e->errors()['listing'][0] ?? __('response.listing.not_found')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Update listing
     *
     * @urlParam id integer required Listing ID. Example: 1
     *
     * @bodyParam title object Listing title in multiple languages.
     * @bodyParam description object Listing description in multiple languages.
     * @bodyParam cover_image file New cover image for the listing.
     * @bodyParam images file[] New gallery images for the listing.
     * @bodyParam location string Location of the listing.
     * @bodyParam price number Listing price.
     * @bodyParam listing_type string Type of listing.
     * @bodyParam is_active boolean Whether listing is active.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listing updated successfully",
     *   "data": {
     *     "id": 1,
     *     "title": {"en": "Updated Gaming Laptop", "tr": "Güncellenmiş Oyun Laptopı"}
     *   }
     * }
     */
    public function update(UpdateListingRequest $request, int $id): JsonResponse
    {
        try {
            $listing = Listing::findOrFail($id);
            $data = $request->validated();
            $user = Auth::user();

            $result = $this->listingService->update($listing, $data, $user);

            return $this->successResponse(
                data: $result['listing'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.update_failed')
            );
        }
    }

    /**
     * Delete listing
     *
     * @urlParam id integer required Listing ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listing deleted successfully"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $listing = Listing::findOrFail($id);
            $user = Auth::user();

            $result = $this->listingService->delete($listing, $user);

            return $this->successResponse(
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.delete_failed')
            );
        }
    }

    /**
     * Get current user's listings
     *
     * @queryParam is_active boolean Filter by active status. Example: true
     * @queryParam listing_type string Filter by listing type. Example: product_seller
     * @queryParam per_page integer Items per page (max 50). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User listings retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": []
     *   }
     * }
     */
    public function myListings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = min((int) $request->get('per_page', 15), 50);
            $filters = $request->only(['is_active', 'listing_type', 'category_id']);

            $result = $this->listingService->getUserListings($user, $filters, $perPage);

            return $this->successResponse(
                data: $result['listings'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.fetch_failed')
            );
        }
    }

    /**
     * Toggle listing active status
     *
     * @urlParam id integer required Listing ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Listing activated successfully",
     *   "data": {
     *     "id": 1,
     *     "is_active": true
     *   }
     * }
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $listing = Listing::findOrFail($id);
            $user = Auth::user();

            $result = $this->listingService->toggleActiveStatus($listing, $user);

            return $this->successResponse(
                data: $result['listing'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.listing.status_update_failed')
            );
        }
    }
}
