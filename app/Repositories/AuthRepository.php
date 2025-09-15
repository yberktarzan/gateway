<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Class AuthRepository
 *
 * Handles all database operations related to authentication.
 * Follows the Repository pattern to separate data access logic from business logic.
 */
class AuthRepository
{
    /**
     * Create a new user with the given data.
     *
     * @param  array<string, mixed>  $data  User data
     * @return User Created user instance
     */
    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Find a user by email address.
     *
     * @param  string  $email  User email
     * @return User|null User instance or null if not found
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find a user by ID.
     *
     * @param  int  $id  User ID
     * @return User|null User instance or null if not found
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find a user by email verification token.
     *
     * @param  string  $token  Email verification token
     * @return User|null User instance or null if not found
     */
    public function findByEmailVerificationToken(string $token): ?User
    {
        return User::where('email_verification_token', $token)->first();
    }

    /**
     * Update user information.
     *
     * @param  User  $user  User instance to update
     * @param  array<string, mixed>  $data  Data to update
     * @return User Updated user instance
     */
    public function updateUser(User $user, array $data): User
    {
        $fillableData = array_intersect_key($data, array_flip($user->getFillable()));

        if (isset($fillableData['password'])) {
            $fillableData['password'] = Hash::make($fillableData['password']);
        }

        $user->update($fillableData);

        return $user->fresh();
    }

    /**
     * Update user profile image.
     *
     * @param  User  $user  User instance
     * @param  string  $imagePath  Profile image path
     * @return User Updated user instance
     */
    public function updateProfileImage(User $user, string $imagePath): User
    {
        $user->update(['profile_image' => $imagePath]);

        return $user->fresh();
    }

    /**
     * Verify user's email with token.
     *
     * @param  User  $user  User instance
     * @param  string  $token  Verification token
     * @return bool True if verification successful
     */
    public function verifyEmail(User $user, string $token): bool
    {
        return $user->verifyEmail($token);
    }

    /**
     * Generate and save email verification token for user.
     *
     * @param  User  $user  User instance
     * @return string Generated token
     */
    public function generateEmailVerificationToken(User $user): string
    {
        return $user->generateEmailVerificationToken();
    }

    /**
     * Check if email exists in database.
     *
     * @param  string  $email  Email address
     * @param  int|null  $excludeUserId  User ID to exclude from check
     * @return bool True if email exists
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $query = User::where('email', $email);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Check if phone exists in database.
     *
     * @param  string  $phone  Phone number
     * @param  int|null  $excludeUserId  User ID to exclude from check
     * @return bool True if phone exists
     */
    public function phoneExists(string $phone, ?int $excludeUserId = null): bool
    {
        $query = User::where('phone', $phone);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Get all users (for admin purposes).
     *
     * @return Collection<int, User> Collection of users
     */
    public function getAllUsers(): Collection
    {
        return User::all();
    }

    /**
     * Delete user account.
     *
     * @param  User  $user  User instance to delete
     * @return bool True if deletion successful
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Update user's last login timestamp.
     *
     * @param  User  $user  User instance
     * @return User Updated user instance
     */
    public function updateLastLogin(User $user): User
    {
        $user->touch(); // Updates updated_at timestamp

        return $user->fresh();
    }
}
