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
    public function callback(): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google authentication failed. Please try again.',
            ], 422);
        }

        $result = $this->authService->handleGoogleUser($googleUser);

        // In production: redirect to frontend with token in URL
        // return redirect(env('FRONTEND_URL') . '/auth/callback?token=' . $result['token']);

        return response()->json([
            'message' => 'Google login successful.',
            'token'   => $result['token'],
            'user'    => UserResource::make($result['user']),
        ]);
    }
}