<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * GET /api/v1/wishlist
     * Get all wishlist items for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with([
                'variant.product.images',
                'variant.attributeValues.group',
            ])
            ->latest()
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/v1/wishlist
     * Add a variant to wishlist.
     * Uses firstOrCreate so adding twice has no effect.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => [
                'required',
                'uuid',
                'exists:product_variants,id',
            ],
        ]);

        Wishlist::firstOrCreate([
            'user_id'    => $request->user()->id,
            'variant_id' => $request->variant_id,
        ]);

        return response()->json([
            'message' => 'Added to wishlist.',
        ], 201);
    }

    /**
     * DELETE /api/v1/wishlist/{variantId}
     * Remove a variant from wishlist.
     */
    public function destroy(Request $request, string $variantId): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)
            ->where('variant_id', $variantId)
            ->firstOrFail()
            ->delete();

        return response()->json([
            'message' => 'Removed from wishlist.',
        ]);
    }

    /**
     * GET /api/v1/wishlist/check/{variantId}
     * Check if a variant is in the user's wishlist.
     * Used by the product detail page heart icon.
     */
    public function check(Request $request, string $variantId): JsonResponse
    {
        $exists = Wishlist::where('user_id', $request->user()->id)
            ->where('variant_id', $variantId)
            ->exists();

        return response()->json(['in_wishlist' => $exists]);
    }
}