<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Filho;
use App\Models\Order;
use App\Jobs\SendInvoiceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Invoice Generation Service V2
 * @version 2.0
 * @author Sistema Mãos Estendidas
 */
class InvoiceGenerationServiceV2
{
    public function __construct(
        protected CreditRestorationService $creditRestoration
    ) {}
    
    // =========================================================
    // FATURAMENTO MENSAL COM DATA DE CORTE PROFISSIONAL
    // =========================================================
    
    /**
     * Gerar faturas mensais de consumo
     * 
     * NOVO: Usa Data de Corte ao invés de billing_close_day individual
     * 
     * @param Carbon|null $referenceDate
     * @return array
     */
    public function generateMonthlyInvoices(?Carbon $referenceDate = null): array
    {
        $referenceDate = $referenceDate ?? now();
        
        Log::info('Iniciando geração de faturas mensais V2', [
            'reference_date' => $referenceDate->toDateTimeString(),
        ]);
        
        // ========== CALCULAR DATA DE CORTE ==========
        
        $cutoffDate = $this->calculateCutoffDate($referenceDate);
        
        Log::info('Data de corte calculada', [
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);
        
        // ========== BUSCAR ORDERS NÃO FATURADAS ==========
        
        $ordersGrouped = $this->getOrdersToInvoice($cutoffDate);
        
        if ($ordersGrouped->isEmpty()) {
            Log::info('Nenhuma order para faturar');
            return [
                'generated_count' => 0,
                'errors' => [],
            ];
        }
        
        // ========== GERAR FATURAS POR FILHO ==========
        
        $generatedCount = 0;
        $errors = [];
        
        foreach ($ordersGrouped as $filhoId => $orders) {
            try {
                $invoice = $this->generateInvoiceForFilho($filhoId, $orders, $cutoffDate);
                $generatedCount++;
                
            } catch (\Exception $e) {
                $errors[] = "Erro ao gerar fatura para Filho ID {$filhoId}: " . $e->getMessage();
                Log::error('Erro ao gerar fatura', [
                    'filho_id' => $filhoId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Geração de faturas concluída', [
            'generated_count' => $generatedCount,
            'errors_count' => count($errors),
        ]);
        
        return [
            'generated_count' => $generatedCount,
            'errors' => $errors,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ];
    }
    
    /**
     * Calcular data de corte (sistema profissional)
     * 
     * GARANTE: Nenhuma order fica para trás
     */
    protected function calculateCutoffDate(Carbon $referenceDate): Carbon
    {
        // Vai para o primeiro dia do mês de referência (Fechamento todo dia primeiro do mês)
        $firstDayOfMonth = $referenceDate->copy()->startOfMonth();
        
        // Volta 1 segundo para pegar o último segundo do mês anterior
        $cutoffDate = $firstDayOfMonth->copy()->subSecond();
        
        return $cutoffDate;
    }
    
    /**
     * Buscar orders que devem ser faturadas
     */
    protected function getOrdersToInvoice(Carbon $cutoffDate)
    {
        $orders = Order::with(['items.product.category', 'filho.user'])
            ->where('is_invoiced', false)
            ->whereIn('status', ['paid', 'completed'])
            ->where('created_at', '<=', $cutoffDate)
            ->whereHas('filho', function ($q) {
                $q->where('status', 'active');
            })
            ->orderBy('filho_id')
            ->orderBy('created_at')
            ->get();
        
        // Agrupar por filho_id
        return $orders->groupBy('filho_id');
    }
    
    /**
     * Gerar fatura para um filho específico
     */
    protected function generateInvoiceForFilho(string $filhoId, $orders, Carbon $cutoffDate): Invoice
    {
        $filho = Filho::with('user')->findOrFail($filhoId);
        
        // Calcular período
        $firstOrderDate = $orders->min('created_at');
        $periodStart = Carbon::parse($firstOrderDate)->startOfDay();
        $periodEnd = $cutoffDate->copy();
        
        // Data de vencimento (10 dias após período)
        $dueDate = $periodEnd->copy()->addDays(10)->endOfDay();
        
        // Calcular totais
        $subtotal = $orders->sum('subtotal');
        $discount = $orders->sum('discount');
        $total = $orders->sum('total');
        
        // Gerar número da fatura
        $invoiceNumber = $this->generateInvoiceNumber($filho, $periodEnd);
        
        return DB::transaction(function () use (
            $filho, $orders, $periodStart, $periodEnd, 
            $dueDate, $subtotal, $discount, $total, $invoiceNumber
        ) {
            
            // Criar fatura
            $invoice = Invoice::create([
                'filho_id' => $filho->id,
                'invoice_number' => $invoiceNumber,
                'type' => 'consumption',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now(),
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => "Consumo de {$periodStart->format('d/m/Y')} a {$periodEnd->format('d/m/Y')}",
            ]);
            
            // Criar items da fatura
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'purchase_date' => $order->created_at->toDateString(),
                        'description' => $item->product_name ?? $item->product->name,
                        'category' => $item->product->category->name ?? 'Geral',
                        'location' => $order->origin,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'discount_amount' => $item->discount ?? 0,
                        'total' => $item->total,
                    ]);
                }
            }
            
            // Vincular orders à fatura
            Order::whereIn('id', $orders->pluck('id'))
                 ->update([
                     'invoice_id' => $invoice->id,
                     'is_invoiced' => true,
                     'invoiced_at' => now(),
                 ]);
            
            // Disparar notificação
            if (class_exists(SendInvoiceNotification::class)) {
                SendInvoiceNotification::dispatch($invoice);
            }
            
            Log::info('Fatura de consumo gerada', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'filho_id' => $filho->id,
                'orders_count' => $orders->count(),
                'total_amount' => $total,
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Gerar número único da fatura
     */
    protected function generateInvoiceNumber(Filho $filho, Carbon $periodEnd): string
    {
        $prefix = 'FAT';
        $filhoSequence = str_pad($filho->id, 3, '0', STR_PAD_LEFT);
        $yearMonth = $periodEnd->format('Ym');
        
        $lastInvoice = Invoice::where('filho_id', $filho->id)
            ->where('invoice_number', 'LIKE', "{$prefix}-{$filhoSequence}-{$yearMonth}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->invoice_number);
            $sequence = intval($parts[3] ?? 0) + 1;
        } else {
            $sequence = 1;
        }
        
        $sequenceStr = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$filhoSequence}-{$yearMonth}-{$sequenceStr}";
    }
    
    // =========================================================
    // SUBSCRIPTION INVOICES (do sistema antigo)
    // =========================================================
    
    /**
     * Gerar fatura de Assinatura (Mensalidade)
     */
    public function generateSubscriptionInvoice(Filho $filho, float $amount, Carbon $referenceMonth): Invoice
    {
        return DB::transaction(function () use ($filho, $amount, $referenceMonth) {
            $periodStart = $referenceMonth->copy()->startOfMonth();
            $periodEnd = $referenceMonth->copy()->endOfMonth();
            
            $dataFechamento = Carbon::create($referenceMonth->year, $referenceMonth->month, 28);
            if ($referenceMonth->day > 28) {
                $dataFechamento->addMonth();
            }
            
            $dueDate = $dataFechamento->copy()->addMonth()->day(5);
            
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
    
    // =========================================================
    // LATE FEES (do sistema antigo)
    // =========================================================
    
    /**
     * Aplicar multa e juros em faturas atrasadas
     */
    public function applyLateFeesDaily(): int
    {
        $lateFeePercent = config('casalar.billing.late_fee_percent', 0);
        $dailyInterestPercent = config('casalar.billing.daily_interest_percent', 0);
        
        $overdueInvoices = Invoice::overdue()
            ->where('status', '!=', 'cancelled')
            ->where('late_fee_applied', false)
            ->get();
        
        $updatedCount = 0;
        
        foreach ($overdueInvoices as $invoice) {
            if ($invoice->days_overdue <= 0) continue;
            
            DB::transaction(function () use ($invoice, $lateFeePercent, $dailyInterestPercent) {
                $lateFeeValue = 0;
                if (!$invoice->late_fee_applied) {
                    $lateFeeValue = ($invoice->subtotal * ($lateFeePercent / 100));
                }
                
                $days = $invoice->days_overdue;
                $interestValue = ($invoice->subtotal * ($dailyInterestPercent / 100)) * $days;
                
                $invoice->update([
                    'late_fee' => $lateFeeValue,
                    'interest' => $interestValue,
                    'late_fee_applied' => true,
                    'total_amount' => $invoice->subtotal - $invoice->discount_amount + $lateFeeValue + $interestValue
                ]);
            });
            
            $updatedCount++;
        }
        
        return $updatedCount;
    }
    
    // =========================================================
    // BLOCKING (do sistema antigo)
    // =========================================================
    
    /**
     * Bloquear filhos inadimplentes
     */
    public function blockDefaulters(): int
    {
        $maxOverdue = config('casalar.billing.max_overdue_invoices', 3);
        
        $defaulterIds = Invoice::select('filho_id')
            ->where('status', 'overdue')
            ->groupBy('filho_id')
            ->havingRaw('COUNT(*) >= ?', [$maxOverdue])
            ->pluck('filho_id');
        
        if ($defaulterIds->isEmpty()) {
            return 0;
        }
        
        return Filho::whereIn('id', $defaulterIds)
            ->where('is_blocked_by_debt', false)
            ->update([
                'is_blocked_by_debt' => true,
                'block_reason' => "Bloqueado por {$maxOverdue}+ faturas vencidas",
                'blocked_at' => now(),
            ]);
    }
    
    /**
     * Verificar se filho regularizou e desbloquear
     */
    public function checkAndUnblock(Filho $filho): void
    {
        if (!$filho->is_blocked_by_debt) return;
        
        $hasOverdue = $filho->invoices()->overdue()->exists();
        
        if (!$hasOverdue) {
            $filho->update([
                'is_blocked_by_debt' => false,
                'block_reason' => null,
                'blocked_at' => null,
            ]);
            
            Log::info('Filho desbloqueado após regularização', [
                'filho_id' => $filho->id,
            ]);
        }
    }
}