<?php

namespace App\Repositories;

use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ListingRepository
{
    /**
     * Create a new listing.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Listing
    {
        return Listing::create($data);
    }

    /**
     * Find a listing by ID.
     */
    public function findById(int $id): ?Listing
    {
        return Listing::with('user')->find($id);
    }

    /**
     * Find a listing by slug in any language.
     */
    public function findBySlug(string $slug, ?string $locale = null): ?Listing
    {
        $locale = $locale ?: app()->getLocale();

        return Listing::with('user')
            ->where(function ($query) use ($slug, $locale) {
                $query->whereRaw('JSON_EXTRACT(slug, ?) = ?', ['$.'.$locale, $slug])
                    ->orWhereRaw('JSON_EXTRACT(slug, ?) = ?', ['$.en', $slug]); // Fallback to English
            })
            ->first();
    }

    /**
     * Update a listing.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Listing $listing, array $data): Listing
    {
        $listing->update($data);

        return $listing->refresh();
    }

    /**
     * Delete a listing.
     */
    public function delete(Listing $listing): bool
    {
        return $listing->delete();
    }

    /**
     * Get paginated listings with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user');

        // Apply filters
        $this->applyFilters($query, $filters);

        // Default ordering: Featured first, then by created date
        $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get all active listings.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getActive(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->active();

        $this->applyFilters($query, $filters);

        // Default ordering: Featured first, then by created date
        $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get listings by type.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getByType(ListingType $type, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->ofType($type)->active();

        $this->applyFilters($query, $filters);

        // Default ordering: Featured first, then by created date
        $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get listings by country.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getByCountry(string $countryCode, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->inCountry($countryCode)->active()->latest();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get user's listings.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getUserListings(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->byUser($user->id)->latest();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get featured listings only.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFeatured(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->featured()->active();

        $this->applyFilters($query, $filters);

        // Featured listings ordered by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Search listings.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(string $search, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->search($search)->active()->latest();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get recent listings.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getRecent(int $days = 7, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Listing::with('user')->recent($days)->active()->latest();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get listings statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Listing::count(),
            'active' => Listing::active()->count(),
            'inactive' => Listing::inactive()->count(),
            'by_type' => [
                'product_seller' => Listing::ofType(ListingType::PRODUCT_SELLER)->active()->count(),
                'product_buyer' => Listing::ofType(ListingType::PRODUCT_BUYER)->active()->count(),
                'service_giver' => Listing::ofType(ListingType::SERVICE_GIVER)->active()->count(),
                'service_taker' => Listing::ofType(ListingType::SERVICE_TAKER)->active()->count(),
                'other' => Listing::ofType(ListingType::OTHER)->active()->count(),
            ],
            'recent' => [
                'today' => Listing::where('created_at', '>=', now()->startOfDay())->count(),
                'this_week' => Listing::recent(7)->count(),
                'this_month' => Listing::recent(30)->count(),
            ],
        ];
    }

    /**
     * Get popular listings (most recent for now, can be extended with view counts etc.)
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Listing::with('user')
            ->active()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Apply filters to query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['listing_type'])) {
            $query->where('listing_type', $filters['listing_type']);
        }

        if (isset($filters['country_code'])) {
            $query->where('country_code', $filters['country_code']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['location']) && ! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (isset($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }
    }
}
