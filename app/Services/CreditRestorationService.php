<?php

namespace App\Services;

use App\Models\Filho;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Restauração de Crédito
 * 
 * RESPONSABILIDADE:
 * - Restaurar limite de crédito após pagamento TOTAL de fatura
 * - Criar transaction de crédito para histórico
 * - Desbloquear filho se estava bloqueado por dívida
 * 
 * REGRAS:
 * - Apenas faturas do tipo "consumption" restauram crédito
 * - Fatura deve estar 100% paga (paid_amount >= total_amount)
 * - Não pode restaurar parcialmente
 * 
 * @version 2.0
 * @author Sistema Mãos Estendidas
 */
class CreditRestorationService
{
    /**
     * Restaurar crédito após pagamento de fatura
     * 
     * FLUXO:
     * 1. Validar tipo de fatura (consumption)
     * 2. Validar se está totalmente paga
     * 3. Zerar credit_used do filho
     * 4. Desbloquear se estava bloqueado
     * 5. Registrar transaction de crédito
     * 
     * @param Filho $filho
     * @param Invoice $invoice
     * @return void
     */
    public function restoreCredit(Filho $filho, Invoice $invoice): void
    {
        // ========== VALIDAÇÕES ==========
        
        // 1. Validar tipo de fatura
        if ($invoice->type !== 'consumption') {
            Log::info('Fatura não é de consumo, não restaura crédito', [
                'invoice_id' => $invoice->id,
                'invoice_type' => $invoice->type,
                'filho_id' => $filho->id,
            ]);
            return;
        }
        
        // 2. Validar se está totalmente paga
        if ($invoice->paid_amount < $invoice->total_amount) {
            Log::info('Fatura não totalmente paga, não restaura crédito ainda', [
                'invoice_id' => $invoice->id,
                'paid_amount' => $invoice->paid_amount,
                'total_amount' => $invoice->total_amount,
                'missing' => $invoice->total_amount - $invoice->paid_amount,
                'filho_id' => $filho->id,
            ]);
            return;
        }

        if($invoice->is_avulse){
            Log::info('Fatura avulsa, não restaura crédito, somente fatura de consumo', [
                'invoice_id' => $invoice->id,
                'filho_id' => $filho->id,
            ]);
            return;
        }
        
        // ========== PROCESSAR RESTAURAÇÃO ==========
        
            DB::beginTransaction();
        try {

            $filho->lockForUpdate()->refresh();
            $filho->update( [ 'credit_used' => DB::raw("GREATEST(0, credit_used - {$invoice->total_amount})") ] );
            
            $balanceBefore = $filho->credit_limit - $filho->credit_used;
            $creditToRestore = $filho->credit_used;
        
            DB::commit();
            
            // ========== LOG DE SUCESSO ==========
            
            Log::info('Crédito restaurado com sucesso', [
                'filho_id' => $filho->id,
                'filho_name' => $filho->full_name,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount_restored' => $invoice->total_amount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao restaurar crédito', [
                'filho_id' => $filho->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
        }
    }
    
    /**
     * Verificar se filho tem crédito pendente de restauração
     * 
     * Útil para troubleshooting
     * 
     * @param string $filhoId
     * @return array
     */
    public function checkPendingRestoration(string $filhoId): array
    {
        $filho = Filho::with(['invoices' => function ($query) {
            $query->where('type', 'consumption')
                  ->where('status', 'paid')
                  ->whereRaw('paid_amount >= total_amount');
        }])->findOrFail($filhoId);
        
        $paidInvoices = $filho->invoices;
        $hasUnrestoredCredit = $filho->credit_used > 0 && $paidInvoices->count() > 0;
        
        return [
            'filho_id' => $filho->id,
            'credit_used' => (float) $filho->credit_used,
            'paid_consumption_invoices' => $paidInvoices->count(),
            'has_unrestored_credit' => $hasUnrestoredCredit,
            'should_restore' => $hasUnrestoredCredit,
            'invoices' => $paidInvoices->map(fn($inv) => [
                'invoice_id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'total_amount' => $inv->total_amount,
                'paid_at' => $inv->paid_at?->toIso8601String(),
            ]),
        ];
    }
    
    /**
     * Restaurar crédito manualmente (troubleshooting)
     * 
     * Útil quando webhook falha
     * 
     * @param string $invoiceId
     * @return array
     */
    public function manualRestore(string $invoiceId): array
    {
        $invoice = Invoice::with('filho')->findOrFail($invoiceId);
        
        if ($invoice->type !== 'consumption') {
            return [
                'success' => false,
                'message' => 'Apenas faturas de consumo restauram crédito',
                'invoice_type' => $invoice->type,
            ];
        }
        
        if ($invoice->status !== 'paid') {
            return [
                'success' => false,
                'message' => 'Fatura não está paga',
                'invoice_status' => $invoice->status,
            ];
        }
        
        $this->restoreCredit($invoice->filho, $invoice);
        
        return [
            'success' => true,
            'message' => 'Crédito restaurado com sucesso',
            'filho_id' => $invoice->filho->id,
            'new_credit_available' => $invoice->filho->credit_limit - $invoice->filho->credit_used,
        ];
    }
}