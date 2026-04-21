<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * All root categories with their children.
     * Used for navigation menus.
     */
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->roots()
            ->with(['children' => fn($q) => $q->active()->orderBy('sort_order')])
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Single category with its children.
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::active()
            ->where('slug', $slug)
            ->with([
                'parent',
                'children' => fn($q) => $q->active(),
            ])
            ->firstOrFail();

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }
}