<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditTransactionResource;
use App\Models\CreditTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    /**
     * Exibir saldo atual do filho
     * 
     * GET /api/v1/app/balance
     */
    public function show(Request $request): JsonResponse
    {
        $filho = $request->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Filho não encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                // Informações de crédito
                'credit_limit' => (float) $filho->credit_limit,
                'available_credit' => (float) $filho->credit_available,
                'used_credit' => (float) ($filho->credit_limit - $filho->credit_available),
                'credit_percentage_used' => $filho->credit_limit > 0 
                    ? round((($filho->credit_limit - $filho->credit_available) / $filho->credit_limit) * 100, 2)
                    : 0,
                
                
                // Status
                'status' => $filho->status,
                'is_blocked' => $filho->status === 'blocked',

            ],
        ]);
    }

    /**
     * Histórico de transações de crédito
     * 
     * GET /api/v1/app/balance/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $filho = $request->user()->filho;

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'Filho não encontrado',
            ], 404);
        }

        $query = CreditTransaction::query()
            ->where('filho_id', $filho->id)
            ->with(['order', 'invoice', 'createdByUser']);

        // Filtros
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Ordenação (mais recente primeiro)
        $query->orderByDesc('created_at');

        // Paginação
        $perPage = min($request->input('per_page', 15), 50);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CreditTransactionResource::collection($transactions),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'last_page' => $transactions->lastPage(),
            ],
            'summary' => [
                'total_purchases' => $filho->creditTransactions()
                    ->where('type', 'purchase')
                    ->sum('amount'),
                'total_payments' => $filho->creditTransactions()
                    ->where('type', 'payment')
                    ->sum('amount'),
                'total_adjustments' => $filho->creditTransactions()
                    ->whereIn('type', ['credit', 'debit', 'adjustment'])
                    ->sum('amount'),
            ],
        ]);
    }
}