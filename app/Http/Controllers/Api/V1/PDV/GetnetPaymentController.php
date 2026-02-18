<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Models\GetnetTransaction;
use App\Models\Order;
use App\Services\Payment\Getnet\GetnetCloudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Controller para pagamentos Getnet via PDV
 */
class GetnetPaymentController extends Controller
{
    public function __construct(
        private readonly GetnetCloudService $getnetService
    ) {}

    /**
     * Cria um pagamento no terminal Getnet
     * POST /api/v1/pdv/getnet/payments
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
            'payment_method' => ['required', Rule::in(['pix', 'credit_card', 'debit_card'])],
            'terminal_id' => 'required|string',
            'installments' => 'nullable|integer|min:1|max:12',
        ]);

        try {
            $order = Order::findOrFail($validated['order_id']);

            // Verifica se pedido já está pago
            if ($order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já está pago',
                ], 422);
            }

            // Verifica se já existe transação pendente
            $existingTransaction = GetnetTransaction::where('order_id', $order->id)
                ->pending()
                ->first();

            if ($existingTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe uma transação pendente para este pedido',
                    'data' => [
                        'transaction' => $existingTransaction->toApiResponse(),
                    ],
                ], 422);
            }

            // Valida parcelas apenas para crédito
            $installments = $validated['installments'] ?? 1;
            if ($validated['payment_method'] !== 'credit_card' && $installments > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parcelamento disponível apenas para cartão de crédito',
                ], 422);
            }

            // Cria pagamento no terminal
            $transaction = $this->getnetService->createTerminalPayment(
                order: $order,
                paymentMethod: $validated['payment_method'],
                terminalId: $validated['terminal_id'],
                installments: $installments
            );

            // Marca ordem como aguardando pagamento externo
            $order->update([
                'awaiting_external_payment' => true,
                'payment_method_chosen' => 'getnet_' . $validated['payment_method'],
                'payment_intent_id' => $transaction->payment_intent_id,
            ]);

            Log::info('PDV: Pagamento Getnet criado', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'terminal_id' => $validated['terminal_id'],
                'method' => $validated['payment_method'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento enviado para o terminal',
                'data' => [
                    'transaction' => $transaction->toApiResponse(),
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total' => $order->total,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('PDV: Erro ao criar pagamento Getnet', [
                'error' => $e->getMessage(),
                'order_id' => $validated['order_id'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Consulta status de uma transação
     * GET /api/v1/pdv/getnet/transactions/{transaction}
     */
    public function show(GetnetTransaction $transaction): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction->toApiResponse(),
            ],
        ]);
    }

    /**
     * Consulta status atualizado via API (polling fallback)
     * POST /api/v1/pdv/getnet/transactions/{transaction}/check-status
     */
    public function checkStatus(GetnetTransaction $transaction): JsonResponse
    {
        try {
            // Atualiza status consultando API Getnet
            $updatedTransaction = $this->getnetService->checkPaymentStatus($transaction);

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => $updatedTransaction->toApiResponse(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('PDV: Erro ao consultar status Getnet', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancela uma transação pendente
     * POST /api/v1/pdv/getnet/transactions/{transaction}/cancel
     */
    public function cancel(Request $request, GetnetTransaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            if (!$transaction->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transação não pode ser cancelada. Status atual: ' . $transaction->status,
                ], 422);
            }

            $reason = $validated['reason'] ?? 'Cancelado pelo operador';
            
            $this->getnetService->cancelTerminalPayment($transaction, $reason);

            // Remove flag de aguardando pagamento do pedido
            $transaction->order->update([
                'awaiting_external_payment' => false,
            ]);

            Log::info('PDV: Transação Getnet cancelada', [
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id,
                'reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transação cancelada com sucesso',
                'data' => [
                    'transaction' => $transaction->fresh()->toApiResponse(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('PDV: Erro ao cancelar transação Getnet', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista transações do dia
     * GET /api/v1/pdv/getnet/transactions
     */
    public function index(Request $request): JsonResponse
    {
        $query = GetnetTransaction::with(['order', 'pointDevice'])
            ->orderByDesc('created_at');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('terminal_id')) {
            $query->where('terminal_id', $request->terminal_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            // Por padrão, apenas do dia
            $query->today();
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->map(fn($t) => $t->toApiResponse()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Lista terminais disponíveis
     * GET /api/v1/pdv/getnet/terminals
     */
    public function terminals(): JsonResponse
    {
        $terminals = \App\Models\PointDevice::enabledForPdv()
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->device_id,
                    'name' => $device->getDisplayName(),
                    'status' => $device->status,
                    'is_online' => $device->is_online,
                    'location' => $device->location,
                    'last_communication_at' => $device->last_communication_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $terminals,
        ]);
    }
}