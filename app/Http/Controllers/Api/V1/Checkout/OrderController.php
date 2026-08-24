<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\GuestPlaceOrderRequest;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
    ) {}

    /**
     * GET /api/v1/orders
     * List authenticated user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getUserOrders($request->user());

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/orders
     * Place a new order from the cart.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Order placed successfully.',
            'data'    => new OrderDetailResource($order),
        ], 201);
    }

    /**
     * POST /api/v1/checkout/guest
     * Place an order without authentication.
     */
    public function storeGuest(GuestPlaceOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $order = $this->orderService->placeGuestOrder($validated);

        $payload = [
            'message'        => 'Order placed successfully.',
            'data'           => new OrderDetailResource($order),
            'payment_method' => $validated['payment_method'],
        ];

        if ($validated['payment_method'] === 'sslcommerz') {
            $result = $this->paymentService->initiatePayment($order->fresh(['latestPayment', 'address']));

            if (!$result->success) {
                return response()->json([
                    'message' => $result->message ?? 'Payment initiation failed.',
                    'data'    => new OrderDetailResource($order),
                ], 422);
            }

            $payload['redirect_url'] = $result->redirectUrl;
        }

        return response()->json($payload, 201);
    }

    /**
     * GET /api/v1/orders/{id}
     * Single order detail for the authenticated user.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $order = $this->orderService->getUserOrder($request->user(), $id);

        return response()->json([
            'data' => new OrderDetailResource($order),
        ]);
    }
}