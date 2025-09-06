<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Logging\ElasticLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

/**
 * Trait ApiResponse
 *
 * Provides a unified, predictable JSON response structure for API controllers.
 *
 * All responses follow the schema: { success: bool, message: string|null, data: mixed, errors: array }
 * Includes automatic logging of API responses with contextual information.
 */
trait ApiResponse
{
    /**
     * Check if logging should be enabled for a specific response type.
     *
     * @param  string  $responseType  Type of response (success, error, validation, etc.)
     * @param  bool  $shouldLog  Manual override for logging
     * @return bool Whether logging should be enabled
     */
    private function shouldLogResponse(string $responseType, bool $shouldLog = false): bool
    {
        // Manuel override varsa öncelik tanı
        if ($shouldLog === false) {
            return false;
        }
        
        if (!config('api.log_responses', true)) {
            return false;
        }

        if ($shouldLog === true) {
            return true;
        }

        /** @var array<string, bool> $logTypes */
        $logTypes = (array) config('api.log_responses_per_type', []);

        return $logTypes[$responseType] ?? false;
    }

    /**
     * Build a unified JSON response with consistent structure.
     *
     * @param  bool  $success  Whether the operation was successful
     * @param  string|null  $message  Response message for the client
     * @param  mixed  $data  Response data payload
     * @param  array<string, mixed>  $errors  Error details (for failed responses)
     * @param  int  $status  HTTP status code
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse JSON response with consistent structure
     */
    private function respond(
        bool $success,
        ?string $message,
        mixed $data = null,
        array $errors = [],
        int $status = Response::HTTP_OK,
        bool $shouldLog = false
    ): JsonResponse {
        $responseData = [
            'success' => $success,
            'message' => $message,
            'api_version' => config('api.version', '1.0.0'),
        ];

        if ($success && $data !== null) {
            $responseData['data'] = $data;
        }

        if (!$success && !empty($errors)) {
            $responseData['errors'] = $errors;
        }

        if ($shouldLog) {
            $this->logResponse($message ?? 'API Response', $status, $data, $errors);
        }

        return response()->json($responseData, $status);
    }

    private function logResponse(string $message, int $status, mixed $data, array $errors = []): void
    {
        try {
            $elasticLogger = app(ElasticLogService::class);

            $context = [
                'domain' => 'api',
                'action' => 'response',
                'http' => ['status' => $status],
                'response_data' => [
                    'success' => $status < 400,
                    'message' => $message,
                    'errors' => $errors,
                    'data_type' => gettype($data),
                ],
            ];

            if ($status >= 500) {
                $elasticLogger->error($message, $context);
            } elseif ($status >= 400) {
                $elasticLogger->warning($message, $context);
            } else {
                $elasticLogger->info($message, $context);
            }
        } catch (Throwable $e) {
            // Silent fail - don't break API response
        }
    }

