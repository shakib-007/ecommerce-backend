<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Http\Requests\Shop\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    /**
     * GET /api/v1/cart
     * Get current user's cart with all items.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCartWithItems($request->user());

        return response()->json([
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * POST /api/v1/cart/items
     * Add item to cart.
     */
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem(
            $request->user(),
            $request->variant_id,
            $request->qty,
        );

        return response()->json([
            'message' => 'Item added to cart.',
            'data'    => new CartResource($cart),
        ], 201);
    }

    /**
     * PUT /api/v1/cart/items/{cartItemId}
     * Update quantity of a cart item.
     */
    public function updateItem(
        UpdateCartItemRequest $request,
        string $cartItemId
    ): JsonResponse {
        $cart = $this->cartService->updateItem(
            $request->user(),
            $cartItemId,
            $request->qty,
        );

        return response()->json([
            'message' => 'Cart updated.',
            'data'    => new CartResource($cart),
        ]);
    }

    /**
     * DELETE /api/v1/cart/items/{cartItemId}
     * Remove a single item from cart.
     */
    public function removeItem(Request $request, string $cartItemId): JsonResponse
    {
        $cart = $this->cartService->removeItem(
            $request->user(),
            $cartItemId,
        );

        return response()->json([
            'message' => 'Item removed from cart.',
            'data'    => new CartResource($cart),
        ]);
    }

    /**
     * DELETE /api/v1/cart
     * Clear entire cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clearCart($request->user());

        return response()->json([
            'message' => 'Cart cleared.',
        ]);
    }

    /**
     * GET /api/v1/cart/count
     * Just the item count — for navbar badge.
     */
    public function count(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->cartService->getItemCount($request->user()),
        ]);
    }
}