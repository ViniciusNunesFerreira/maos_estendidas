<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\InvoiceItem;
use App\Models\Filho;
use App\Models\Order;
use App\Models\Payment;
use App\Jobs\SendInvoiceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Gera faturas de consumo mensal para todos os filhos elegíveis.
     * Otimizado para alta performance e baixo consumo de memória.
     */
    public function generateMonthlyInvoices(?Carbon $referenceDate = null): array
    {
        // Define a data de referência (hoje)
        $today = $referenceDate ? $referenceDate->copy()->startOfDay() : Carbon::today();
        
        $generatedCount = 0;
        $errors = [];
        $currentDay = $today->day;
        
        // --- 1. PREPARAÇÃO DE DATAS DO LOTE ---
        
        // A fatura gerada HOJE (dia do fechamento) refere-se ao mês anterior completo
        // Ex: Fechamento 01/02 -> Período: 01/01 a 31/01
        $periodStart = $today->copy()->subMonth()->startOfMonth();
        $periodEnd   = $today->copy()->subMonth()->endOfMonth();
        
        // Vencimento: 5º dia útil deste mês atual
        $dueDate = $this->getFifthBusinessDay($today);

        Log::info("Iniciando faturamento em massa. Ref: {$today->format('d/m/Y')}. Vencimento: {$dueDate->format('d/m/Y')}");

        // --- 2. QUERY INTELIGENTE (Edge Case Final de Mês) ---
        
        // Buscamos apenas filhos que possuem ordens não faturadas na 'carteira' no mês anterior
        $query = Filho::active()->whereHas('orders', function ($query) use ( $periodStart, $periodEnd) {
                    $query->where('payment_method_chosen', 'carteira')
                        ->where('status', 'delivered')
                        ->where('is_invoiced', false)
                        ->whereBetween('created_at', [$periodStart, $periodEnd]);
                });

        // Se hoje for o último dia do mês (ex: 28 de fev, 30 de abril),
        // devemos processar também quem tem vencimento nos dias 'inexistentes' (29, 30, 31)
        if ($today->copy()->endOfMonth()->isToday()) {
            $query->where('billing_close_day', '>=', $currentDay);
        } else {
            $query->where('billing_close_day', $currentDay);
        }

        // --- 3. PROCESSAMENTO EM LOTES (Chunking) ---

        // Processa em lotes de 100 para não estourar a RAM
        $query->chunkById(100, function ($filhos) use ($periodStart, $periodEnd, $dueDate, &$generatedCount, &$errors) {
            
            foreach ($filhos as $filho) {
                DB::beginTransaction();
                try {
                    // Verifica se já existe fatura para este mês/ano para evitar duplicidade
                    // Isso protege contra re-execução do Cron Job
                    $exists = Invoice::where('filho_id', $filho->id)
                        ->where('type', 'subscription')
                        ->whereMonth('period_start', $periodStart->month)
                        ->whereYear('period_start', $periodStart->year)
                        ->exists();

                    if ($exists) {
                        DB::commit();
                        continue; 
                    }

                    // Processa a criação da fatura
                   // $this->createInvoiceForFilho($filho, $periodStart, $periodEnd, $dueDate);
                    $this->processFilhoInvoice($filho, $periodStart, $periodEnd, $dueDate);
                    
                    DB::commit();
                    $generatedCount++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $msg = "Erro Faturamento Filho ID {$filho->id}: {$e->getMessage()}";
                    $errors[] = $msg;
                    Log::error($msg);
                }
            }
            
            // Libera memória explicitamente após cada chunk
            unset($filhos);
            gc_collect_cycles();
        });

        Log::info("Faturamento concluído. Gerados: {$generatedCount}. Erros: " . count($errors));

        return [
            'generated_count' => $generatedCount,
            'errors' => $errors
        ];
    }


    private function processFilhoInvoice($filho, $start, $end, $dueDate)
    {
        DB::transaction(function () use ($filho, $start, $end, $dueDate) {
            $orders = $filho->orders()
                ->where('payment_method_chosen', 'carteira')
                ->where('is_invoiced', false)
                ->whereBetween('created_at', [$start, $end])
                ->with('items.product')
                ->get();

            if ($orders->isEmpty()) return;

            $totalAmount = $orders->sum('total');

            // 1. Criar a Fatura Mãe
            $invoice = Invoice::create([
                'filho_id' => $filho->id,
                'invoice_number' => Invoice::generateNextInvoiceNumber('consumption'),
                'type' => 'consumption',
                'period_start' => $start,
                'period_end' => $end,
                'issue_date' => now(),
                'due_date' => $dueDate,
                'subtotal' => $totalAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => "Fechamento mensal de consumo - " . $start->format('m/Y'),
            ]);

            // 2. Criar Itens da Fatura e vincular Ordens
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'order_id' => $order->id,
                        'description' => $item->product->name ?? 'Consumo Lojinha',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'total' => $item->total,
                    ]);
                }
                
                // Marca a ordem como faturada
                $order->markAsInvoiced($invoice);
            }

            // 3. Despachar para Fila de WhatsApp com Delay para evitar BAN
            // Usamos um delay incremental ou fixo para humanizar
           /* ProcessInvoiceNotification::dispatch($filho, $invoice)
                ->delay(now()->addMinutes(rand(1, 60))); */
        });
    }


    /**
     * Cria a fatura individual (Método helper privado)
     */
   /* private function createInvoiceForFilho(Filho $filho, Carbon $start, Carbon $end, Carbon $due): Invoice
    {
        // Pega o valor da assinatura ativa ou o padrão do sistema
        // Assumindo que o relacionamento 'subscription' existe e pega a mais recente/ativa
        $subscription = $filho->subscription; 
        $amount = $subscription ? $subscription->amount : config('casalar.subscription.default_amount');

        $invoice = Invoice::create([
            'filho_id' => $filho->id,
            'type' => 'subscription',
            'invoice_number' => Invoice::generateNextInvoiceNumber('subscription'),
            'period_start' => $start,
            'period_end' => $end,
            'issue_date' => now(),
            'due_date' => $due,
            'subtotal' => $amount,
            'total_amount' => $amount,
            'status' => 'pending',
            'notes' => "Mensalidade referente a " . $start->translatedFormat('F/Y'),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Mensalidade - " . $start->translatedFormat('F/Y'),
            'category' => 'Mensalidade',
            'quantity' => 1,
            'unit_price' => $amount,
            'subtotal' => $amount,
            'total' => $amount,
            'purchase_date' => now(),
        ]);

        // Dispara notificação se necessário (Job na fila)
        // SendInvoiceNotification::dispatch($invoice);

        return $invoice;
    }*/


    /**
     * Gera fatura de Assinatura (Mensalidade)
     * Diferente do consumo, esta não depende de pedidos anteriores, é um valor fixo.
     */
    //Função antiga comentada para correção em : 03/02/2026
    public function generateSubscriptionInvoice(
        Subscription $subscription,
        Filho $filho, 
        float $amount, 
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $dueDate
    ): Invoice
    {
        return DB::transaction(function () use ($subscription, $filho, $amount, $periodStart, $periodEnd, $dueDate) {
            
            // Formatamos a competência baseada no início do período
            $competencia = $periodStart->format('m/Y');

            $invoice = Invoice::create([
                'filho_id' => $filho->id,
                'type' => 'subscription',
                'subscription_id' => $subscription->id, 
                'invoice_number' => Invoice::generateNextInvoiceNumber('subscription'),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now(),
                'due_date' => $dueDate,
                'subtotal' => $amount,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => "Mensalidade Competência: {$competencia}",
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Mensalidade - {$periodStart->translatedFormat('F/Y')}",
                'category' => 'Mensalidade',
                'quantity' => 1,
                'unit_price' => $amount,
                'subtotal' => $amount,
                'total' => $amount,
                'purchase_date' => now(),
            ]);

           // $invoice->subscription()->associate($subscription);

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


    /**
     * Calcula o 5º dia útil de um determinado mês/ano
     * Considera Finais de Semana e Feriados Nacionais (Fixos e Móveis).
     */
    public function getFifthBusinessDay(Carbon $date): Carbon
    {
        $day = $date->copy()->startOfMonth();
        $businessDaysFound = 0;
        $targetBusinessDays = 5;

        // Cache simples dos feriados desse ano para evitar recálculo no loop
        $holidays = $this->getNationalHolidays($day->year);

        while ($businessDaysFound < $targetBusinessDays) {
            $dateString = $day->format('m-d');
            
            $isWeekend = $day->isWeekend();
            $isHoliday = in_array($dateString, $holidays);

            // Só conta se não for sábado, domingo, nem feriado
            if (!$isWeekend && !$isHoliday) {
                $businessDaysFound++;
            }

            // Se ainda não chegamos no 5º dia, avança.
            // Se chegamos no 5º dia, o loop para e retornamos $day (que é a data correta)
            if ($businessDaysFound < $targetBusinessDays) {
                $day->addDay();
            }
        }

        return $day;
    }


    private function getNationalHolidays(int $year): array
    {
        // 1. Feriados Fixos Nacionais
        $fixedHolidays = [
            '01-01', // Confraternização Universal
            '04-21', // Tiradentes
            '05-01', // Dia do Trabalho
            '09-07', // Independência do Brasil
            '10-12', // Nossa Senhora Aparecida
            '11-02', // Finados
            '11-15', // Proclamação da República
            '11-20', // Dia da Consciência Negra (Lei 14.759/2023)
            '12-25', // Natal
        ];

        // 2. Feriados Móveis (Baseados na Páscoa)
        // O PHP tem funções nativas para cálculo da páscoa, o que garante precisão matemática
        
        // Data da Páscoa (Domingo)
        $easterDate = Carbon::createFromDate($year, 3, 21)->addDays(easter_days($year));

        // Calculando feriados relativos à Páscoa
        $movableHolidays = [
            $easterDate->copy()->subDays(48)->format('m-d'), // Segunda de Carnaval (Bancos fechados)
            $easterDate->copy()->subDays(47)->format('m-d'), // Terça de Carnaval (Bancos fechados)
            $easterDate->copy()->subDays(2)->format('m-d'),  // Sexta-feira Santa (Paixão de Cristo)
            $easterDate->copy()->addDays(60)->format('m-d'), // Corpus Christi
        ];

        return array_merge($fixedHolidays, $movableHolidays);
    }


    /**
     * Registra um pagamento manual para uma fatura.
     */
    public function registerManualPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            // 1. Criar o registro de Pagamento (Payment)
            $payment = Payment::create([
                'invoice_id'    => $invoice->id,
                'method'        => $data['method'], // ex: 'dinheiro', 'pix'
                'amount'        => $data['amount'],
                'status'        => 'confirmed',
                'confirmed_at'  => now(),
                'gateway_name'  => 'manual', // Identifica que não veio de API externa
                'reference'     => $data['reference'] ?? null, // Ex: ID da transação bancária
            ]);

            // 2. Atualizar a Fatura (Invoice) com o máximo de detalhes para relatórios
            // O modelo Invoice possui campos de controle que precisam ser sincronizados.
            $newPaidAmount = $invoice->paid_amount + $data['amount'];
            
            // Determinar novo status
            $status = $newPaidAmount >= $invoice->total_amount ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount'   => $newPaidAmount,
                'status'        => $status,
                'paid_at'       => $status === 'paid' ? now() : $invoice->paid_at,
                'internal_notes'=> $this->appendNote($invoice->internal_notes, $data['internal_notes']),
            ]);

            // 3. Restaurar crédito do Filho (se aplicável)
            // Se a fatura é de consumo, ao pagar, liberamos o limite gasto.
            if ($invoice->filho) {
                $invoice->filho->restoreCredit($data['amount']);
                $invoice->filho->checkAndUpdateBlockStatus(); // Verifica se o pagamento remove bloqueios
            }

            Log::info("Pagamento manual registrado", [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'user_id'    => auth()->id()
            ]);

            return $payment;
        });
    }

    private function appendNote(?string $currentNotes, ?string $newNote): ?string
    {
        if (!$newNote) return $currentNotes;
        $date = now()->format('d/m/Y H:i');
        $user = auth()->user()->name ?? 'Sistema';
        return ($currentNotes ? $currentNotes . "\n" : "") . "[{$date} - {$user}]: {$newNote}";
    }


}