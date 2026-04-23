<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\StoreVariantRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Admin\AdminProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function __construct(
        private AdminProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list($request->all());

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->validated(),
            $request->file('images', [])
        );

        return response()->json([
            'message' => 'Product created successfully.',
            'data'    => new ProductDetailResource($product),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::withTrashed()
            ->with([
                'category',
                'brand',
                'variants.attributeValues.group',
                'images',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => new ProductDetailResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product = $this->productService->update($product, $request->validated());

        return response()->json([
            'message' => 'Product updated.',
            'data'    => new ProductDetailResource($product),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted.']);
    }

    public function restore(string $id): JsonResponse
    {
        $product = $this->productService->restore($id);

        return response()->json([
            'message' => 'Product restored.',
            'data'    => new ProductDetailResource($product),
        ]);
    }

    // ── Variant management ────────────────────────────────────────

    public function addVariant(StoreVariantRequest $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $variant = $this->productService->addVariant($product, $request->validated());

        return response()->json([
            'message' => 'Variant added.',
            'data'    => $variant,
        ], 201);
    }

    public function updateVariantStock(Request $request, string $variantId): JsonResponse
    {
        $request->validate(['stock_qty' => ['required', 'integer', 'min:0']]);

        $variant = ProductVariant::findOrFail($variantId);
        $variant = $this->productService->updateVariantStock(
            $variant,
            $request->stock_qty
        );

        return response()->json([
            'message'   => 'Stock updated.',
            'stock_qty' => $variant->stock_qty,
        ]);
    }

    public function deleteVariant(string $variantId): JsonResponse
    {
        $variant = ProductVariant::findOrFail($variantId);

        // Prevent deleting variant that has order history
        if ($variant->orderItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete variant with order history. Deactivate it instead.',
            ], 422);
        }

        $variant->delete();

        return response()->json(['message' => 'Variant deleted.']);
    }

    // ── Image management ──────────────────────────────────────────

    public function uploadImages(Request $request, string $productId): JsonResponse
    {
        $request->validate([
            'images'        => ['required', 'array'],
            'images.*'      => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'primary_image' => ['nullable', 'integer'],
        ]);

        $product = Product::findOrFail($productId);

        $this->productService->storeImages(
            $product,
            $request->file('images'),
            $request->primary_image ?? 0
        );

        return response()->json([
            'message' => 'Images uploaded.',
            'images'  => $product->fresh('images')->images,
        ]);
    }

    public function deleteImage(string $imageId): JsonResponse
    {
        $image = ProductImage::findOrFail($imageId);
        $this->productService->deleteImage($image);

        return response()->json(['message' => 'Image deleted.']);
    }
}