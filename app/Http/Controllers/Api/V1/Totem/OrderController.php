<?php

namespace App\Http\Controllers\Api\V1\Totem;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Filho;
use App\Services\OrderService;
use App\DTOs\CreateOrderDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Criar pedido via Totem (Filho autenticado ou visitante)
     * POST /api/v1/totem/orders
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'filho_id' => 'nullable|uuid|exists:filhos,id',
            'guest_name' => 'required_without:filho_id|nullable|string|max:255',
            'guest_document' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:20',
            'payment_method' => 'required_without:filho_id|nullable|in:pix,credito,debito,dinheiro',
            'notes' => 'nullable|string|max:300',
        ]);

        try {
            DB::beginTransaction();

            // Validar e calcular itens
            $items = [];
            $total = 0;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                // Verificar se é produto da cantina
                if (!$product || !$product->is_active || !in_array($product->location, ['cantina', 'ambos'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Produto não disponível no totem',
                        'product_id' => $item['product_id'],
                    ], 422);
                }

                // Verificar estoque
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Quantidade indisponível para '{$product->name}'",
                        'available' => $product->stock,
                    ], 422);
                }

                $subtotal = $product->price * $item['quantity'];
                $total += $subtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Se for filho, validar crédito
            if ($request->filled('filho_id')) {
                $filho = Filho::findOrFail($request->filho_id);

                if ($filho->status !== 'active') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cadastro inativo',
                    ], 403);
                }

                if ($filho->is_blocked) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Existem faturas em atraso. Procure a administração.',
                    ], 403);
                }

                if ($filho->credit_available < $total) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Crédito insuficiente',
                        'credit_available' => $filho->credit_available,
                        'order_total' => $total,
                    ], 422);
                }
            }

            // Criar pedido
            $order = $this->orderService->create(new CreateOrderDTO(
                filhoId: $request->filho_id,
                guestName: $request->guest_name,
                guestDocument: $request->guest_document,
                origin: 'totem',
                items: $items,
                paymentMethod: $request->payment_method,
                notes: $request->notes
            ));

            DB::commit();

            // Emitir evento para KDS
            event(new \App\Events\NewOrderCreated($order));

            return response()->json([
                'success' => true,
                'message' => 'Pedido realizado com sucesso!',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'status' => $order->status,
                    'estimated_time' => '10-15 min',
                    'items' => collect($items)->map(fn($i) => [
                        'name' => $i['product_name'],
                        'quantity' => $i['quantity'],
                        'subtotal' => $i['subtotal'],
                    ]),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pedido. Tente novamente.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Consultar status do pedido
     * GET /api/v1/totem/orders/{orderNumber}/status
     */
    public function status(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        $statusInfo = $this->getStatusInfo($order->status);

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $statusInfo['label'],
                'status_description' => $statusInfo['description'],
                'status_icon' => $statusInfo['icon'],
                'is_ready' => $order->status === 'ready',
                'is_completed' => $order->status === 'completed',
                'is_cancelled' => $order->status === 'cancelled',
                'created_at' => $order->created_at->format('H:i'),
                'estimated_time' => $this->getEstimatedTime($order),
            ],
        ]);
    }

    /**
     * Listar pedidos em andamento (painel do totem)
     * GET /api/v1/totem/orders/queue
     */
    public function queue(): JsonResponse
    {
        $orders = Order::query()
            ->where('origin', 'totem')
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
            ->whereDate('created_at', today())
            ->orderByRaw("CASE 
                WHEN status = 'ready' THEN 1 
                WHEN status = 'preparing' THEN 2 
                WHEN status = 'confirmed' THEN 3 
                ELSE 4 END")
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(function ($order) {
                return [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => $this->getStatusInfo($order->status)['label'],
                    'customer_name' => $order->filho?->name ?? $order->guest_name ?? 'Cliente',
                    'created_at' => $order->created_at->format('H:i'),
                ];
            });

        // Separar por status para exibição
        $ready = $orders->where('status', 'ready')->values();
        $preparing = $orders->whereIn('status', ['preparing', 'confirmed', 'pending'])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'ready' => $ready,
                'preparing' => $preparing,
            ],
        ]);
    }

    /**
     * Validar filho antes de iniciar pedido
     * POST /api/v1/totem/orders/validate-filho
     */
    public function validateFilho(Request $request): JsonResponse
    {
        $request->validate([
            'cpf' => 'required_without:qr_code|nullable|string',
            'qr_code' => 'required_without:cpf|nullable|string',
            'pin' => 'nullable|string|size:4',
        ]);

        $filho = null;

        if ($request->filled('qr_code')) {
            $filho = Filho::where('qr_code', $request->qr_code)->first();
        } elseif ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', $request->cpf);
            $filho = Filho::where('cpf', $cpf)->first();
        }

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Cadastro não encontrado',
            ], 404);
        }

        if ($filho->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cadastro inativo. Procure a administração.',
            ], 403);
        }

        if ($filho->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Existem pendências financeiras. Procure a administração.',
            ], 403);
        }

        // Validar PIN se configurado
        if ($filho->pin && $request->filled('pin')) {
            if ($filho->pin !== $request->pin) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN incorreto',
                ], 401);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $filho->id,
                'name' => $filho->name,
                'photo_url' => $filho->photo_url,
                'credit_available' => $filho->credit_available,
                'requires_pin' => (bool) $filho->pin,
            ],
        ]);
    }

    /**
     * Cancelar pedido (apenas se ainda não iniciou preparo)
     * POST /api/v1/totem/orders/{orderNumber}/cancel
     */
    public function cancel(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('origin', 'totem')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido já está em preparo e não pode ser cancelado',
            ], 422);
        }

        try {
            $this->orderService->cancelOrder($order, null, 'Cancelado pelo cliente no totem');

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pedido',
            ], 500);
        }
    }

    /**
     * Obter informações do status
     */
    private function getStatusInfo(string $status): array
    {
        return match ($status) {
            'pending' => [
                'label' => 'Recebido',
                'description' => 'Seu pedido foi recebido',
                'icon' => 'clock',
            ],
            'confirmed' => [
                'label' => 'Confirmado',
                'description' => 'Pedido confirmado, aguardando preparo',
                'icon' => 'check',
            ],
            'preparing' => [
                'label' => 'Preparando',
                'description' => 'Seu pedido está sendo preparado',
                'icon' => 'loader',
            ],
            'ready' => [
                'label' => 'PRONTO!',
                'description' => 'Retire seu pedido no balcão',
                'icon' => 'bell',
            ],
            'completed' => [
                'label' => 'Entregue',
                'description' => 'Pedido finalizado',
                'icon' => 'check-circle',
            ],
            'cancelled' => [
                'label' => 'Cancelado',
                'description' => 'Pedido foi cancelado',
                'icon' => 'x-circle',
            ],
            default => [
                'label' => $status,
                'description' => '',
                'icon' => 'help-circle',
            ],
        };
    }

    /**
     * Calcular tempo estimado
     */
    private function getEstimatedTime(Order $order): ?string
    {
        if (in_array($order->status, ['ready', 'completed', 'cancelled'])) {
            return null;
        }

        $minutesSinceCreation = $order->created_at->diffInMinutes(now());
        $baseTime = 12; // 12 minutos base

        $remaining = max(0, $baseTime - $minutesSinceCreation);

        if ($remaining === 0) {
            return 'Em breve';
        }

        return "~{$remaining} min";
    }
}