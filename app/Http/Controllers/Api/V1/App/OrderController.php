<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\PayOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\DTOs\CreateOrderDTO;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
    ) {}

    /**
     * Listar pedidos do filho autenticado
     * GET /api/v1/app/orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filho = $user->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não vinculado a um filho',
            ], 403);
        }

        $query = Order::where('filho_id', $filho->id)->with(['items.product', 'payment']);
        

        $filter = $request->input('filter', 'active');

        if($filter === 'active'){
            $query->where('is_invoiced', false)
                    ->where('customer_type', 'filho')
                    ->where('payment_method_chosen', 'carteira')
                    ->whereIn('status', ['delivered', 'ready']);
        }else {
            // Aba "Histórico" (all): Filtra por mês e ano específicos (Padrão: Mês/Ano atual)
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $query->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
        }

        // Ordena do mais recente para o mais antigo
        $orders = $query->orderBy('created_at', 'desc')->paginate(5);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Exibir detalhes de um pedido
     * GET /api/v1/app/orders/{order}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $filho = $user->filho;

        // Verificar ownership
        if ($order->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        $order->load(['items.product', 'payment']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Criar pedido (SEM processar pagamento)
     * POST /api/v1/app/orders
     * 
     * ✅ CORRIGIDO: Usa StoreOrderRequest unificado
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
       

       try {
            $user = $request->user();
            $filho = $user->filho;

            // Validar se filho pode comprar
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não vinculado a um filho',
                ], 403);
            }

            if (!$filho->can_purchase) {
                return response()->json([
                    'success' => false,
                    'message' => $filho->block_reason ?? 'Você está bloqueado para compras',
                    'error_code' => 'BLOCKED_FOR_PURCHASES',
                ], 403);
            }

            
            
            $orderDTO = CreateOrderDTO::fromRequest(
                data: $request->validated(),
                userId: $user->id
            );

            // Criar pedido
            $order = $this->orderService->createOrderForFilho(
                filho: $filho,
                data: $orderDTO,
            );

            // Obter métodos de pagamento disponíveis
            $availableMethods = $this->paymentService->getAvailablePaymentMethods($order);

            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso',
                'data' => new OrderResource($order),
                'available_payment_methods' => $availableMethods,
                'next_step' => [
                    'action' => 'select_payment',
                    'message' => 'Escolha o método de pagamento',
                    // ✅ Atualizado para novo fluxo
                    'route' => "/payment-method/{$order->id}",
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao criar pedido', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Processar pagamento do pedido
     * POST /api/v1/app/orders/{order}/pay
     * 
     * ✅ ATUALIZADO: Usa novo sistema de pagamentos
     */
    public function pay(PayOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $user = $request->user();
            $filho = $user->filho;

            // Verificar ownership
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            // Validar se pedido pode ser pago
            if ($order->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já foi pago',
                ], 422);
            }

            if ($order->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido foi cancelado',
                ], 422);
            }

            // Processar pagamento via PaymentService
            $paymentMethod = $request->validated()['payment_method'];
            $paymentData = [
                'card_token' => $request->validated()['card_token'] ?? null,
                'installments' => $request->validated()['installments'] ?? 1,
                'card_data' => $request->validated()['card_data'] ?? null,
            ];

            $result = $this->paymentService->processOrderPayment(
                $order,
                $paymentMethod,
                $paymentData
            );

            Log::info('Pagamento processado', [
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'success' => $result['success'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\App\Exceptions\InsufficientCreditException $e) {
            return $e->render();
        } catch (\App\Exceptions\PaymentException $e) {
            return $e->render();
        } catch (\Exception $e) {
            Log::error('Erro ao processar pagamento', [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar pedido
     * POST /api/v1/app/orders/{order}/cancel
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        try {
            $user = $request->user();
            $filho = $user->filho;

            // Verificar ownership
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $this->orderService->cancelOrder(
                $order,
                $request->input('reason', 'Cancelado pelo cliente')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado com sucesso',
                'data' => new OrderResource($order->fresh()),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar pedido', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Repetir pedido
     * POST /api/v1/app/orders/{order}/repeat
     */
    public function repeat(Request $request, Order $order): JsonResponse
    {
        try {
            $user = $request->user();
            $filho = $user->filho;

            // Verificar ownership
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            // Preparar items do pedido original
            $items = $order->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])->toArray();

            // Criar novo pedido
            $newOrder = $this->orderService->createOrderForFilho(
                filho: $filho,
                items: $items,
                origin: 'app',
                notes: "Repetição do pedido {$order->order_number}",
                createdById: $user->id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido repetido com sucesso',
                'data' => new OrderResource($newOrder),
                'available_payment_methods' => $this->paymentService->getAvailablePaymentMethods($newOrder),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao repetir pedido', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

   
    /**
     * Pedidos ativos
     * GET /api/v1/app/orders/active
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        $filho = $user->filho;

        $orders = Order::where('filho_id', $filho->id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Estatísticas
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
            'total_orders' => $filho->orders()->where('status', '!=', 'cancelled')->count(),
            'completed_orders' => $filho->orders()->where('status', 'completed')->count(),
            'total_spent' => $filho->orders()->where('status', 'completed')->sum('total'),
            'average_ticket' => $filho->orders()->where('status', 'completed')->avg('total') ?? 0,
            'this_month' => [
                'orders' => $filho->orders()
                    ->where('status', '!=', 'cancelled')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'spent' => $filho->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', 'completed')
                    ->sum('total'),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'Aguardando Pagamento',
            'confirmed' => 'Confirmado',
            'preparing' => 'Em Preparação',
            'ready' => 'Pronto',
            'delivered' => 'Entregue',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            default => 'Desconhecido',
        };
    }

    private function getEstimatedTime(Order $order): ?string
    {
        if ($order->status === 'preparing') {
            return '10-15 minutos';
        }

        return null;
    }
}