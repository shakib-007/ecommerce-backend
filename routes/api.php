<?php
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Shop\ProductController;
use App\Http\Controllers\Api\V1\Shop\CategoryController;
use App\Http\Controllers\Api\V1\Shop\BrandController;
use App\Http\Controllers\Api\V1\Shop\CartController;
use App\Http\Controllers\Api\V1\Checkout\AddressController;
use App\Http\Controllers\Api\V1\Checkout\OrderController;
use App\Http\Controllers\Api\V1\Payment\PaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminCouponController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Public Auth Routes ───────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);

        // Google OAuth
        Route::get('google', [GoogleAuthController::class, 'redirect']);
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

            Route::post('orders/{orderId}/confirm-cod', [AdminPaymentController::class, 'confirmCod']);
            Route::post('orders/{orderId}/refund', [AdminPaymentController::class, 'refund']);

            // Dashboard
            Route::get('dashboard', [AdminDashboardController::class, 'index']);

            // Products
            Route::get('products',                    [AdminProductController::class, 'index']);
            Route::post('products',                   [AdminProductController::class, 'store']);
            Route::get('products/{id}',               [AdminProductController::class, 'show']);
            Route::put('products/{id}',               [AdminProductController::class, 'update']);
            Route::delete('products/{id}',            [AdminProductController::class, 'destroy']);
            Route::post('products/{id}/restore',      [AdminProductController::class, 'restore']);
            Route::post('products/{id}/images',       [AdminProductController::class, 'uploadImages']);
            Route::delete('images/{imageId}',         [AdminProductController::class, 'deleteImage']);
            Route::post('products/{id}/variants',     [AdminProductController::class, 'addVariant']);
            Route::delete('variants/{id}',            [AdminProductController::class, 'deleteVariant']);
            Route::patch('variants/{id}/stock',       [AdminProductController::class, 'updateVariantStock']);

            // Categories
            Route::get('categories',        [AdminCategoryController::class, 'index']);
            Route::post('categories',       [AdminCategoryController::class, 'store']);
            Route::put('categories/{id}',   [AdminCategoryController::class, 'update']);
            Route::delete('categories/{id}',[AdminCategoryController::class, 'destroy']);

            // Brands
            Route::get('brands',                    [AdminBrandController::class, 'index']);
            Route::post('brands',                   [AdminBrandController::class, 'store']);
            Route::put('brands/{id}',               [AdminBrandController::class, 'update']);
            Route::delete('brands/{id}',            [AdminBrandController::class, 'destroy']);
            Route::patch('brands/{id}/toggle',      [AdminBrandController::class, 'toggleStatus']);

            // Orders
            Route::get('orders',                         [AdminOrderController::class, 'index']);
            Route::get('orders/{id}',                    [AdminOrderController::class, 'show']);
            Route::patch('orders/{id}/status',           [AdminOrderController::class, 'updateStatus']);
            Route::post('orders/{id}/confirm-cod',       [AdminPaymentController::class, 'confirmCod']);
            Route::post('orders/{id}/refund',            [AdminPaymentController::class, 'refund']);

            // Users
            Route::get('users',                     [AdminUserController::class, 'index']);
            Route::get('users/{id}',                [AdminUserController::class, 'show']);
            Route::patch('users/{id}/toggle',       [AdminUserController::class, 'toggleStatus']);

            // Coupons
            Route::get('coupons',                   [AdminCouponController::class, 'index']);
            Route::post('coupons',                  [AdminCouponController::class, 'store']);
            Route::patch('coupons/{id}/toggle',     [AdminCouponController::class, 'toggleStatus']);
            Route::delete('coupons/{id}',           [AdminCouponController::class, 'destroy']);

            // Settings
            Route::get('settings',                  [AdminSettingController::class, 'index']);
            Route::put('settings',                  [AdminSettingController::class, 'update']);

        });

        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::get('/count', [CartController::class, 'count']);
            Route::post('/items', [CartController::class, 'addItem']);
            Route::put('/items/{id}', [CartController::class, 'updateItem']);
            Route::delete('/items/{id}', [CartController::class, 'removeItem']);
            Route::delete('/', [CartController::class, 'clear']);
        });

        // Addresses
        Route::prefix('addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index']);
            Route::post('/', [AddressController::class, 'store']);
            Route::put('/{id}', [AddressController::class, 'update']);
            Route::delete('/{id}', [AddressController::class, 'destroy']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/{id}', [OrderController::class, 'show']);
        });

        // Initiate payment after order placed
        Route::post('payment/initiate/{orderId}', [PaymentController::class, 'initiate']);

    });

    // ─── Public Shop Routes ───────────────────────────────────────────
    Route::prefix('shop')->group(function () {

        // Categories
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{slug}', [CategoryController::class, 'show']);

        // Brands
        Route::get('brands', [BrandController::class, 'index']);

        // Products
        // IMPORTANT: 'featured' route must come before '{slug}'
        // otherwise Laravel will treat "featured" as a slug
        Route::get('products/featured', [ProductController::class, 'featured']);
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{slug}', [ProductController::class, 'show']);

    });

    Route::prefix('payment/sslcommerz')->group(function () {
        Route::post('success', [PaymentController::class, 'sslSuccess']);
        Route::post('fail', [PaymentController::class, 'sslFail']);
        Route::post('cancel', [PaymentController::class, 'sslCancel']);
        Route::post('ipn', [PaymentController::class, 'sslIpn']);
    });
});