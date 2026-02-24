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
use Carbon\Carbon;
use Exception;
use App\Notifications\SendMessageWhatsApp;

/**
 * Serviço de Pagamentos Externos (PIX e Cartão)
 * 
 * RESPONSABILIDADE:
 * - Criar Payment Intents para PIX e Cartão
 * - Integrar com CheckoutTransparenteService
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

                $intent = PaymentIntent::where('invoice_id', $invoice->id)
                    ->where('payment_method', 'pix')
                    ->where('status', 'pending')
                    ->where('pix_expiration', '>', now())
                    ->first();

                if ($intent) {
                    return $this->formatResponse($intent);
                }

                $amountToPay = $invoice->total_amount - $invoice->paid_amount;

                // 4. Chamar Mercado Pago
                $mpData = $this->buildInvoicePaymentData($invoice, $amountToPay);
                $mpResult = $this->checkoutTransparente->createPixPaymentForInvoice($invoice, $mpData);

           
            return DB::transaction(function () use ($invoice, $mpResult, $mpData) {

                $invoice->refresh()->lockForUpdate();

                $paymentIntent = PaymentIntent::updateOrCreate(
                    ['mp_payment_id' => $mpResult['payment_id']], 
                    [
                        'invoice_id' => $invoice->id,
                        'payment_method' => 'pix',
                        'integration_type' => 'checkout',
                        'amount'           => $invoice->total_amount,
                        'status' => 'pending',
                        'pix_qr_code' => $mpResult['qr_code'],
                        'pix_qr_code_base64' => $mpResult['qr_code_base64'],
                        'pix_expiration' => $mpResult['expiration_date'],
                        'mp_request' => $mpData,
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
        $payer,
        string $paymentMethodId,
    ): array {
        
        try {
            
            // Delegar para CheckoutTransparenteService
            $mpResult = $this->checkoutTransparente->createCardPayment(
                $order,
                $cardToken,
                $payer,
                $paymentMethodId,
                1
            );

            \Log::debug($mpResult);

            // VERIFICAÇÃO IMEDIATA
            if (($mpResult['status'] ?? '') === 'approved') {
                $intent = PaymentIntent::find($mpResult['payment_intent_id']);
                $this->processPaymentConfirmation($intent, $mpResult['mp_response'] ?? []);
            }
            
            
            
            return [
                'success' => true,
                'payment_intent_id' => $mpResult['payment_intent_id'],
                'transaction_id' => $mpResult['mp_payment_id'],
                'status' => $mpResult['status'],
                'status_detail' => $mpResult['status_detail'] ?? '',
                'message' => $this->getCardPaymentMessage($mpResult['status']),
                'card_last_digits' => $mpResult['card_last_digits'] ?? '',
                'card_brand' => $mpResult['card_brand'] ?? '',
                'approved' => $mpResult['approved'],
                'installments' => 1,
            ];
            
        } catch (\Exception $e) {
            
            
            Log::error('Erro ao processar cartão para Order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentException("Erro no processamento do cartão: " . $e->getMessage(), 400, null, 'CREATE_CARD_PAYMENT_FAILED');
            
        }
    }
    
    // =========================================================
    // CARTÃO - INVOICES
    // =========================================================
    
    public function createInvoiceCardPayment(
        Invoice $invoice,
        string $cardToken,
        $payer,
        string $paymentMethodId,
    ): array {

        if ($invoice->status === 'paid') {
            throw new PaymentException('Esta fatura já foi Paga.', 400, 'CREATE_CARD_PAYMENT_FAILED');
        }

        
        try {
            

            // Chamar MP
            $mpData = $this->buildInvoiceCardPaymentData($invoice, $cardToken, $payer, $paymentMethodId, 1);
            $mpResult = $this->checkoutTransparente->createCardPaymentForInvoice($invoice, $mpData);
            
            // VERIFICAÇÃO IMEDIATA
            if (($mpResult['status'] ?? '') === 'approved') {
                $intent = PaymentIntent::find($mpResult['payment_intent_id']);
                $this->processPaymentConfirmation($intent, $mpResult['mp_response'] ?? []);
            }
                            
            return [
                'success' => true,
                'payment_intent_id' => $mpResult['payment_intent_id'],
                'transaction_id' => $mpResult['mp_payment_id'],
                'status' => $mpResult['status'],
                'status_detail' => $mpResult['status_detail'] ?? '',
                'message' => $this->getCardPaymentMessage($mpResult['status']),
                'card_last_digits' => $mpResult['card_last_digits'] ?? '',
                'card_brand' => $mpResult['card_brand'] ?? '',
                'approved' => $mpResult['approved'],
                'installments' => 1,
            ];
            
        } catch (\Exception $e) {
        
            Log::error('Erro ao processar cartão para Invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new PaymentException("Erro no processamento do cartão: " . $e->getMessage(), 400, null, 'CREATE_CARD_PAYMENT_FAILED');
        }
    }
    
    // =========================================================
    // WEBHOOKS - PROCESSAMENTO
    // =========================================================
    
    /**
     * Processar webhook de pagamento aprovado
     * 
     * IMPORTANTE: Aqui acontece a restauração de limite!
     * 
     */

    public function processPaymentConfirmation(PaymentIntent $intent, array $mpData): void
    {
        // 1. Lock para evitar Race Condition (Webhook vs Usuário atualizando tela)
        DB::transaction(function () use ($intent, $mpData) {
            
            // Bloqueia o registro para leitura/escrita
            $intent->refresh()->lockForUpdate();

            // IDEMPOTÊNCIA: Se já estiver aprovado e processado, sai.
            if ($intent->status === 'approved' && $intent->payment_id) {
                return;
            }

            // Validar status do payload do MP
            $status = $mpData['status'] ?? $intent->status;
            if ($status !== 'approved') {
                // Se não for aprovado, apenas atualiza o status do intent e sai
                $intent->update([
                    'status' => $status,
                    'status_detail' => $mpData['status_detail'] ?? null,
                    'mp_response' => $mpData
                ]);
                return;
            }


            // 2. Atualizar Intent
            $intent->markAsApproved($mpData['transaction_amount'] ?? $intent->amount);
            $intent->update([
                'mp_response' => $mpData,
                'mp_payment_id' => $mpData['id'] ?? $intent->mp_payment_id,
            ]);

            // 3. Criar ou Recuperar o Pagamento Financeiro
            // Verifica se já existe Payment com este ID do gateway para evitar duplicação
            $payment = Payment::where('gateway_transaction_id', (string) $mpData['id'])->first();

            if (!$payment) {    
                $payment = Payment::create([
                    'order_id' => $intent->order_id,
                    'invoice_id' => $intent->invoice_id,
                    'method' => $intent->payment_method === 'pix' ? 'pix' : 'credito',
                    'amount' => $mpData['transaction_amount'],
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'installments' => $intent->installments ?? $mpData['installments'] ?? 1,
                    
                    // Dados de Auditoria MP
                    'gateway_name' => 'mercadopago',
                    'gateway_transaction_id' => $mpData['id'],
                    'authorized_at' => Carbon::parse($mpData['date_approved'] ?? now()),
                    'mp_payment_id' => $mpData['id'],
                    'mp_payment_intent_id' => $intent->id,
                    'mp_status' => $mpData['status'],
                    'mp_status_detail' => $mpData['status_detail'] ?? null,
                    'mp_payment_type_id' => $mpData['payment_type_id'] ?? null,
                    'mp_payment_method_id' => $mpData['payment_method_id'] ?? null,
                    'mp_transaction_amount' => $mpData['transaction_amount'],
                    'mp_response' => $mpData,
                ]);
                
                // Vincula ao Intent
                $intent->payment()->associate($payment);
                $intent->save();
            }

            // 4. Efeitos Colaterais (Baixa de Pedido/Fatura + Crédito)
            
            if ($intent->order_id) {
                $this->finalizeOrder($intent->order, $payment);
            }
            
            if ($intent->invoice_id) {
                $this->finalizeInvoice($intent->invoice, $payment, $intent);
            }

            Log::info('Pagamento processado com sucesso (Centralizado)', [
                'intent_id' => $intent->id,
                'payment_id' => $payment->id
            ]);
        });
    }

    /**
     * Lógica específica de finalização de Pedido
     */
    private function finalizeOrder(Order $order, Payment $payment): void
    {
            $status = match($order->origin) {
                'pdv' => 'delivered',
                default => 'ready',
            };

            $is_invoiced = match($order->origin){
                'app' => true,
                default => false
            };
            
            $order->update([
                'payment_intent_id' => $payment->mp_payment_intent_id,
                'awaiting_external_payment' => false,
                'status' => $status,
                'paid_at' => now(),
                'is_invoiced' => $is_invoiced
            ]);


            if($order->origin === 'app'){
                try{

                    $filho = $order->filho;

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
            
    }

    /**
     * Lógica específica de finalização de Fatura + CRÉDITO
     */
    private function finalizeInvoice(Invoice $invoice, Payment $payment, PaymentIntent $intent): void
    {
        // Trava a invoice para recalculo
        $invoice = Invoice::where('id', $invoice->id)->lockForUpdate()->first();
       
        $currentPaid = $invoice->payments()
            ->where('status', 'confirmed')
            ->where('id', '!=', $payment->id) 
            ->sum('amount');
            
        // Recalcular total pago incluindo o atual
        $totalPaid = $currentPaid + $payment->amount;
        
        $invoice->paid_amount = $totalPaid;
        
        if ($invoice->paid_amount >= ($invoice->total_amount - 0.01)) { 
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            
        } else {
            $invoice->status = 'partial';
        }
        
        $invoice->save();

        // === PONTO CRÍTICO: RESTAURAÇÃO DE CRÉDITO ===
        // Só restaura se a fatura foi totalmente paga e de consumo
        if ($invoice->status === 'paid' && $invoice->type === 'consumption') {
            try {
                $this->creditRestoration->restoreCredit($invoice->filho, $invoice);
                Log::info("Crédito restaurado para invoice {$invoice->id}");
            } catch (\Exception $e) {
                
                Log::critical('CRÍTICO: Pagamento confirmado, mas falha ao restaurar crédito.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
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
        string $cardToken,
        $payer,
        string $paymentMethodId,
        int $installments
    ): array {
        $filho = $invoice->filho;
        $user = $filho->user;

        return [
            'transaction_amount' => (float) $invoice->total_amount,
            'token' => $cardToken,
            'description' => "Fatura #{$invoice->invoice_number} - Mensalidade Mãos Estendidas",
            'installments' => $installments,
            'payment_method_id' => $paymentMethodId, 
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => $payer['identification']['type'],
                    'number' => preg_replace('/\D/', '', $payer['identification']['number']),
                ],
            ],
            'notification_url' => route('api.webhooks.mercadopago'),
            'external_reference' => "invoice_{$invoice->id}",
            'metadata' => [
                'entity_type' => 'invoice',
                'entity_id' => $invoice->id,
                'order_number' => $invoice->invoice_number,
                'filho_id' => $filho->id,
                'integration_type' => 'checkout_card',
            ],
        ];
    }

    /**
     * Verificar status de pagamento
     */
    public function checkPaymentStatus(PaymentIntent $intent): array
    {
        try {
            $mpResponse = $this->mercadoPago->getPayment($intent->mp_payment_id);

            // VERIFICAÇÃO IMEDIATA
            if (($mpResponse['status'] ?? '') === 'approved') {
                $this->processPaymentConfirmation($intent, $mpResponse ?? []);
            }

            return [
                'success' => true,
                'status' => $mpResponse['status'],
                'approved' => $intent->is_approved,
            ];

        } catch (MercadoPagoException $e) {
            Log::error('Erro ao verificar status do pagamento', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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