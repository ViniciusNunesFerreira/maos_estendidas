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

        // Calcular total de faturas pendentes
        $pendingInvoices = $filho->invoices()
            ->whereIn('status', ['open', 'partial', 'overdue'])
            ->sum('remaining_amount');

        $overdueInvoices = $filho->invoices()
            ->where('status', 'overdue')
            ->sum('remaining_amount');

        // Assinatura pendente
        $subscriptionDebt = $filho->subscriptionInvoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                // Informações de crédito
                'credit_limit' => (float) $filho->credit_limit,
                'available_credit' => (float) $filho->available_credit,
                'used_credit' => (float) ($filho->credit_limit - $filho->available_credit),
                'credit_percentage_used' => $filho->credit_limit > 0 
                    ? round((($filho->credit_limit - $filho->available_credit) / $filho->credit_limit) * 100, 2)
                    : 0,
                
                // Informações de dívida
                'total_debt' => (float) ($pendingInvoices + $subscriptionDebt),
                'consumption_debt' => (float) $pendingInvoices,
                'subscription_debt' => (float) $subscriptionDebt,
                'overdue_amount' => (float) $overdueInvoices,
                
                // Status
                'status' => $filho->status,
                'can_purchase' => $filho->can_purchase,
                'is_blocked' => $filho->status === 'blocked',
                
                // Faturamento
                'overdue_invoices_count' => $filho->total_overdue_invoices,
                'max_overdue_allowed' => $filho->max_overdue_invoices,
                'billing_close_day' => $filho->billing_close_day,
                'next_billing_date' => $filho->next_billing_date?->format('Y-m-d'),
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