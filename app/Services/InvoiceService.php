<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Filho;
use App\Models\Order;
use App\Jobs\SendInvoiceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Gera faturas de consumo mensal para todos os filhos elegíveis.
     * Utiliza chunking para evitar estouro de memória.
     */
    public function generateMonthlyInvoices(?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now();
        $generatedCount = 0;
        $errors = [];

        // Dia atual para verificar o ciclo de fechamento
        $currentDay = $referenceDate->day;

        // Processa em lotes de 100 filhos para otimizar memória
        Filho::active()
            ->where('billing_close_day', $currentDay)
            ->chunkById(100, function ($filhos) use ($referenceDate, &$generatedCount, &$errors) {
                foreach ($filhos as $filho) {
                    try {
                        $invoice = $this->processInvoiceForFilho($filho, $referenceDate);
                        if ($invoice) {
                            $generatedCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Erro ao gerar fatura para Filho ID {$filho->id}: " . $e->getMessage();
                        Log::error("Invoice Generation Error: " . $e->getMessage());
                    }
                }
            });

        return [
            'generated_count' => $generatedCount,
            'errors' => $errors
        ];
    }

    /**
     * Processa o fechamento de fatura de consumo para um único filho.
     * Centraliza a lógica que antes estava duplicada.
     */
    public function processInvoiceForFilho(Filho $filho, Carbon $referenceDate): ?Invoice
    {
        // Define o intervalo de busca de pedidos (mês anterior ao fechamento ou ciclo personalizado)
        // Assumindo ciclo: dia X do mês anterior até dia X-1 deste mês
        $billingDay = $filho->billing_close_day ?? 28;
        $endDate = $referenceDate->copy()->day($billingDay)->endOfDay();
        $startDate = $endDate->copy()->subMonth()->addDay()->startOfDay();

        // Busca pedidos elegíveis (não faturados dentro do período)
        $orders = Order::where('filho_id', $filho->id)
            ->eligibleForInvoicing()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.product.category']) // Eager Loading para evitar N+1 na criação de itens
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($filho, $orders, $startDate, $endDate) {
            
            // 1. Cria o Cabeçalho da Fatura
            $dueDays = config('casalar.billing.invoice_due_days', 10);
            $dueDate = $endDate->copy()->addDays($dueDays);

            $invoice = Invoice::create([
                'filho_id' => $filho->id,
                'invoice_number' => Invoice::generateNextInvoiceNumber('consumption'),
                'type' => 'consumption',
                'period_start' => $startDate,
                'period_end' => $endDate,
                'issue_date' => now(),
                'due_date' => $dueDate,
                'status' => 'pending', // Fatura gerada, aguardando pgto
                'notes' => "Referente ao consumo de {$startDate->format('d/m')} a {$endDate->format('d/m')}",
            ]);

            $totalSubtotal = 0;
            $totalDiscount = 0;

            // 2. Processa os Itens e Vincula Pedidos
            foreach ($orders as $order) {
                // Atualiza o pedido para constar como faturado
                $order->update([
                    'invoice_id' => $invoice->id,
                    'is_invoiced' => true,
                    'invoiced_at' => now(),
                ]);

                // Transfere itens do pedido para itens da fatura (Snapshot)
                foreach ($order->items as $orderItem) {
                    $productName = $orderItem->product->name ?? $orderItem->product_name ?? 'Item Removido';
                    $categoryName = $orderItem->product->category->name ?? $orderItem->category ?? 'Geral';
                    $location = $order->origin ?? 'loja';

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'purchase_date' => $order->created_at->toDateString(),
                        'description' => $productName,
                        'category' => $categoryName,
                        'location' => $location,
                        'quantity' => $orderItem->quantity,
                        'unit_price' => $orderItem->unit_price,
                        'subtotal' => $orderItem->subtotal,
                        'discount_amount' => $orderItem->discount ?? 0,
                        'total' => $orderItem->total,
                    ]);

                    $totalSubtotal += $orderItem->subtotal;
                    $totalDiscount += ($orderItem->discount ?? 0);
                }
            }

            // 3. Atualiza Totais da Fatura
            $invoice->update([
                'subtotal' => $totalSubtotal,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalSubtotal - $totalDiscount,
            ]);

            // 4. Dispara Notificação (Assíncrono)
            // Verifica se a classe existe antes de disparar
            if (class_exists(SendInvoiceNotification::class)) {
                SendInvoiceNotification::dispatch($invoice);
            }

            return $invoice;
        });
    }

    /**
     * Gera fatura de Assinatura (Mensalidade)
     * Diferente do consumo, esta não depende de pedidos anteriores, é um valor fixo.
     */
    public function generateSubscriptionInvoice(Filho $filho, float $amount, Carbon $referenceMonth): Invoice
    {
        return DB::transaction(function () use ($filho, $amount, $referenceMonth) {
            $periodStart = $referenceMonth->copy()->startOfMonth();
            $periodEnd = $referenceMonth->copy()->endOfMonth();

            
            // 1. Calcular Data de Fechamento (Billing Date)
            $dataFechamento = Carbon::create($referenceMonth->year, $referenceMonth->month, 28);
            if ($referenceMonth->day > 28) {
                $dataFechamento->addMonth();
            }
            // 2. Calcular Data de Vencimento (Dia 05 do mês seguinte ao fechamento)
            $dataVencimento = $dataFechamento->copy()->addMonth()->day(5);
            $dueDate = $dataVencimento;

            $invoice = Invoice::create([
                'filho_id' => $filho->id,
                'type' => 'subscription', 
                'invoice_number' => Invoice::generateNextInvoiceNumber('subscription'),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now(),
                'due_date' => $dueDate,
                'subtotal' => $amount,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => "Mensalidade Competência: {$referenceMonth->format('m/Y')}",
            ]);

            // Cria o item da mensalidade
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Mensalidade - {$referenceMonth->format('F/Y')}",
                'category' => 'Mensalidade',
                'quantity' => 1,
                'unit_price' => $amount,
                'subtotal' => $amount,
                'total' => $amount,
                'purchase_date' => now(),
            ]);

            return $invoice;
        });
    }

    /**
     * Aplica multa e juros em faturas atrasadas.
     * Deve ser rodado via Cron Job diariamente.
     */
    public function applyLateFeesDaily(): int
    {
        $lateFeePercent = config('casalar.billing.late_fee_percent', 2.0); // 2%
        $dailyInterestPercent = config('casalar.billing.daily_interest_percent', 0.033); // 0.033% ao dia

        $overdueInvoices = Invoice::overdue()
            ->where('status', '!=', 'cancelled')
            ->where('late_fee_applied', false) // Evita aplicar multa fixa múltiplas vezes
            ->get();

        $updatedCount = 0;

        foreach ($overdueInvoices as $invoice) {
            // Apenas aplica se realmente estiver vencida (check duplo)
            if ($invoice->days_overdue <= 0) continue;

            DB::transaction(function () use ($invoice, $lateFeePercent, $dailyInterestPercent) {
                // Cálculo da Multa (Fixa)
                $lateFeeValue = 0;
                if (!$invoice->late_fee_applied) {
                    $lateFeeValue = ($invoice->subtotal * ($lateFeePercent / 100));
                }

                // Cálculo dos Juros (Acumulativo simples baseado nos dias)
                // Nota: Para juros compostos ou recálculo diário incremental, a lógica seria mais complexa.
                // Aqui recalculamos o juro total baseado nos dias de atraso atuais.
                $days = $invoice->days_overdue;
                $interestValue = ($invoice->subtotal * ($dailyInterestPercent / 100)) * $days;

                $invoice->update([
                    'late_fee' => $lateFeeValue,
                    'interest' => $interestValue,
                    'late_fee_applied' => true,
                    // total_amount é recalculado automaticamente se usar o método recalculateTotals,
                    // mas aqui fazemos direto para eficiência no loop.
                    'total_amount' => $invoice->subtotal - $invoice->discount_amount + $lateFeeValue + $interestValue
                ]);
            });
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Bloqueia filhos inadimplentes baseado na configuração.
     */
    public function blockDefaulters(): int
    {
        $maxOverdue = config('casalar.billing.max_overdue_invoices', 3);
        
        // Busca IDs de filhos que excedem o limite
        $defaulterIds = Invoice::select('filho_id')
            ->where('status', 'overdue')
            ->groupBy('filho_id')
            ->havingRaw('COUNT(*) >= ?', [$maxOverdue])
            ->pluck('filho_id');

        if ($defaulterIds->isEmpty()) {
            return 0;
        }

        return Filho::whereIn('id', $defaulterIds)
            ->where('is_blocked', false)
            ->update(['is_blocked' => true]);
    }

    /**
     * Verifica se o filho regularizou e desbloqueia.
     */
    public function checkAndUnblock(Filho $filho): void
    {
        if (!$filho->is_blocked) return;

        $hasOverdue = $filho->invoices()->overdue()->exists();

        if (!$hasOverdue) {
            $filho->update(['is_blocked' => false]);
        }
    }


}