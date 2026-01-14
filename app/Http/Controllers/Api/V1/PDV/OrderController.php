<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderFromPDVRequest;
use App\Http\Resources\OrderResource;
use App\DTOs\CreateOrderDTO;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para pedidos no PDV Desktop
 * 
 * Gerencia tanto vendas para filhos quanto para visitantes
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Listar pedidos do PDV
     * GET /api/v1/pdv/orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items.product', 'filho.user'])
            ->orderByDesc('created_at');

        // Filtros
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Exibir pedido específico
     * GET /api/v1/pdv/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items.product', 'filho.user', 'createdBy']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Criar novo pedido via PDV
     * POST /api/v1/pdv/orders
     * 
     * Suporta:
     * - Vendas para filhos (crédito)
     * - Vendas para visitantes (pagamento imediato)
     */
    public function store(StoreOrderFromPDVRequest $request): JsonResponse
    {
        try {
            // Criar DTO
            $dto = CreateOrderDTO::fromRequest(
                data: $request->validated(),
                userId: auth()->id()
            );

            // Criar pedido
            $order = $this->orderService->create($dto);

            // Preparar resposta baseado no tipo de cliente
            $responseData = [
                'order' => new OrderResource($order),
            ];

            // Se filho, incluir info de crédito
            if ($dto->isFilho() && $order->filho) {
                $filho = $order->filho->fresh();
                $responseData['credit_info'] = [
                    'credit_limit' => (float) $filho->credit_limit,
                    'credit_used' => (float) $filho->credit_used,
                    'credit_available' => (float) $filho->credit_available,
                    'usage_percent' => (float) $filho->credit_usage_percent,
                ];
            }

            // Se visitante, confirmar pagamento
            if ($dto->isGuest()) {
                $responseData['payment'] = [
                    'method' => $request->payment_method,
                    'amount' => $request->payment_amount,
                    'status' => 'paid',
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso',
                'data' => $responseData,
            ], 201);

        } catch (\App\Exceptions\InsufficientCreditException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INSUFFICIENT_CREDIT',
            ], 422);

        } catch (\App\Exceptions\FilhoBlockedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'FILHO_BLOCKED',
            ], 403);

        } catch (\App\Exceptions\InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INSUFFICIENT_STOCK',
            ], 422);

        } catch (\App\Exceptions\ProductNotAvailableException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PRODUCT_NOT_AVAILABLE',
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Erro ao criar pedido no PDV', [
                'user_id' => auth()->id(),
                'device_id' => $request->device_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Cancelar pedido
     * POST /api/v1/pdv/orders/{order}/cancel
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->orderService->cancel(
                order: $order,
                reason: $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado com sucesso',
                'data' => new OrderResource($order->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}