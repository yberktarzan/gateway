<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class CompanyRepository
 *
 * Handles all database operations related to companies.
 * Follows the Repository pattern to separate data access logic from business logic.
 */
class CompanyRepository
{
    /**
     * Create a new company with the given data.
     *
     * @param  array<string, mixed>  $data  Company data
     * @return Company Created company instance
     */
    public function create(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * Find a company by ID.
     *
     * @param  int  $id  Company ID
     * @return Company|null Company instance or null if not found
     */
    public function findById(int $id): ?Company
    {
        return Company::with('user')->find($id);
    }

    /**
     * Find companies by user ID.
     *
     * @param  int  $userId  User ID
     * @return Collection<Company> Collection of companies
     */
    public function findByUserId(int $userId): Collection
    {
        return Company::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all companies with optional filtering.
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  int  $perPage  Items per page for pagination
     * @return LengthAwarePaginator Paginated companies
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Company::with('user');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_vip'])) {
            $query->where('is_vip', $filters['is_vip']);
        }

        if (isset($filters['country_code'])) {
            $query->byCountry($filters['country_code']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhere('website', 'LIKE', "%{$search}%");
            });
        }

        // Default ordering: VIP first, then by created date
        $query->orderBy('is_vip', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get active companies only.
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  int  $perPage  Items per page for pagination
     * @return LengthAwarePaginator Paginated active companies
     */
    public function getActive(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = 'active';

        return $this->getAll($filters, $perPage);
    }

    /**
     * Get VIP companies only.
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  int  $perPage  Items per page for pagination
     * @return LengthAwarePaginator Paginated VIP companies
     */
    public function getVip(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['is_vip'] = true;
        $filters['status'] = 'active'; // VIP companies should be active

        return $this->getAll($filters, $perPage);
    }

    /**
     * Update company data.
     *
     * @param  Company  $company  Company instance
     * @param  array<string, mixed>  $data  Update data
     * @return Company Updated company instance
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }

    /**
     * Delete a company.
     *
     * @param  Company  $company  Company instance
     * @return bool True if deletion successful
     */
    public function delete(Company $company): bool
    {
        return $company->delete();
    }

    /**
     * Set company as VIP.
     *
     * @param  Company  $company  Company instance
     * @return Company Updated company instance
     */
    public function setVip(Company $company): Company
    {
        $company->update(['is_vip' => true]);

        return $company->fresh();
    }

    /**
     * Remove VIP status from company.
     *
     * @param  Company  $company  Company instance
     * @return Company Updated company instance
     */
    public function removeVip(Company $company): Company
    {
        $company->update(['is_vip' => false]);

        return $company->fresh();
    }

    /**
     * Change company status.
     *
     * @param  Company  $company  Company instance
     * @param  string  $status  New status (active, inactive, pending)
     * @return Company Updated company instance
     */
    public function changeStatus(Company $company, string $status): Company
    {
        $company->update(['status' => $status]);

        return $company->fresh();
    }

    /**
     * Count companies by status.
     *
     * @param  string  $status  Status to count
     * @return int Number of companies with given status
     */
    public function countByStatus(string $status): int
    {
        return Company::where('status', $status)->count();
    }

    /**
     * Count companies by user.
     *
     * @param  int  $userId  User ID
     * @return int Number of companies owned by user
     */
    public function countByUser(int $userId): int
    {
        return Company::where('user_id', $userId)->count();
    }
}
