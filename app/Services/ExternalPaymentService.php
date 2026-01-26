<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PaymentException;
use App\Exceptions\BusinessRuleException;

/**
 * Serviço de Pagamentos Externos (PIX e Cartão)
 * 
 * RESPONSABILIDADE:
 * - Criar Payment Intents para PIX e Cartão
 * - Integrar com CheckoutTransparenteService (já existente)
 * - Processar webhooks do Mercado Pago
 * - Confirmar pagamentos e atualizar status
 * 
 * IMPORTANTE:
 * Este service DELEGA para CheckoutTransparenteService
 * Não reimplementa lógica do Mercado Pago
 * 
 * @version 2.0
 * @author Sistema Mãos Estendidas
 */
class ExternalPaymentService
{
    public function __construct(
        protected CheckoutTransparenteService $checkoutTransparente,
        protected CreditRestorationService $creditRestoration
    ) {}
    
    // =========================================================
    // PIX - ORDERS
    // =========================================================
    
    /**
     * Criar pagamento PIX para Order
     * 
     * @param Order $order
     * @return array Dados do PIX (QR Code, etc)
     * @throws PaymentException
     */
    public function createOrderPixPayment(Order $order): array
    {
        // Validação: Order já paga
        if ($order->status === 'paid' && $order->is_invoiced) {
            throw new PaymentException(
                'Este pedido já foi pago e faturado.',
                400,
                null,
                'ORDER_ALREADY_PAID'
            );
        }
        
        DB::beginTransaction();
        try {
            
            // Delegar para CheckoutTransparenteService
            $mpResult = $this->checkoutTransparente->createPixPayment($order);
            
            DB::commit();
            
                        
            return [
                'success' => true,
                'payment_intent_id' => $mpResult['payment_intent_id'],
                'pix' => [
                    'qr_code' => $mpResult['pix']['qr_code'],
                    'qr_code_base64' => $mpResult['pix']['qr_code_base64'],
                    'copy_paste_code' => $mpResult['pix']['qr_code'], // Mesmo código
                    'expiration' => $mpResult['pix']['expiration_date'] ?? now()->addHours(24)->toIso8601String(),
                ],
                
                'message' => 'QR Code PIX gerado com sucesso',
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            throw new PaymentException(
                'Erro ao gerar PIX: ' . $e->getMessage(),
                500,
                null,
                'CREATE_PIX_FAILED'
            );
        }
    }
    
    // =========================================================
    // PIX - INVOICES
    // =========================================================
    
    /**
     * Criar pagamento PIX para Invoice
     * 
     * 🚨 REGRA CRÍTICA: Pagamento de fatura restaura limite de crédito
     * 
     * @param Invoice $invoice
     * @return array Dados do PIX
     * @throws PaymentException
     * @throws BusinessRuleException
     */
    public function createInvoicePixPayment(Invoice $invoice): array
    {
        // Validação: Invoice já paga
        if ($invoice->status === 'paid') {
            throw new PaymentException(
                'Esta fatura já foi paga.',
                400,
                'INVOICE_ALREADY_PAID'
            );
        }
        
        // Validação: Invoice cancelada
        if ($invoice->status === 'cancelled') {
            throw new PaymentException(
                'Fatura cancelada não pode ser paga.',
                400,
                'INVOICE_CANCELLED'
            );
        }
        
        try {
           
            return DB::transaction(function () use ($invoice) {
                // 1. LOCK: Seleciona a Invoice e trava o registro para outros processos
                $invoice = Invoice::where('id', $invoice->id)->lockForUpdate()->first();

                $amountToPay = $invoice->total_amount - $invoice->paid_amount;

              
                $intent = PaymentIntent::where('invoice_id', $invoice->id)
                    ->where('status', 'pending')
                    ->where('pix_expiration', '>', now())
                    ->first();

                if ($intent) {
                    Log::info('PIX Recuperado (Idempotência)', ['intent_id' => $intent->id]);
                    return $this->formatResponse($intent); // Retorne os dados que já existem
                }


                // 4. Chamar Mercado Pago
                $mpData = $this->buildInvoicePaymentData($invoice, $amountToPay);
                $mpResult = $this->checkoutTransparente->createPixPaymentForInvoice($invoice, $mpData);

                // 5. UPDATE OR CREATE com tratamento de concorrência
                // Usamos o mp_payment_id como âncora de segurança
                $paymentIntent = PaymentIntent::updateOrCreate(
                    ['mp_payment_id' => $mpResult['payment_id']], 
                    [
                        'invoice_id' => $invoice->id,
                        'status' => 'pending',
                        'pix_qr_code' => $mpResult['qr_code'],
                        'pix_qr_code_base64' => $mpResult['qr_code_base64'],
                        'pix_expiration' => $mpResult['expiration_date'],
                        'mp_response' => $mpResult,
                    ]
                );

                return $this->formatResponse($paymentIntent);
            });

        } catch (\Exception $e) {
            Log::error('Erro ao criar PIX', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            throw $e;
        }

       
    }

     // Helper para manter o padrão de retorno do PWA
    private function formatResponse($intent) {
        return [
            'success' => true,
            'payment_intent_id' => $intent->id,
            'pix' => [
                'qr_code' => $intent->pix_qr_code,
                'qr_code_base64' => $intent->pix_qr_code_base64,
                'copy_paste_code' => $intent->pix_qr_code,
                'expiration' => $intent->pix_expiration->toIso8601String(),
            ],
            'amount' => $intent->amount,
            'mp_payment_id' => $intent->mp_payment_id,
            'status' => $intent->status
        ];
    }
    
    // =========================================================
    // CARTÃO - ORDERS
    // =========================================================
    
    
    public function createOrderCardPayment(
        Order $order,
        string $cardToken,
        string $paymentMethodId,
        int $installments = 1
    ): array {
        DB::beginTransaction();
        try {
            
            // Delegar para CheckoutTransparenteService
            $mpResult = $this->checkoutTransparente->createCardPayment(
                $order,
                $cardToken,
                $paymentMethodId,
                $installments
            );
            
            DB::commit();
            
            return [
                'success' => true,
                'payment_intent_id' => $mpResult['payment_intent_id'],
                'transaction_id' => $mpResult['mp_payment_id'],
                'status' => $mpResult['status'],
                'status_detail' => $mpResult['status_detail'] ?? '',
                'message' => $this->getCardPaymentMessage($mpResult['status']),
                'card_last_digits' => $mpResult['card_last_digits'] ?? '',
                'card_brand' => $mpResult['card_brand'] ?? '',
                'installments' => $installments,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao processar cartão para Order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new PaymentException(
                'Erro ao processar cartão: ' . $e->getMessage(),
                500,
                'CREATE_CARD_PAYMENT_FAILED'
            );
        }
    }
    
    // =========================================================
    // CARTÃO - INVOICES
    // =========================================================
    
    /**
     * Criar pagamento com Cartão para Invoice
     */
    public function createInvoiceCardPayment(
        Invoice $invoice,
        string $cardToken,
        string $paymentMethodId,
        int $installments = 1
    ): array {
        DB::beginTransaction();
        try {
            
            $amountToPay = $invoice->total_amount - $invoice->paid_amount;
            
            // Similar ao PIX, criar Payment e PaymentIntent
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'method' => 'credito',
                'amount' => $amountToPay,
                'status' => 'pending',
            ]);
            
            $paymentIntent = PaymentIntent::create([
                'payment_id' => $payment->id,
                'integration_type' => 'checkout',
                'payment_method' => 'credit_card',
                'amount' => $amountToPay,
                'status' => 'created',
                'installments' => $installments,
            ]);
            
            // Chamar MP
            $mpData = $this->buildInvoiceCardPaymentData($invoice, $amountToPay, $cardToken, $paymentMethodId, $installments);
            $mpResult = $this->checkoutTransparente->createCardPaymentForInvoice($invoice, $mpData);
            
            // Atualizar PaymentIntent
            $paymentIntent->update([
                'mp_payment_id' => $mpResult['payment_id'],
                'status' => $mpResult['status'],
                'card_last_digits' => $mpResult['card_last_digits'] ?? null,
                'card_brand' => $mpResult['card_brand'] ?? null,
                'mp_response' => $mpResult,
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'transaction_id' => $mpResult['payment_id'],
                'status' => $mpResult['status'],
                'status_detail' => $mpResult['status_detail'] ?? '',
                'message' => $this->getCardPaymentMessage($mpResult['status']),
                'card_last_digits' => $mpResult['card_last_digits'] ?? '',
                'card_brand' => $mpResult['card_brand'] ?? '',
                'installments' => $installments,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao processar cartão para Invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new PaymentException(
                'Erro ao processar cartão para fatura: ' . $e->getMessage(),
                500,
                'CREATE_INVOICE_CARD_PAYMENT_FAILED'
            );
        }
    }
    
    // =========================================================
    // WEBHOOKS - PROCESSAMENTO
    // =========================================================
    
    /**
     * Processar webhook de pagamento aprovado
     * 
     * 🔴 IMPORTANTE: Aqui acontece a restauração de limite!
     * 
     * @param string $mpPaymentId ID do pagamento no Mercado Pago
     * @return void
     */
    public function processPaymentApproved(string $mpPaymentId): void
    {
        $paymentIntent = PaymentIntent::where('mp_payment_id', $mpPaymentId)->first();
        
        if (!$paymentIntent) {
            Log::warning('Payment Intent não encontrado para webhook', [
                'mp_payment_id' => $mpPaymentId,
            ]);
            return;
        }
        
        DB::beginTransaction();
        try {
            
            // Atualizar PaymentIntent
            $paymentIntent->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            
            // Se for Order
            if ($paymentIntent->order_id) {
                $this->processOrderPaymentApproved($paymentIntent);
            }
            
            // Se for Invoice
            if ($paymentIntent->payment?->invoice_id) {
                $this->processInvoicePaymentApproved($paymentIntent);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao processar webhook de pagamento aprovado', [
                'mp_payment_id' => $mpPaymentId,
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Processar aprovação de pagamento de Order
     */
    protected function processOrderPaymentApproved(PaymentIntent $paymentIntent): void
    {
        $order = $paymentIntent->order;
        
        // Atualizar Payment (se existir)
        if ($paymentIntent->payment) {
            $paymentIntent->payment->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
        }
        
        // Atualizar Order
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
            'is_invoiced' => true, // ✅ Pago com dinheiro real, não vai para fatura
            'awaiting_external_payment' => false,
        ]);
        
        Log::info('Pagamento de Order aprovado via webhook', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => $paymentIntent->amount,
        ]);
    }
    
    /**
     * Processar aprovação de pagamento de Invoice
     * 
     * 🔄 AQUI ACONTECE A MÁGICA: RESTAURAÇÃO DO LIMITE!
     */
    protected function processInvoicePaymentApproved(PaymentIntent $paymentIntent): void
    {
        $payment = $paymentIntent->payment;
        $invoice = $payment->invoice;
        
        // Atualizar Payment
        $payment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
        
        // Atualizar Invoice
        $invoice->paid_amount = $invoice->paid_amount + $paymentIntent->amount;
        
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
        } else {
            $invoice->status = 'partial';
        }
        
        $invoice->save();
        
        // 🔄 RESTAURAR LIMITE DE CRÉDITO
        if ($invoice->status === 'paid') {
            $this->creditRestoration->restoreCredit($invoice->filho, $invoice);
        }
        
        Log::info('Pagamento de Invoice aprovado via webhook', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $paymentIntent->amount,
            'invoice_fully_paid' => $invoice->status === 'paid',
        ]);
    }
    
    // =========================================================
    // HELPERS
    // =========================================================
    
    protected function buildInvoicePaymentData(Invoice $invoice, float $amount, $paymentMethodId='pix'): array
    {
        $expirationDate = now()->addMinutes(30)->format('Y-m-d\TH:i:s.vP');

        return [
            'transaction_amount' => (float) $amount,
            'description' => "Fatura {$invoice->invoice_number}",
            'external_reference' => "invoice_{$invoice->id}",
            'payment_method_id' => $paymentMethodId,
            'installments' => 1,
            'date_of_expiration' => $expirationDate,
            'payer' => [
                'email' => $invoice->filho->user->email,
                'first_name' => $invoice->filho->user->name,
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/[^0-9]/', '', $invoice->filho->cpf),
                ],
            ],
            'notification_url' => route('api.webhooks.mercadopago'),
        ];
    }
    
    protected function buildInvoiceCardPaymentData(
        Invoice $invoice,
        float $amount,
        string $cardToken,
        string $paymentMethodId,
        int $installments
    ): array {
        return array_merge($this->buildInvoicePaymentData($invoice, $amount), [
            'token' => $cardToken,
            'payment_method_id' => $paymentMethodId,
            'installments' => $installments,
        ]);
    }
    
    protected function getCardPaymentMessage(string $status): string
    {
        return match ($status) {
            'approved' => 'Pagamento aprovado com sucesso!',
            'pending' => 'Pagamento em processamento...',
            'in_process' => 'Aguardando confirmação do banco...',
            'rejected' => 'Pagamento rejeitado. Verifique os dados do cartão.',
            default => 'Status do pagamento: ' . $status,
        };
    }
}