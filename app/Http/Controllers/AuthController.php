<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Class AuthController
 *
 * Handles authentication-related HTTP requests.
 * Uses ApiResponse trait for consistent response formatting.
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Register a new user.
     *
     * @group Authentication
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return $this->createdResponse(
                data: $result,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.auth.registration_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Authenticate user login.
     *
     * @group Authentication
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->successResponse(
                data: $result,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse(
                message: __('response.auth.login_failed')
            );
        }
    }

    /**
     * Logout current user.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            $result = $this->authService->logout($user);

            return $this->successResponse(
                data: null,
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.auth.logout_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Refresh authentication token.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            $result = $this->authService->refreshToken($user);

            return $this->successResponse(
                data: $result,
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.auth.token_refresh_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Get current authenticated user profile.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            return $this->successResponse(
                data: ['user' => $user->makeHidden(['email_verification_token'])],
                message: __('response.auth.profile_retrieved')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.auth.profile_retrieve_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Update user profile.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            $result = $this->authService->updateProfile($user, $request->validated());

            return $this->updatedResponse(
                data: $result,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.auth.profile_update_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Change user password.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            $result = $this->authService->changePassword($user, $request->validated());

            return $this->successResponse(
                data: null,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->badRequestResponse(
                message: __('response.auth.password_change_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Send password reset link via email.
     *
     * @group Authentication
     *
     * @unauthenticated
     */
    public function sendPasswordResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->sendPasswordResetLink($request->validated());

            return $this->successResponse(
                data: null,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->badRequestResponse(
                message: __('response.auth.reset_link_send_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Reset password using token.
     *
     * @group Authentication
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->resetPassword($request->validated());

            return $this->successResponse(
                data: null,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.error.validation')
            );
        } catch (\Throwable $e) {
            return $this->badRequestResponse(
                message: __('response.auth.password_reset_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Verify user email with token.
     *
     * @group Authentication
     *
     * @unauthenticated
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $token = $request->input('token');

            if (! $token) {
                return $this->badRequestResponse(
                    message: __('response.auth.verification_token_required')
                );
            }

            $result = $this->authService->verifyEmail($token);

            return $this->successResponse(
                data: $result['user'] ?? null,
                message: $result['message']
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                errors: $e->errors(),
                message: __('response.auth.verification_failed')
            );
        } catch (\Throwable $e) {
            return $this->badRequestResponse(
                message: __('response.auth.verification_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Resend email verification.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            $result = $this->authService->resendEmailVerification($user);

            return $this->successResponse(
                data: ['verification_token' => $result['verification_token'] ?? null],
                message: $result['message']
            );
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: __('response.auth.verification_email_failed'),
                errors: ['system' => $e->getMessage()]
            );
        }
    }

    /**
     * Check authentication status.
     *
     * @group Authentication
     *
     * @authenticated
     */
    public function checkAuth(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return $this->unauthorizedResponse(
                    message: __('response.auth.not_authenticated')
                );
            }

            return $this->successResponse(
                data: [
                    'authenticated' => true,
                    'user' => $user->makeHidden(['email_verification_token']),
                ],
                message: __('response.auth.authenticated')
            );
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse(
                message: __('response.auth.auth_check_failed')
            );
        }
    }
}
