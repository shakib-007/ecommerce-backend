<?php
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Shop\ProductController;
use App\Http\Controllers\Api\V1\Shop\CategoryController;
use App\Http\Controllers\Api\V1\Shop\BrandController;
use App\Http\Controllers\Api\V1\Shop\CartController;
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

        Route::prefix('cart')->group(function () {
            Route::get('/',              [CartController::class, 'index']);
            Route::get('/count',         [CartController::class, 'count']);
            Route::post('/items',        [CartController::class, 'addItem']);
            Route::put('/items/{id}',    [CartController::class, 'updateItem']);
            Route::delete('/items/{id}', [CartController::class, 'removeItem']);
            Route::delete('/',           [CartController::class, 'clear']);
        });

    });

    // ─── Public Shop Routes ───────────────────────────────────────────
    Route::prefix('shop')->group(function () {

        // Categories
        Route::get('categories',        [CategoryController::class, 'index']);
        Route::get('categories/{slug}', [CategoryController::class, 'show']);

        // Brands
        Route::get('brands', [BrandController::class, 'index']);

        // Products
        // IMPORTANT: 'featured' route must come before '{slug}'
        // otherwise Laravel will treat "featured" as a slug
        Route::get('products/featured', [ProductController::class, 'featured']);
        Route::get('products',          [ProductController::class, 'index']);
        Route::get('products/{slug}',   [ProductController::class, 'show']);

    });
});