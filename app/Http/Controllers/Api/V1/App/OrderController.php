<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderFromAppRequest;
use App\Http\Resources\OrderResource;
use App\DTOs\CreateOrderDTO;
use App\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para pedidos no App dos Filhos
 * 
 * Responsável por:
 * - Listar pedidos do filho
 * - Criar novos pedidos
 * - Acompanhar status
 * - Cancelar pedidos
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Listar pedidos do filho autenticado
     * GET /api/v1/app/orders
     */
    public function index(Request $request): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $query = $filho->orders()
            ->with(['items.product'])
            ->orderByDesc('created_at');

        // Filtros opcionais
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $perPage = min($request->input('per_page', 15), 50);
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
     * GET /api/v1/app/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho || $order->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        $order->load(['items.product.category']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Criar novo pedido (via App)
     * POST /api/v1/app/orders
     * 
     * ESTE É O MÉTODO PRINCIPAL PARA CRIAÇÃO DE PEDIDOS
     */
    public function store(StoreOrderFromAppRequest $request): JsonResponse
    {
        
        
            $filho = auth()->user()->filho;

            \Log::info(userId: auth()->id());

            \Log::debug('Request: '.$request);

            // Criar DTO a partir do request validado
            $dto = CreateOrderDTO::fromRequest(
                $request->validated(), auth()->id()
            );

            // Criar pedido via service
            $order = $this->orderService->create($dto);

            // Recarregar filho para pegar crédito atualizado
            $filho->refresh();

            // Preparar resposta de sucesso
            return response()->json([
                'success' => true,
                'message' => 'Pedido realizado com sucesso',
                'data' => [
                    'order' => new OrderResource($order),
                    'credit_info' => $this->getCreditInfo($filho),
                    'next_action' => $this->getNextAction($order),
                ],
            ], 201);

        try {

        } catch (\App\Exceptions\InsufficientCreditException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Crédito insuficiente para esta compra',
                'error_code' => 'INSUFFICIENT_CREDIT',
                'data' => [
                    'credit_available' => $filho->credit_available,
                    'order_total' => $dto->total ?? 0,
                    'difference' => max(0, ($dto->total ?? 0) - $filho->credit_available),
                    'suggestions' => [
                        'Remova alguns itens do carrinho',
                        'Entre em contato com a administração',
                    ],
                ],
            ], 422);

        } catch (\App\Exceptions\FilhoBlockedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'FILHO_BLOCKED',
                'data' => [
                    'overdue_invoices' => $filho->total_overdue_invoices,
                    'max_allowed' => $filho->max_overdue_invoices,
                    'is_blocked_by_debt' => $filho->is_blocked_by_debt,
                    'action_required' => 'Regularize suas faturas para continuar comprando',
                    'contact' => 'Procure a administração',
                ],
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
            // Log do erro
            \Log::error('Erro ao criar pedido no app', [
                'user_id' => auth()->id(),
                'filho_id' => $filho->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido. Tente novamente.',
                'error_code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Cancelar pedido
     * POST /api/v1/app/orders/{order}/cancel
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho || $order->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido não pode mais ser cancelado',
                'data' => [
                    'current_status' => $order->status,
                    'can_cancel' => false,
                ],
            ], 422);
        }

        try {
            $this->orderService->cancel(
                order: $order,
                reason: $request->input('reason', 'Cancelado pelo cliente via app')
            );

            // Recarregar filho para pegar crédito atualizado
            $filho->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado com sucesso',
                'data' => [
                    'order' => new OrderResource($order->fresh()),
                    'credit_info' => $this->getCreditInfo($filho),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Acompanhar status do pedido
     * GET /api/v1/app/orders/{order}/track
     */
    public function track(Order $order): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho || $order->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        $statusHistory = $this->buildStatusHistory($order);

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'current_status' => $order->status,
                'current_status_label' => $this->getStatusLabel($order->status),
                'is_cancelled' => $order->status === 'cancelled',
                'can_cancel' => in_array($order->status, ['pending', 'confirmed']),
                'status_history' => $statusHistory,
                'estimated_time' => $this->getEstimatedTime($order),
            ],
        ]);
    }

    /**
     * Pedidos ativos (não finalizados)
     * GET /api/v1/app/orders/active
     */
    public function active(): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $activeOrders = $filho->orders()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($activeOrders),
        ]);
    }

    /**
     * Estatísticas de compras do filho
     * GET /api/v1/app/orders/stats
     */
    public function stats(): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de filho não encontrado',
            ], 404);
        }

        $stats = [
            'total_orders' => $filho->orders()->count(),
            'completed_orders' => $filho->orders()->where('status', 'completed')->count(),
            'total_spent' => $filho->orders()->where('status', 'completed')->sum('total'),
            'average_ticket' => $filho->orders()->where('status', 'completed')->avg('total') ?? 0,
            'this_month' => [
                'orders' => $filho->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'spent' => $filho->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', 'completed')
                    ->sum('total'),
            ],
            'favorite_products' => $this->getFavoriteProducts($filho),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Repetir pedido anterior
     * POST /api/v1/app/orders/{order}/repeat
     */
    public function repeat(Order $order): JsonResponse
    {
        $filho = auth()->user()->filho;

        if (!$filho || $order->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        $items = $order->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        $request = new Request(['items' => $items]);
        $request->setUserResolver(fn() => auth()->user());

        return $this->store(new StoreOrderFromAppRequest($request->all()));
    }

    // ============================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ============================================

    /**
     * Obter informações de crédito do filho
     */
    private function getCreditInfo($filho): array
    {
        return [
            'credit_limit' => (float) $filho->credit_limit,
            'credit_used' => (float) $filho->credit_used,
            'credit_available' => (float) $filho->credit_available,
            'usage_percent' => (float) $filho->credit_usage_percent,
        ];
    }

    /**
     * Obter próxima ação após criar pedido
     */
    private function getNextAction(Order $order): array
    {
        return [
            'type' => 'track_order',
            'message' => 'Acompanhe seu pedido',
            'tracking_url' => route('api.v1.app.orders.track', ['order' => $order->id]),
        ];
    }

    /**
     * Construir histórico de status
     */
    private function buildStatusHistory(Order $order): array
    {
        return [
            ['status' => 'pending', 'label' => 'Pedido recebido', 'completed' => true, 'time' => $order->created_at],
            ['status' => 'confirmed', 'label' => 'Pedido confirmado', 'completed' => in_array($order->status, ['confirmed', 'preparing', 'ready', 'delivered', 'completed']), 'time' => null],
            ['status' => 'preparing', 'label' => 'Em preparo', 'completed' => in_array($order->status, ['preparing', 'ready', 'delivered', 'completed']), 'time' => $order->preparing_at],
            ['status' => 'ready', 'label' => 'Pronto para retirada', 'completed' => in_array($order->status, ['ready', 'delivered', 'completed']), 'time' => $order->ready_at],
            ['status' => 'completed', 'label' => 'Entregue', 'completed' => $order->status === 'completed', 'time' => $order->completed_at],
        ];
    }

    /**
     * Obter label do status
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Aguardando confirmação',
            'confirmed' => 'Confirmado',
            'preparing' => 'Em preparo',
            'ready' => 'Pronto para retirada',
            'delivered' => 'Entregue',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            default => $status,
        };
    }

    /**
     * Calcular tempo estimado
     */
    private function getEstimatedTime(Order $order): ?string
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return null;
        }

        $minutesRemaining = match ($order->status) {
            'pending' => 15,
            'confirmed' => 12,
            'preparing' => 8,
            'ready' => 0,
            default => null,
        };

        if ($minutesRemaining === null) {
            return null;
        }

        if ($minutesRemaining === 0) {
            return 'Pronto!';
        }

        return "~{$minutesRemaining} min";
    }

    /**
     * Obter produtos favoritos do filho
     */
    private function getFavoriteProducts($filho): array
    {
        return \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.filho_id', $filho->id)
            ->where('orders.status', 'completed')
            ->select('products.id', 'products.name', 'products.image_url')
            ->selectRaw('sum(order_items.quantity) as total_quantity')
            ->selectRaw('count(*) as times_ordered')
            ->groupBy('products.id', 'products.name', 'products.image_url')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->toArray();
    }
}