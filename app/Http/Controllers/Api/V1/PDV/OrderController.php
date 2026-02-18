<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\DTOs\CreateOrderDTO;
use App\Services\OrderService;
use App\Models\Order;
use App\Models\Filho;
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
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $dto = CreateOrderDTO::fromRequest(
                data: $request->validated(),
                userId: auth()->id()
            );


            if ($dto->customerType === 'filho'  && $dto->payment_method === 'carteira') {
                // Buscamos apenas o modelo simples, o lock acontece dentro do Service
                $filho = Filho::findOrFail($dto->filhoId);
                $order = $this->orderService->createOrderForFilho($filho, $dto);
            }else{
                $order = $this->orderService->createOrderForGuest($dto);
            }

                // === IMPLEMENTAÇÃO DO FLUXO DE CAIXA ===
            $cashSession = \App\Models\CashSession::where('user_id', auth()->id())
                ->where('status', 'open')
                ->first();

            if ($cashSession) {
                $method = $dto->customerType === 'filho' && $order->payment_method_chosen === 'carteira' ? 'credito_interno' : $dto->payment_method;
                
                \App\Models\CashMovement::create([
                    'cash_session_id' => $cashSession->id,
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'type' => 'sale',
                    'amount' => $order->total, 
                    'payment_method' => $method, 
                    'description' => "Venda #{$order->order_number}" . ($dto->customerType === 'filho' ? ' (Filho)' : '')
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Venda realizada com sucesso',
                'data' => new OrderResource($order),
            ], 201);

        } catch (\App\Exceptions\InsufficientCreditException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'error_code' => 'INSUFFICIENT_CREDIT'], 422);
        } catch (\Exception $e) {
            \Log::error("Erro PDV: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno no servidor'], 500);
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