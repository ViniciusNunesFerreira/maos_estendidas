<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Services\CreditConsumptionService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller de Transações (App PWA)
 * 
 * ENDPOINTS:
 * - POST /consume-limit → Consumir crédito (pagar com saldo)
 * - GET /transactions → Extrato de transações
 * - GET /balance → Consultar saldo disponível
 * 
 * @version 2.0
 * @author Sistema Mãos Estendidas
 */
class TransactionController extends Controller
{
    public function __construct(
        protected CreditConsumptionService $creditService
    ) {}
    
    /**
     * Consumir limite de crédito (Compra com Saldo)
     * 
     * POST /api/v1/app/transactions/consume-limit
     * 
     * Body:
     * {
     *   "order_id": "uuid"
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function consumeLimit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
        ]);
        
        try {
            $order = Order::findOrFail($validated['order_id']);
            
            // ========== VALIDAR AUTORIZAÇÃO ==========
            
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está vinculado a um filho/aluno.',
                    'code' => 'USER_NOT_FILHO',
                ], 403);
            }
            
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido não pertence a você.',
                    'code' => 'UNAUTHORIZED_ORDER_ACCESS',
                ], 403);
            }
            
            // ========== PROCESSAR CONSUMO ==========
            
            $result = $this->creditService->consumeLimit($order);
            
            return response()->json($result, 200);
            
        } catch (\App\Exceptions\InsufficientCreditException $e) {
            return $e->render();
        } catch (\App\Exceptions\PaymentException $e) {
            return $e->render();
        } catch (\App\Exceptions\FilhoBlockedException $e) {
            return $e->render();
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao consumir crédito', [
                'order_id' => $validated['order_id'],
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao processar pagamento. Tente novamente.',
                'code' => 'UNEXPECTED_ERROR',
            ], 500);
        }
    }
    
    /**
     * Consultar extrato de transações
     * 
     * GET /api/v1/app/transactions?limit=50
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está vinculado a um filho/aluno.',
                    'code' => 'USER_NOT_FILHO',
                ], 403);
            }
            
            $limit = (int) $request->input('limit', 50);
            $limit = min($limit, 100); // Máximo 100 registros
            
            $transactions = $filho->transactions()
                ->with(['order:id,order_number'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'amount' => (float) $transaction->amount,
                        'balance_before' => (float) $transaction->balance_before,
                        'balance_after' => (float) $transaction->balance_after,
                        'description' => $transaction->description,
                        'notes' => $transaction->notes,
                        'created_at' => $transaction->created_at->toIso8601String(),
                        'order_number' => $transaction->order?->order_number,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $transactions,
                'count' => $transactions->count(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar extrato', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar extrato.',
                'code' => 'FETCH_TRANSACTIONS_FAILED',
            ], 500);
        }
    }
    
    /**
     * Consultar saldo disponível
     * 
     * GET /api/v1/app/transactions/balance
     * 
     * @return JsonResponse
     */
    public function getBalance(): JsonResponse
    {
        try {
            $filho = auth()->user()->filho;
            
            if (!$filho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não está vinculado a um filho/aluno.',
                    'code' => 'USER_NOT_FILHO',
                ], 403);
            }
            
            $result = $this->creditService->getBalance($filho->id);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Erro ao buscar saldo', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar saldo.',
                'code' => 'FETCH_BALANCE_FAILED',
            ], 500);
        }
    }
}