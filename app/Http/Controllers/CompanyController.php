<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\Company\CompanyService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @group Companies
 *
 * APIs for managing companies
 */
class CompanyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CompanyService $companyService
    ) {}

    /**
     * Get all companies
     *
     * Retrieve a list of companies with optional filtering.
     *
     * @queryParam status string Filter by status (active, inactive, pending). Example: active
     * @queryParam is_vip boolean Filter VIP companies. Example: true
     * @queryParam country_code string Filter by country code. Example: US
     * @queryParam search string Search in name, description, website. Example: tech
     * @queryParam per_page integer Items per page (max 100). Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Companies retrieved successfully",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "name": "Tech Corp",
     *         "description": {"en": "A tech company", "tr": "Bir teknoloji şirketi"},
     *         "logo": "logos/tech-corp.jpg",
     *         "website": "https://techcorp.com",
     *         "country_code": "US",
     *         "is_vip": true,
     *         "status": "active",
     *         "user": {"id": 1, "name": "John Doe"}
     *       }
     *     ],
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'is_vip', 'country_code', 'search']);
            $perPage = min($request->get('per_page', 15), 100); // Max 100 items per page

            $result = $this->companyService->getAll($filters, $perPage);

            return $this->successResponse(
                data: $result['companies'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.list_failed')
            );
        }
    }

    /**
     * Get active companies only
     *
     * Retrieve only active companies with optional filtering.
     *
     * @queryParam is_vip boolean Filter VIP companies. Example: true
     * @queryParam country_code string Filter by country code. Example: US
     * @queryParam search string Search in name, description, website. Example: tech
     * @queryParam per_page integer Items per page (max 100). Example: 15
     * @queryParam page integer Page number. Example: 1
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_vip', 'country_code', 'search']);
            $perPage = min($request->get('per_page', 15), 100);

            $result = $this->companyService->getActive($filters, $perPage);

            return $this->successResponse(
                data: $result['companies'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.active_list_failed')
            );
        }
    }

    /**
     * Get VIP companies only
     *
     * Retrieve only VIP companies with optional filtering.
     *
     * @queryParam country_code string Filter by country code. Example: US
     * @queryParam search string Search in name, description, website. Example: tech
     * @queryParam per_page integer Items per page (max 100). Example: 15
     * @queryParam page integer Page number. Example: 1
     */
    public function vip(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['country_code', 'search']);
            $perPage = min($request->get('per_page', 15), 100);

            $result = $this->companyService->getVip($filters, $perPage);

            return $this->successResponse(
                data: $result['companies'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.vip_list_failed')
            );
        }
    }

    /**
     * Create a new company
     *
     * @authenticated
     *
     * @bodyParam name string required Company name. Example: Tech Corp
     * @bodyParam country_code string required ISO country code. Example: US
     * @bodyParam description object required Translatable descriptions. Example: {"en": "A tech company", "tr": "Bir teknoloji şirketi"}
     * @bodyParam logo string optional Logo file path. Example: logos/tech-corp.jpg
     * @bodyParam website string optional Company website. Example: https://techcorp.com
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Company created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Tech Corp",
     *     "description": {"en": "A tech company", "tr": "Bir teknoloji şirketi"},
     *     "status": "pending"
     *   }
     * }
     */
    public function store(CreateCompanyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = Auth::user();

            $result = $this->companyService->create($data, $user);

            return $this->createdResponse(
                data: $result['company'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.create_failed')
            );
        }
    }

    /**
     * Get company by ID
     *
     * @urlParam id integer required Company ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Company retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Tech Corp",
     *     "description": {"en": "A tech company", "tr": "Bir teknoloji şirketi"},
     *     "user": {"id": 1, "name": "John Doe"}
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->companyService->getById($id);

            return $this->successResponse(
                data: $result['company'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->notFoundResponse(
                message: $e->getMessage()
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.retrieve_failed')
            );
        }
    }

    /**
     * Update company
     *
     * @authenticated
     *
     * @urlParam id integer required Company ID. Example: 1
     *
     * @bodyParam name string optional Company name. Example: Tech Corp Updated
     * @bodyParam country_code string optional ISO country code. Example: TR
     * @bodyParam description object optional Translatable descriptions. Example: {"en": "Updated description", "tr": "Güncellenmiş açıklama"}
     * @bodyParam logo string optional Logo file path. Example: logos/new-logo.jpg
     * @bodyParam website string optional Company website. Example: https://newtechcorp.com
     */
    public function update(UpdateCompanyRequest $request, int $id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);
            $data = $request->validated();
            $user = Auth::user();

            $result = $this->companyService->update($company, $data, $user);

            return $this->successResponse(
                data: $result['company'],
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.update_failed')
            );
        }
    }

    /**
     * Delete company
     *
     * @authenticated
     *
     * @urlParam id integer required Company ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Company deleted successfully"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);
            $user = Auth::user();

            $result = $this->companyService->delete($company, $user);

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
                message: __('response.company.delete_failed')
            );
        }
    }

    /**
     * Get current user's companies
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User companies retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "My Company",
     *       "status": "active"
     *     }
     *   ]
     * }
     */
    public function myCompanies(): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->companyService->getUserCompanies($user);

            return $this->successResponse(
                data: $result['companies'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.user_companies_failed')
            );
        }
    }

    /**
     * Get company statistics
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Statistics retrieved successfully",
     *   "data": {
     *     "total": 100,
     *     "active": 80,
     *     "inactive": 10,
     *     "pending": 10,
     *     "vip": 15
     *   }
     * }
     */
    public function statistics(): JsonResponse
    {
        try {
            $result = $this->companyService->getStatistics();

            return $this->successResponse(
                data: $result['statistics'],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.company.statistics_failed')
            );
        }
    }
}
