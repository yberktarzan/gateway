<?php

namespace App\Services\Listing;

use App\Models\Listing;
use App\Models\User;
use App\Repositories\ListingRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Listing Service
 *
 * Handles business logic for listing operations.
 * Acts as a bridge between controllers and repositories.
 */
class ListingService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ImageService $imageService
    ) {}

    /**
     * Create a new listing.
     *
     * @param  array<string, mixed>  $data  Listing data
     * @param  User  $user  Owner user
     * @return array<string, mixed> Result with listing data
     *
     * @throws ValidationException
     */
    public function create(array $data, User $user): array
    {
        // Add user_id to data
        $data['user_id'] = $user->id;

        // Set default status if not provided
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Only admins can set featured status directly
        if (isset($data['is_featured']) && $data['is_featured'] === true) {
            // TODO: Check if user is admin when role system is implemented
            // For now, regular users cannot set featured status
            $data['is_featured'] = false;
        } else {
            // Default to non-featured
            $data['is_featured'] = false;
        }

        // Generate slug from title if not provided
        if (! isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateSlugFromTitle($data['title']);
        }

        // Handle cover image upload if provided
        if (isset($data['cover_image']) && $data['cover_image'] !== null) {
            $coverImageFilename = $this->imageService->uploadCoverImage($data['cover_image']);
            $data['cover_image'] = $coverImageFilename;
        } else {
            unset($data['cover_image']); // Remove null/empty cover image from data
        }

        // Handle gallery images upload if provided
        if (isset($data['images']) && is_array($data['images']) && ! empty($data['images'])) {
            $galleryImageFilenames = $this->imageService->uploadGalleryImages($data['images']);
            $data['images'] = $galleryImageFilenames;
        } else {
            $data['images'] = null; // Set to null if no images provided
        }

        $listing = $this->listingRepository->create($data);

        return [
            'listing' => $listing->load('user'),
            'message' => __('response.listing.created'),
        ];
    }

    /**
     * Get listing by ID.
     *
     * @param  int  $id  Listing ID
     * @return array<string, mixed> Result with listing data
     *
     * @throws ValidationException
     */
    public function getById(int $id): array
    {
        $listing = $this->listingRepository->findById($id);

        if (! $listing) {
            throw ValidationException::withMessages([
                'listing' => [__('response.listing.not_found')],
            ]);
        }

        return [
            'listing' => $listing,
            'message' => __('response.listing.retrieved'),
        ];
    }

    /**
     * Get listing by slug.
     *
     * @param  string  $slug  Listing slug
     * @param  string|null  $locale  Language locale
     * @return array<string, mixed> Result with listing data
     *
     * @throws ValidationException
     */
    public function getBySlug(string $slug, ?string $locale = null): array
    {
        $listing = $this->listingRepository->findBySlug($slug, $locale);

        if (! $listing) {
            throw ValidationException::withMessages([
                'listing' => [__('response.listing.not_found')],
            ]);
        }

        return [
            'listing' => $listing,
            'message' => __('response.listing.retrieved'),
        ];
    }

    /**
     * Update listing.
     *
     * @param  Listing  $listing  Listing to update
     * @param  array<string, mixed>  $data  Update data
     * @param  User  $user  Requesting user
     * @return array<string, mixed> Result with updated listing
     *
     * @throws ValidationException
     */
    public function update(Listing $listing, array $data, User $user): array
    {
        // Check if user owns the listing
        if ($listing->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'listing' => [__('response.listing.unauthorized')],
            ]);
        }

        // Regular users cannot change featured status
        if (isset($data['is_featured'])) {
            // TODO: Check if user is admin when role system is implemented
            // For now, remove is_featured from data for regular users
            unset($data['is_featured']);
        }

        // Update slug if title is being updated
        if (isset($data['title']) && (! isset($data['slug']) || empty($data['slug']))) {
            $data['slug'] = $this->generateSlugFromTitle($data['title']);
        }

        // Handle cover image upload if provided
        if (isset($data['cover_image']) && $data['cover_image'] !== null) {
            // Delete old cover image if it exists
            if ($listing->cover_image) {
                $listing->deleteCoverImage();
            }

            // Upload new cover image
            $coverImageFilename = $this->imageService->uploadCoverImage($data['cover_image']);
            $data['cover_image'] = $coverImageFilename;
        } elseif (array_key_exists('cover_image', $data) && $data['cover_image'] === null) {
            // If cover image is explicitly set to null, delete existing cover image
            if ($listing->cover_image) {
                $listing->deleteCoverImage();
            }
            $data['cover_image'] = null;
        } else {
            // Remove cover image from data if not provided (don't change existing cover image)
            unset($data['cover_image']);
        }

        // Handle gallery images upload if provided
        if (isset($data['images']) && is_array($data['images']) && ! empty($data['images'])) {
            // Delete old gallery images if they exist
            if ($listing->images) {
                $listing->deleteGalleryImages();
            }

            // Upload new gallery images
            $galleryImageFilenames = $this->imageService->uploadGalleryImages($data['images']);
            $data['images'] = $galleryImageFilenames;
        } elseif (array_key_exists('images', $data) && $data['images'] === null) {
            // If images are explicitly set to null, delete existing gallery images
            if ($listing->images) {
                $listing->deleteGalleryImages();
            }
            $data['images'] = null;
        } else {
            // Remove images from data if not provided (don't change existing images)
            unset($data['images']);
        }

        $listing = $this->listingRepository->update($listing, $data);

        return [
            'listing' => $listing,
            'message' => __('response.listing.updated'),
        ];
    }

    /**
     * Delete listing.
     *
     * @param  Listing  $listing  Listing to delete
     * @param  User  $user  Requesting user
     * @return array<string, mixed> Result message
     *
     * @throws ValidationException
     */
    public function delete(Listing $listing, User $user): array
    {
        // Check if user owns the listing
        if ($listing->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'listing' => [__('response.listing.unauthorized')],
            ]);
        }

        // Delete all images (cover + gallery) if they exist
        if ($listing->cover_image || $listing->images) {
            $listing->deleteAllImages();
        }

        $this->listingRepository->delete($listing);

        return [
            'message' => __('response.listing.deleted'),
        ];
    }

    /**
     * Get paginated listings with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> Result with listings
     */
    public function getPaginated(array $filters = [], int $perPage = 15): array
    {
        $listings = $this->listingRepository->getPaginated($filters, $perPage);

        return [
            'listings' => $listings,
            'message' => __('response.listing.list_retrieved'),
        ];
    }

    /**
     * Get active listings with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> Result with listings
     */
    public function getActive(array $filters = [], int $perPage = 15): array
    {
        $listings = $this->listingRepository->getActive($filters, $perPage);

        return [
            'listings' => $listings,
            'message' => __('response.listing.active_list_retrieved'),
        ];
    }

    /**
     * Get featured listings only.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> Result with featured listings
     */
    public function getFeatured(array $filters = [], int $perPage = 15): array
    {
        $listings = $this->listingRepository->getFeatured($filters, $perPage);

        return [
            'listings' => $listings,
            'message' => __('response.listing.featured_list_retrieved'),
        ];
    }

    /**
     * Get user's listings.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> Result with listings
     */
    public function getUserListings(User $user, array $filters = [], int $perPage = 15): array
    {
        $listings = $this->listingRepository->getUserListings($user, $filters, $perPage);

        return [
            'listings' => $listings,
            'message' => __('response.listing.user_list_retrieved'),
        ];
    }

    /**
     * Search listings.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed> Result with listings
     */
    public function search(string $search, array $filters = [], int $perPage = 15): array
    {
        $listings = $this->listingRepository->search($search, $filters, $perPage);

        return [
            'listings' => $listings,
            'message' => __('response.listing.search_results'),
        ];
    }

    /**
     * Get listing statistics.
     *
     * @return array<string, mixed> Result with statistics
     */
    public function getStatistics(): array
    {
        $statistics = $this->listingRepository->getStatistics();

        return [
            'statistics' => $statistics,
            'message' => __('response.listing.statistics_retrieved'),
        ];
    }

    /**
     * Toggle listing active status.
     *
     * @return array<string, mixed> Result with updated listing
     *
     * @throws ValidationException
     */
    public function toggleActiveStatus(Listing $listing, User $user): array
    {
        // Check if user owns the listing
        if ($listing->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'listing' => [__('response.listing.unauthorized')],
            ]);
        }

        $listing = $this->listingRepository->update($listing, [
            'is_active' => ! $listing->is_active,
        ]);

        $message = $listing->is_active
            ? __('response.listing.activated')
            : __('response.listing.deactivated');

        return [
            'listing' => $listing,
            'message' => $message,
        ];
    }

    /**
     * Generate slug from title in multiple languages.
     *
     * @param  array<string, string>  $titles
     * @return array<string, string>
     */
    private function generateSlugFromTitle(array $titles): array
    {
        $slugs = [];

        foreach ($titles as $locale => $title) {
            $slugs[$locale] = Str::slug($title, '-', $locale);
        }

        return $slugs;
    }
}
