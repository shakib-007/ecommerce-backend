<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
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
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Google redirects back here with a code.
     * We exchange it for user info, then issue our own token.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect(env('FRONTEND_URL') . '/login?error=google_failed');
        }

        $result = $this->authService->handleGoogleUser($googleUser);

        // Redirect to Next.js callback page with token in URL
        return redirect(
            env('FRONTEND_URL') . '/callback?token=' . $result['token']
        );
    }
}