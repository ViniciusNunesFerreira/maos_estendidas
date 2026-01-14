<?php
// app/Services/Filho/BalanceService.php

namespace App\Services\Filho;

use App\Events\FilhoBalanceUpdated;
use App\Models\Filho;
use App\Models\Order;

class BalanceService
{
    public function credit(
        Filho $filho,
        float $amount,
        string $description,
        ?string $type = 'credit'
    ): void {
        $transaction = $filho->credit($amount, $description, $type);
        
        event(new FilhoBalanceUpdated($filho, $transaction));
    }

    public function debit(
        Filho $filho,
        float $amount,
        string $description,
        ?Order $order = null,
        ?string $type = 'debit'
    ): void {
        $transaction = $filho->debit($amount, $description, $type);
        
        if ($order) {
            $transaction->update(['order_id' => $order->id]);
        }
        
        event(new FilhoBalanceUpdated($filho, $transaction));
    }

    public function refund(
        Filho $filho,
        float $amount,
        string $description,
        ?Order $order = null
    ): void {
        $this->credit($filho, $amount, $description, 'refund');
    }

    public function processMensalidade(Filho $filho): void
    {
        if (!$filho->mensalidade_active) {
            return;
        }

        // Creditar mensalidade
        $this->credit(
            $filho,
            $filho->mensalidade_amount,
            "Crédito mensalidade - Mês " . now()->format('m/Y'),
            'mensalidade_credit'
        );
    }

    public function debitMensalidade(Filho $filho, float $amount): void
    {
        $this->debit(
            $filho,
            $amount,
            "Débito mensalidade - Mês " . now()->format('m/Y'),
            null,
            'mensalidade_debit'
        );
    }
}