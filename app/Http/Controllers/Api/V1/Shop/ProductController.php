<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ProductFilterRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    /**
     * Paginated product listing with filters.
     * GET /api/v1/products
     */
    public function index(ProductFilterRequest $request): JsonResponse
    {
        $products = $this->productService->getFilteredProducts(
            $request->validated()
        );

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
                'from'         => $products->firstItem(),
                'to'           => $products->lastItem(),
            ],
            'links' => [
                'next' => $products->nextPageUrl(),
                'prev' => $products->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Single product detail page.
     * GET /api/v1/products/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

        $related = $this->productService->getRelatedProducts($product);

        return response()->json([
            'data'    => new ProductDetailResource($product),
            'related' => ProductResource::collection($related),
        ]);
    }

    /**
     * Featured products for homepage.
     * GET /api/v1/products/featured
     */
    public function featured(): JsonResponse
    {
        $products = $this->productService->getFilteredProducts([
            'featured' => true,
            'per_page' => 8,
        ]);

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }
}