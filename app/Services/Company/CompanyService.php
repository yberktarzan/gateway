<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Validation\ValidationException;

/**
 * Class CompanyService
 *
 * Handles business logic for company operations.
 * Acts as a bridge between controllers and repositories.
 */
class CompanyService
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly LogoService $logoService
    ) {}

    /**
     * Create a new company.
     *
     * @param  array<string, mixed>  $data  Company data
     * @param  User  $user  Owner user
     * @return array<string, mixed> Result with company data
     *
     * @throws ValidationException
     */
    public function create(array $data, User $user): array
    {
        // Add user_id to data
        $data['user_id'] = $user->id;

        // Set default status if not provided
        if (! isset($data['status'])) {
            $data['status'] = 'pending';
        }

        // Only admins can set VIP status directly
        if (isset($data['is_vip']) && $data['is_vip'] === true) {
            // TODO: Check if user is admin when role system is implemented
            // For now, regular users cannot set VIP status
            $data['is_vip'] = false;
        }

        // Handle logo upload if provided
        if (isset($data['logo']) && $data['logo'] !== null) {
            $logoFilename = $this->logoService->uploadLogo($data['logo']);
            $data['logo'] = $logoFilename;
        } else {
            unset($data['logo']); // Remove null/empty logo from data
        }

        $company = $this->companyRepository->create($data);

        return [
            'company' => $company->load('user'),
            'message' => __('response.company.created'),
        ];
    }

    /**
     * Get company by ID.
     *
     * @param  int  $id  Company ID
     * @return array<string, mixed> Result with company data
     *
     * @throws ValidationException
     */
    public function getById(int $id): array
    {
        $company = $this->companyRepository->findById($id);

        if (! $company) {
            throw ValidationException::withMessages([
                'company' => [__('response.company.not_found')],
            ]);
        }

        return [
            'company' => $company,
            'message' => __('response.company.retrieved'),
        ];
    }

    /**
     * Get companies with filtering and pagination.
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  int  $perPage  Items per page
     * @return array<string, mixed> Result with paginated companies
     */
    public function getAll(array $filters = [], int $perPage = 15): array
    {
        $companies = $this->companyRepository->getAll($filters, $perPage);

        return [
            'companies' => $companies,
            'message' => __('response.company.list_retrieved'),
        ];
    }

    /**
     * Get active companies only.
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  int  $perPage  Items per page
     * @return array<string, mixed> Result with active companies
     */
    public function getActive(array $filters = [], int $perPage = 15): array
    {
        $companies = $this->companyRepository->getActive($filters, $perPage);

        return [
            'companies' => $companies,
            'message' => __('response.company.active_list_retrieved'),
        ];
    }

    /**
     * Get VIP companies only.
     *
     * @param  array<string, mixed>  $filters  Optional filters
     * @param  int  $perPage  Items per page
     * @return array<string, mixed> Result with VIP companies
     */
    public function getVip(array $filters = [], int $perPage = 15): array
    {
        $companies = $this->companyRepository->getVip($filters, $perPage);

        return [
            'companies' => $companies,
            'message' => __('response.company.vip_list_retrieved'),
        ];
    }

    /**
     * Get user's companies.
     *
     * @param  User  $user  Owner user
     * @return array<string, mixed> Result with user's companies
     */
    public function getUserCompanies(User $user): array
    {
        $companies = $this->companyRepository->findByUserId($user->id);

        return [
            'companies' => $companies,
            'message' => __('response.company.user_companies_retrieved'),
        ];
    }

    /**
     * Update company.
     *
     * @param  Company  $company  Company to update
     * @param  array<string, mixed>  $data  Update data
     * @param  User  $user  Requesting user
     * @return array<string, mixed> Result with updated company
     *
     * @throws ValidationException
     */
    public function update(Company $company, array $data, User $user): array
    {
        // Check if user owns the company
        if ($company->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'company' => [__('response.company.unauthorized')],
            ]);
        }

        // Regular users cannot change VIP status or certain statuses
        if (isset($data['is_vip'])) {
            // TODO: Check if user is admin when role system is implemented
            // For now, remove is_vip from data for regular users
            unset($data['is_vip']);
        }

        if (isset($data['status']) && in_array($data['status'], ['active', 'inactive'])) {
            // TODO: Only admins should be able to change status to active/inactive
            // For now, keep the restriction
            unset($data['status']);
        }

        // Handle logo upload if provided
        if (isset($data['logo']) && $data['logo'] !== null) {
            // Delete old logo if it exists
            if ($company->logo) {
                $company->deleteLogo();
            }

            // Upload new logo
            $logoFilename = $this->logoService->uploadLogo($data['logo']);
            $data['logo'] = $logoFilename;
        } elseif (array_key_exists('logo', $data) && $data['logo'] === null) {
            // If logo is explicitly set to null, delete existing logo
            if ($company->logo) {
                $company->deleteLogo();
            }
            $data['logo'] = null;
        } else {
            // Remove logo from data if not provided (don't change existing logo)
            unset($data['logo']);
        }

        $company = $this->companyRepository->update($company, $data);

        return [
            'company' => $company,
            'message' => __('response.company.updated'),
        ];
    }

    /**
     * Delete company.
     *
     * @param  Company  $company  Company to delete
     * @param  User  $user  Requesting user
     * @return array<string, mixed> Result message
     *
     * @throws ValidationException
     */
    public function delete(Company $company, User $user): array
    {
        // Check if user owns the company
        if ($company->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'company' => [__('response.company.unauthorized')],
            ]);
        }

        // Delete logo file if it exists
        if ($company->logo) {
            $company->deleteLogo();
        }

        $this->companyRepository->delete($company);

        return [
            'message' => __('response.company.deleted'),
        ];
    }

    /**
     * Set company as VIP (Admin only).
     *
     * @param  Company  $company  Company to set as VIP
     * @return array<string, mixed> Result with updated company
     */
    public function setVip(Company $company): array
    {
        // TODO: Add admin check when role system is implemented
        $company = $this->companyRepository->setVip($company);

        return [
            'company' => $company,
            'message' => __('response.company.vip_set'),
        ];
    }

    /**
     * Remove VIP status (Admin only).
     *
     * @param  Company  $company  Company to remove VIP status from
     * @return array<string, mixed> Result with updated company
     */
    public function removeVip(Company $company): array
    {
        // TODO: Add admin check when role system is implemented
        $company = $this->companyRepository->removeVip($company);

        return [
            'company' => $company,
            'message' => __('response.company.vip_removed'),
        ];
    }

    /**
     * Change company status (Admin only).
     *
     * @param  Company  $company  Company to update
     * @param  string  $status  New status
     * @return array<string, mixed> Result with updated company
     *
     * @throws ValidationException
     */
    public function changeStatus(Company $company, string $status): array
    {
        // TODO: Add admin check when role system is implemented

        $validStatuses = ['active', 'inactive', 'pending'];
        if (! in_array($status, $validStatuses)) {
            throw ValidationException::withMessages([
                'status' => [__('response.company.invalid_status')],
            ]);
        }

        $company = $this->companyRepository->changeStatus($company, $status);

        return [
            'company' => $company,
            'message' => __('response.company.status_changed'),
        ];
    }

    /**
     * Get company statistics.
     *
     * @return array<string, mixed> Statistics data
     */
    public function getStatistics(): array
    {
        return [
            'statistics' => [
                'total' => $this->companyRepository->countByStatus('active') +
                          $this->companyRepository->countByStatus('inactive') +
                          $this->companyRepository->countByStatus('pending'),
                'active' => $this->companyRepository->countByStatus('active'),
                'inactive' => $this->companyRepository->countByStatus('inactive'),
                'pending' => $this->companyRepository->countByStatus('pending'),
                'vip' => Company::where('is_vip', true)->count(),
            ],
            'message' => __('response.company.statistics_retrieved'),
        ];
    }
}
