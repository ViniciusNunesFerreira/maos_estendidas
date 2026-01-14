<?php
// app/Http/Controllers/Api/V1/Kitchen/OrderController.php

namespace App\Http\Controllers\Api\V1\Kitchen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function pending(): JsonResponse
    {
        $orders = Order::with(['items.product', 'filho.user'])
            ->whereIn('status', ['paid'])
            ->oldest()
            ->get();

        return response()->json(OrderResource::collection($orders));
    }

    public function preparing(): JsonResponse
    {
        $orders = Order::with(['items.product', 'filho.user'])
            ->where('status', 'preparing')
            ->oldest('preparing_at')
            ->get();

        return response()->json(OrderResource::collection($orders));
    }

    public function ready(): JsonResponse
    {
        $orders = Order::with(['items.product', 'filho.user'])
            ->where('status', 'ready')
            ->oldest('ready_at')
            ->get();

        return response()->json(OrderResource::collection($orders));
    }

    public function accept(Order $order): JsonResponse
    {
        $order = $this->orderService->updateStatus($order, 'preparing');

        return response()->json(new OrderResource($order));
    }

    public function markReady(Order $order): JsonResponse
    {
        $order = $this->orderService->updateStatus($order, 'ready');

        return response()->json(new OrderResource($order));
    }

    public function markDelivered(Order $order): JsonResponse
    {
        $order = $this->orderService->updateStatus($order, 'delivered');

        return response()->json(new OrderResource($order));
    }
}