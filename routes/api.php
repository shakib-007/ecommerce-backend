<?php
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Public Auth Routes ───────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login',    LoginController::class);

        // Google OAuth
        Route::get('google',          [GoogleAuthController::class, 'redirect']);
        Route::get('google/callback', [GoogleAuthController::class, 'callback']);
    });

    // ─── Protected Routes (must be logged in) ─────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', LogoutController::class);

        // Current user profile
        Route::get('me', function () {
            return \App\Http\Resources\UserResource::make(
                request()->user()->load('addresses')
            );
        });

        // ─── Admin Only ───────────────────────────────────────────
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Admin routes will be added in later phases
            Route::get('test', fn() => response()->json(['message' => 'Admin access confirmed.']));
        });

    });

});