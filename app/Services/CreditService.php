<?php

namespace App\Services;

use App\Models\Filho;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated 2.0 Use CreditConsumptionService e CreditRestorationService
 * Este service será removido na versão 3.0
 * 
 * Migração:
 * - credit() → CreditRestorationService->restoreCredit()
 * - debit() → CreditConsumptionService->consumeLimit()
 * - getAvailableCredit() → CreditConsumptionService->getBalance()
 */

class CreditService
{
    /**
     * Adicionar crédito ao filho
     */
    public function credit(
        Filho $filho,
        float $amount,
        string $description,
        ?string $orderId = null,
        ?int $userId = null
    ): CreditTransaction {
        return DB::transaction(function () use ($filho, $amount, $description, $orderId, $userId) {
            $balanceBefore = $filho->credit_used;
            
            // Reduzir crédito utilizado (aumenta disponível)
            $filho->decrement('credit_used', $amount);
            $filho->refresh();

            return CreditTransaction::create([
                'filho_id' => $filho->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $filho->credit_used,
                'description' => $description,
                'order_id' => $orderId,
                'created_by_user_id' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Debitar crédito do filho
     */
    public function debit(
        Filho $filho,
        float $amount,
        string $description,
        ?string $orderId = null,
        ?int $userId = null
    ): CreditTransaction {
        return DB::transaction(function () use ($filho, $amount, $description, $orderId, $userId) {
            $balanceBefore = $filho->credit_used;
            
            // Aumentar crédito utilizado (reduz disponível)
            $filho->increment('credit_used', $amount);
            $filho->refresh();

            return CreditTransaction::create([
                'filho_id' => $filho->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $filho->credit_used,
                'description' => $description,
                'order_id' => $orderId,
                'created_by_user_id' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Adicionar crédito manualmente (ajuste administrativo)
     */
    public function addCredit(
        Filho $filho,
        float $amount,
        string $reason,
        ?int $userId = null
    ): CreditTransaction {
        return $this->credit(
            filho: $filho,
            amount: $amount,
            description: "[Ajuste Manual] {$reason}",
            userId: $userId
        );
    }

    /**
     * Debitar crédito manualmente (ajuste administrativo)
     */
    public function debitCredit(
        Filho $filho,
        float $amount,
        string $reason,
        ?int $userId = null
    ): CreditTransaction {
        return $this->debit(
            filho: $filho,
            amount: $amount,
            description: "[Ajuste Manual] {$reason}",
            userId: $userId
        );
    }

    /**
     * Verificar se filho tem crédito suficiente
     */
    public function hasAvailableCredit(Filho $filho, float $amount): bool
    {
        return $filho->credit_available >= $amount;
    }

    /**
     * Obter crédito disponível
     */
    public function getAvailableCredit(Filho $filho): float
    {
        return $filho->credit_available;
    }

    /**
     * Atualizar limite de crédito
     */
    public function updateCreditLimit(Filho $filho, float $newLimit, ?int $userId = null): void
    {
        $oldLimit = $filho->credit_limit;
        
        $filho->update(['credit_limit' => $newLimit]);

        CreditTransaction::create([
            'filho_id' => $filho->id,
            'type' => 'limit_change',
            'amount' => 0,
            'balance_before' => $oldLimit,
            'balance_after' => $newLimit,
            'description' => "Limite alterado de R$ " . number_format($oldLimit, 2, ',', '.') . 
                           " para R$ " . number_format($newLimit, 2, ',', '.'),
            'created_by_user_id' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Resetar crédito utilizado (início do mês)
     */
    public function resetMonthlyCredit(Filho $filho): void
    {
        if ($filho->credit_used > 0) {
            $this->credit(
                filho: $filho,
                amount: $filho->credit_used,
                description: "Reset mensal de crédito"
            );
        }
    }

    /**
     * Obter histórico de transações
     */
    public function getTransactionHistory(Filho $filho, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $filho->creditTransactions()
            ->with('createdByUser')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obter resumo de crédito
     */
    public function getCreditSummary(Filho $filho): array
    {
        $thisMonth = $filho->creditTransactions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        return [
            'credit_limit' => $filho->credit_limit,
            'credit_used' => $filho->credit_used,
            'credit_available' => $filho->credit_available,
            'usage_percentage' => $filho->credit_limit > 0 
                ? round(($filho->credit_used / $filho->credit_limit) * 100, 1) 
                : 0,
            'this_month_debits' => (clone $thisMonth)->where('type', 'debit')->sum('amount'),
            'this_month_credits' => (clone $thisMonth)->where('type', 'credit')->sum('amount'),
            'transactions_count' => (clone $thisMonth)->count(),
        ];
    }
}