<?php
namespace App\Services;

use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthService
{
    /**
     * Register a new customer account.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password_hash'     => Hash::make($data['password']),
            'phone'             => $data['phone'] ?? null,
            'role'              => 'customer',
            'is_active'         => true,
            'email_verified_at' => now(), // skip email verification for now
        ]);

        $token = $this->issueToken($user);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Validate credentials and issue token.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        // Deliberately vague error message — don't reveal if email exists
        if (!$user || !Hash::check($password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        // Revoke all previous tokens (single session)
        // Comment this out if you want multi-device login
        $user->tokens()->delete();

        $token = $this->issueToken($user);

        return ['user' => $user->load('addresses'), 'token' => $token];
    }

    /**
     * Handle Google OAuth — find or create user.
     */
    public function handleGoogleUser(SocialiteUser $googleUser): array
    {
        // Check if user already exists by Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        // Or find by email (user might have registered with email before)
        if (!$user) {
            $user = User::where('email', $googleUser->getEmail())->first();
        }

        if ($user) {
            // Update Google ID if not set yet
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated.'],
                ]);
            }
        } else {
            // Brand new user via Google
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'password_hash'     => null, // no password for OAuth users
                'role'              => 'customer',
                'is_active'         => true,
                'email_verified_at' => now(), // Google already verified the email
            ]);
        }

        $user->tokens()->delete();
        $token = $this->issueToken($user);

        return ['user' => $user->load('addresses'), 'token' => $token];
    }

    /**
     * Issue a Sanctum token with 30-day expiry.
     */
    private function issueToken(User $user): string
    {
        return $user->createToken(
            'api-token',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;
    }
}