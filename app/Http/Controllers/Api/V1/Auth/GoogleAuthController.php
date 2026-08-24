<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Redirect browser to Google consent screen.
     * Frontend calls this URL directly (not via fetch).
     */
    public function redirect(): RedirectResponse
    {
        $this->assertGoogleConfigured();

        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Google redirects back here with a code.
     * We exchange it for user info, then issue our own Sanctum token.
     */
    public function callback(): RedirectResponse
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        try {
            $this->assertGoogleConfigured();
            $googleUser = Socialite::driver('google')->stateless()->user();
            $result = $this->authService->handleGoogleUser($googleUser);
        } catch (\Throwable $e) {
            return redirect($frontend . '/login?error=google_failed');
        }

        return redirect($frontend . '/callback?token=' . urlencode($result['token']));
    }

    private function assertGoogleConfigured(): void
    {
        if (
            blank(config('services.google.client_id'))
            || blank(config('services.google.client_secret'))
            || blank(config('services.google.redirect'))
        ) {
            abort(500, 'Google OAuth is not configured. Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI in .env, then run php artisan config:clear.');
        }
    }
}