    /**
     * Return a successful response.
     *
     * @param  mixed  $data  The data to return
     * @param  string|null  $message  Success message (uses default if null)
     * @param  int  $status  HTTP status code
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Success response with 2xx status code
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        bool $shouldLog = false
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('success', $shouldLog);

        return $this->respond(
            success: true,
            message: $message ?? __('response.success.default'),
            data: $data,
            status: $status,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return an error response.
     *
     * @param  string|null  $message  Error message (uses default if null)
     * @param  int  $status  HTTP status code (4xx or 5xx)
     * @param  array<string, mixed>  $errors  Detailed error information
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Error response with 4xx or 5xx status code
     */
    protected function errorResponse(
        ?string $message = null,
        int $status = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        bool $shouldLog = true
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('error', $shouldLog);

        return $this->respond(
            success: false,
            message: $message ?? __('response.error.default'),
            errors: $errors,
            status: $status,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a validation error response (422).
     *
     * @param  array<string, mixed>  $errors  Validation error details
     * @param  string|null  $message  Custom validation message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Validation error response
     */
    protected function validationErrorResponse(
        array $errors,
        ?string $message = null,
        bool $shouldLog = true
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('validation', $shouldLog);

        return $this->errorResponse(
            message: $message ?? __('response.error.validation'),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a "not found" error response (404).
     *
     * @param  string|null  $message  Custom not found message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Not found error response
     */
    protected function notFoundResponse(?string $message = null, bool $shouldLog = true): JsonResponse
    {
        $shouldLogResolved = $this->shouldLogResponse('not_found', $shouldLog);

        return $this->errorResponse(
            message: $message ?? __('response.error.not_found'),
            status: Response::HTTP_NOT_FOUND,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return an "unauthorized" error response (401).
     *
     * @param  string|null  $message  Custom unauthorized message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Unauthorized error response
     */
    protected function unauthorizedResponse(?string $message = null, bool $shouldLog = true): JsonResponse
    {
        $shouldLogResolved = $this->shouldLogResponse('unauthorized', $shouldLog);

        return $this->errorResponse(
            message: $message ?? __('response.error.unauthorized'),
            status: Response::HTTP_UNAUTHORIZED,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a "forbidden" error response (403).
     *
     * @param  string|null  $message  Custom forbidden message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Forbidden error response
     */
    protected function forbiddenResponse(?string $message = null, bool $shouldLog = true): JsonResponse
    {
        $shouldLogResolved = $this->shouldLogResponse('forbidden', $shouldLog);

        return $this->errorResponse(
            message: $message ?? __('response.error.forbidden'),
            status: Response::HTTP_FORBIDDEN,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a "bad request" error response (400).
     *
     * @param  string|null  $message  Custom bad request message
     * @param  array<string, mixed>  $errors  Additional error details
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Bad request error response
     */
    protected function badRequestResponse(
        ?string $message = null,
        array $errors = [],
        bool $shouldLog = true
    ): JsonResponse {
        return $this->errorResponse(
            message: $message ?? __('response.error.bad_request'),
            status: Response::HTTP_BAD_REQUEST,
            errors: $errors,
            shouldLog: $shouldLog
        );
    }

    /**
     * Return a "server error" response (500).
     *
     * @param  string|null  $message  Custom server error message
     * @param  array<string, mixed>  $errors  Additional error details
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Server error response
     */
    protected function serverErrorResponse(
        ?string $message = null,
        array $errors = [],
        bool $shouldLog = true
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('server_error', $shouldLog);

        return $this->errorResponse(
            message: $message ?? __('response.error.server_error'),
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
            errors: $errors,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a "rate limited" error response (429).
     *
     * @param  string|null  $message  Custom rate limit message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Rate limited error response
     */
    protected function rateLimitedResponse(?string $message = null, bool $shouldLog = true): JsonResponse
    {
        return $this->errorResponse(
            message: $message ?? __('response.error.rate_limited'),
            status: Response::HTTP_TOO_MANY_REQUESTS,
            shouldLog: $shouldLog
        );
    }

    /**
     * Return a "created" success response (201).
     *
     * @param  mixed  $data  Created resource data
     * @param  string|null  $message  Custom creation message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Created success response
     */
    protected function createdResponse(
        mixed $data = null,
        ?string $message = null,
        bool $shouldLog = true
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('created', $shouldLog);

        return $this->successResponse(
            data: $data,
            message: $message ?? __('response.success.created'),
            status: Response::HTTP_CREATED,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return an "updated" success response (200).
     *
     * @param  mixed  $data  Updated resource data
     * @param  string|null  $message  Custom update message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Updated success response
     */
    protected function updatedResponse(
        mixed $data = null,
        ?string $message = null,
        bool $shouldLog = true
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('updated', $shouldLog);

        return $this->successResponse(
            data: $data,
            message: $message ?? __('response.success.updated'),
            status: Response::HTTP_OK,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a "deleted" success response (200).
     *
     * @param  string|null  $message  Custom deletion message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Deleted success response
     */
    protected function deletedResponse(?string $message = null, bool $shouldLog = true): JsonResponse
    {
        $shouldLogResolved = $this->shouldLogResponse('deleted', $shouldLog);

        return $this->successResponse(
            data: null,
            message: $message ?? __('response.success.deleted'),
            status: Response::HTTP_OK,
            shouldLog: $shouldLogResolved
        );
    }

    /**
     * Return a "no content" success response (204).
     *
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse No content success response
     */
    protected function noContentResponse(bool $shouldLog = false): JsonResponse
    {
        return $this->successResponse(
            data: null,
            message: null,
            status: Response::HTTP_NO_CONTENT,
            shouldLog: $shouldLog
        );
    }

    /**
     * Return a redirect response with URL (302).
     *
     * @param  string  $url  The URL to redirect to
     * @param  string|null  $message  Custom redirect message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Redirect response with URL in data
     */
    protected function redirectResponse(
        string $url,
        ?string $message = null,
        bool $shouldLog = false
    ): JsonResponse {
        return $this->successResponse(
            data: ['url' => $url],
            message: $message ?? __('response.success.redirecting'),
            status: Response::HTTP_FOUND,
            shouldLog: $shouldLog
        );
    }

    /**
     * Return a paginated data response.
     *
     * @param  mixed  $paginatedData  Laravel paginator instance or paginated array
     * @param  string|null  $message  Custom message
     * @param  bool  $shouldLog  Whether to log this response
     * @return JsonResponse Paginated data response
     */
    protected function paginatedResponse(
        mixed $paginatedData,
        ?string $message = null,
        bool $shouldLog = false
    ): JsonResponse {
        $shouldLogResolved = $this->shouldLogResponse('paginated', $shouldLog);

        return $this->successResponse(
            data: $paginatedData,
            message: $message ?? __('response.success.retrieved'),
            status: Response::HTTP_OK,
            shouldLog: $shouldLogResolved
        );
    }
}
