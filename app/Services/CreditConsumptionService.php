<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Filho;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InsufficientCreditException;
use App\Exceptions\PaymentException;
use App\Exceptions\FilhoBlockedException;
use App\Notifications\SendMessageWhatsApp;

/**
 * Serviço de Consumo de Crédito (Saldo Interno)
 * 
 * MODELO DE NEGÓCIO:
 * - Saldo interno = Limite de crédito mensal 
 * - Consumo deduz do limite e cria dívida
 * - Dívida é faturada mensalmente
 * - Pagamento da fatura restaura o limite
 * 
 * @version 2.0
 * @author Sistema Mãos Estendidas
 */
class CreditConsumptionService
{
   
    public function consumeLimit(Order $order): array
    {
        // ========== VALIDAÇÕES INICIAIS ==========
        
        if (!$order->filho_id) {
            throw new PaymentException(
                message: 'Pedido não está vinculado a um filho/aluno.',
                code: 400,
                errorCode: 'ORDER_NOT_LINKED_TO_FILHO'
            );
        }
        
        if ($order->paid_at) {
            throw new PaymentException(
                message: 'Este pedido já foi pago.',
                code: 400,
                errorCode: 'ORDER_ALREADY_PAID'
            );
        }
        
        if ($order->status === 'cancelled') {
            throw new PaymentException(
                'Pedido cancelado não pode ser pago.',
                400,
                null,
                'ORDER_CANCELLED'
            );
        }
        
        // ========== CARREGAR FILHO ==========
        
        $filho = Filho::with('user')->findOrFail($order->filho_id);
        
        // ========== VALIDAR STATUS DO FILHO ==========
        
        if ($filho->status !== 'active') {
            throw new PaymentException(
                'Filho/aluno está inativo. Não é possível realizar compras.',
                403,
                null,
                'FILHO_INACTIVE'
            );
        }
        
        if ($filho->is_blocked_by_debt) {
            throw new FilhoBlockedException($filho->block_reason ?? 'Bloqueado por inadimplência');
        }
        
        // ========== VALIDAR LIMITE DISPONÍVEL ==========
        
        $creditAvailable = $filho->credit_limit - $filho->credit_used;
        
        if ($order->total > $creditAvailable) {
            throw new InsufficientCreditException(
                'Saldo insuficiente para esta compra.',
                [
                    'required' => (float) $order->total,
                    'available' => (float) $creditAvailable,
                    'missing' => (float) ($order->total - $creditAvailable),
                    'credit_limit' => (float) $filho->credit_limit,
                    'credit_used' => (float) $filho->credit_used,
                ]
            );
        }
        
        // ========== PROCESSAR TRANSAÇÃO ==========
        
        DB::beginTransaction();
        try {
            
            $balanceBefore = $creditAvailable;
            
            // 1. Atualizar crédito usado do filho
            $filho->credit_used = $filho->credit_used + $order->total;
            $filho->save();
            
            $balanceAfter = $filho->credit_limit - $filho->credit_used;
            
            // 2. Marcar Order como paga (operacionalmente)
            $order->update([
                'status' => 'ready',
                'paid_at' => now(),
                'is_invoiced' => false, 
                'payment_method_chosen' => 'carteira'
            ]);
            
            // 3. Registrar Transaction para auditoria
            $transaction = Transaction::create([
                'filho_id' => $filho->id,
                'order_id' => $order->id,
                'type' => 'debit',
                'amount' => $order->total,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Compra: Pedido #{$order->order_number}",
                'notes' => "Origem: {$order->origin}",
                'created_by_user_id' => auth()->id() ?? $order->created_by_user_id,
            ]);
            
            DB::commit();

            if($order->origin === 'app'){

                

                try{

                    $delaySeconds = now()->addSeconds(rand(5, 60));

                    $saudacoes = ['Olá! ', 'Oi ', 'Tudo bem? ', 'Oi amor! '];
                    $saudacao = $saudacoes[array_rand($saudacoes)];

                    $finais = [' vamos ver estoque e assim que estiver tudo pronto avisamos.', ' já estamos providenciando para reservar, tá. Logo te avisamos.', ' está tudo ok! Agora é só aguardar para retirar na lojinha'];
                    $final = $finais[array_rand($finais)];

                    $msg = "{$saudacao} Recebemos seu pedido, {$final} .";
                    
                    $filho->notify( (new SendMessageWhatsApp($msg))->delay($delaySeconds) );

                }catch(\Exception $e){
                    \Log::error('Erro ao enviar mensagem whatsapp: '.$e->getMessage());
                }

            }
            
           
            // ========== RETORNO PADRONIZADO ==========
            
            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount_debited' => (float) $order->total,
                'new_credit_available' => (float) $balanceAfter,
                'credit_limit' => (float) $filho->credit_limit,
                'credit_used' => (float) $filho->credit_used,
                'message' => "Compra aprovada! Novo saldo: R$ " . number_format($balanceAfter, 2, ',', '.'),
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
                
            throw new PaymentException(
                'Erro ao processar pagamento com saldo: ' . $e->getMessage(),
                500,
                null,
                'CREDIT_CONSUMPTION_FAILED'
            );
        }
    }
    
    /**
     * Consultar saldo disponível do filho
     * 
     * @param string $filhoId UUID do filho
     * @return array Informações do saldo
     */
    public function getBalance(string $filhoId): array
    {
        $filho = Filho::findOrFail($filhoId);
        
        $creditAvailable = $filho->credit_limit - $filho->credit_used;
        
        return [
            'success' => true,
            'credit_limit' => (float) $filho->credit_limit,
            'credit_used' => (float) $filho->credit_used,
            'credit_available' => (float) $creditAvailable,
            'is_blocked' => $filho->is_blocked_by_debt,
            'block_reason' => $filho->block_reason,
        ];
    }
}