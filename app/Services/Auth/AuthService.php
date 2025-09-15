<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Class AuthService
 *
 * Handles authentication business logic and validation.
 * Follows SOLID principles with single responsibility for auth operations.
 */
class AuthService
{
    public function __construct(
        private AuthRepository $authRepository
    ) {}

    /**
     * Register a new user.
     *
     * @param  array<string, mixed>  $data  Registration data
     * @return array<string, mixed> Registration result with user and token
     *
     * @throws ValidationException
     */
    public function register(array $data): array
    {
        // FormRequest validation already handles this, no need for duplicate validation
        $user = $this->authRepository->createUser($data);

        // Create authentication token
        $token = $this->createToken($user);

        return [
            'user' => $user->makeHidden(['email_verification_token']),
            'token' => $token,
            'message' => __('response.auth.register_success_no_verification'),
        ];
    }

    /**
     * Authenticate user login.
     *
     * @param  array<string, mixed>  $credentials  Login credentials
     * @return array<string, mixed> Login result with user and token
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        // FormRequest validation already handles this, no need for duplicate validation
        $user = $this->authRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('response.auth.invalid_credentials')],
            ]);
        }

        // Update last login
        $this->authRepository->updateLastLogin($user);

        // Create authentication token
        $token = $this->createToken($user);

        return [
            'user' => $user->makeHidden(['email_verification_token']),
            'token' => $token,
            'message' => __('response.auth.login_success'),
        ];
    }

    /**
     * Logout user by revoking current token.
     *
     * @param  User  $user  Authenticated user
     * @return array<string, mixed> Logout result
     */
    public function logout(User $user): array
    {
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

        return [
            'message' => __('response.auth.logout_success'),
        ];
    }

    /**
     * Refresh user authentication token.
     *
     * @param  User  $user  Authenticated user
     * @return array<string, mixed> Refresh result with new token
     */
    public function refreshToken(User $user): array
    {
        // Revoke current token
        $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();

        // Create new token
        $token = $this->createToken($user);

        return [
            'token' => $token,
            'message' => __('response.auth.token_refreshed'),
        ];
    }

    /**
     * Update user profile.
     *
     * @param  User  $user  User to update
     * @param  array<string, mixed>  $data  Update data
     * @return array<string, mixed> Update result
     *
     * @throws ValidationException
     */
    public function updateProfile(User $user, array $data): array
    {
        // FormRequest validation already handles this, no need for duplicate validation
        $updatedUser = $this->authRepository->updateUser($user, $data);

        return [
            'user' => $updatedUser->makeHidden(['email_verification_token']),
            'message' => __('response.auth.profile_updated'),
        ];
    }

    /**
     * Change user password.
     *
     * @param  User  $user  User instance
     * @param  array<string, mixed>  $data  Password change data
     * @return array<string, mixed> Change result
     *
     * @throws ValidationException
     */
    public function changePassword(User $user, array $data): array
    {
        // FormRequest validation already handles this, no need for duplicate validation
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('response.auth.current_password_incorrect')],
            ]);
        }

        $this->authRepository->updateUser($user, [
            'password' => $data['new_password'],
        ]);

        // Logout user to force re-login
        Auth::logout();

        return [
            'message' => __('response.auth.password_changed'),
        ];
    }

    /**
     * Send password reset link.
     *
     * @param  array<string, mixed>  $data  Reset request data
     * @return array<string, mixed> Reset result
     *
     * @throws ValidationException
     */
    public function sendPasswordResetLink(array $data): array
    {
        // FormRequest validation already handles this, no need for duplicate validation
        $user = $this->authRepository->findByEmail($data['email']);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('response.auth.email_not_found')],
            ]);
        }

        $status = Password::sendResetLink(['email' => $data['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__('response.auth.reset_link_failed')],
            ]);
        }

        return [
            'message' => __('response.auth.password_reset_sent'),
        ];
    }

    /**
     * Reset password using token.
     *
     * @param  array<string, mixed>  $data  Reset data
     * @return array<string, mixed> Reset result
     *
     * @throws ValidationException
     */
    public function resetPassword(array $data): array
    {
        // FormRequest validation already handles this, no need for duplicate validation
        $status = Password::reset(
            $data,
            function ($user, $password) {
                $this->authRepository->updateUser($user, [
                    'password' => $password,
                ]);

                // No need to revoke tokens in session-based auth
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__('response.auth.password_reset_failed')],
            ]);
        }

        return [
            'message' => __('response.auth.password_reset_success'),
        ];
    }

    /**
     * Verify user email with token.
     *
     * @param  string  $token  Verification token
     * @return array<string, mixed> Verification result
     *
     * @throws ValidationException
     */
    public function verifyEmail(string $token): array
    {
        $user = $this->authRepository->findByEmailVerificationToken($token);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => [__('response.auth.invalid_token')],
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'message' => __('response.auth.email_already_verified'),
            ];
        }

        $verified = $this->authRepository->verifyEmail($user, $token);

        if (! $verified) {
            throw ValidationException::withMessages([
                'token' => [__('response.auth.verification_failed')],
            ]);
        }

        return [
            'user' => $user->fresh()->makeHidden(['email_verification_token']),
            'message' => __('response.auth.email_verified'),
        ];
    }

    /**
     * Resend email verification.
     *
     * @param  User  $user  User instance
     * @return array<string, mixed> Resend result
     */
    public function resendEmailVerification(User $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'message' => __('response.auth.email_already_verified'),
            ];
        }

        $verificationToken = $this->authRepository->generateEmailVerificationToken($user);

        return [
            'verification_token' => $verificationToken,
            'message' => __('response.auth.verification_sent'),
        ];
    }

    /**
     * Create authentication token for user.
     *
     * @param  User  $user  User instance
     * @return string Authentication token
     */
    private function createToken(User $user): string
    {
        return $user->createToken('auth-token')->plainTextToken;
    }
}
