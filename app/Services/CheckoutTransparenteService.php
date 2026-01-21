<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Payment;
use App\Exceptions\MercadoPagoException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service para Checkout Transparente (App PWA)
 * 
 * Responsabilidades:
 * - Processar pagamentos PIX
 * - Processar pagamentos com Cartão
 * - Criar PaymentIntent
 * - Verificar status de pagamento
 * - Cancelar pagamentos
 */
class CheckoutTransparenteService
{
    public function __construct(
        protected MercadoPagoService $mercadoPago
    ) {}

    // =========================================================
    // PIX
    // =========================================================

    /**
     * Criar pagamento PIX
     * 
     * @param Order $order Pedido a ser pago
     * @return array Dados do PIX (QR Code, etc)
     */
    public function createPixPayment(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            // Validar pedido
            $this->validateOrder($order);

            // Preparar dados para Mercado Pago
            $mpData = $this->buildPixPaymentData($order);

            try {
                // Criar payment no Mercado Pago
                $mpResponse = $this->mercadoPago->createPayment($mpData);

                // Criar PaymentIntent
                $intent = $this->createPaymentIntent($order, 'pix', $mpData, $mpResponse);

            } catch (MercadoPagoException $e) {
               
                 Log::error('Erro Mercado Pago', [
                    'error' => $e->getMessage(),
                    'mp_response' => $e->getMercadoPagoResponse(),
                ]);
                throw new Exception('Erro ao gerar PIX: ' . $e->getMessage());
            }

                
                // Atualizar order
                $order->update([
                    'payment_intent_id' => $intent->id,
                    'awaiting_external_payment' => true,
                    'payment_method_chosen' => 'mercadopago_pix',
                    'status' => 'pending',
                ]);


                return [
                    'success' => true,
                    'payment_intent_id' => $intent->id,
                    'mp_payment_id' => $mpResponse['id'],
                    'status' => $mpResponse['status'] ?? 'pending',
                    'pix' => [
                        'qr_code' => $mpResponse['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                        'qr_code_base64' => $mpResponse['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                        'ticket_url' => $mpResponse['point_of_interaction']['transaction_data']['ticket_url'] ?? '',
                        'expiration_date' => $this->getPixExpirationDate($mpResponse),
                    ],
                ];

            
        });
    }

    /**
     * Construir dados do pagamento PIX para MP
     */
    protected function buildPixPaymentData(Order $order): array
    {
        $filho = $order->filho;
        $user = $filho->user;

        return [
            'transaction_amount' => (float) $order->total,
            'description' => "Pedido #{$order->order_number} - Mãos Estendidas",
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $user->email,
                'first_name' => explode(' ', $user->name)[0] ?? $user->name,
                'last_name' => substr($user->name, strlen(explode(' ', $user->name)[0])) ?: $user->name,
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', $filho->cpf),
                ],
            ],
            'notification_url' => 'https://eomlimb9byboaq8.m.pipedream.net', //route('api.webhooks.mercadopago'),
            'external_reference' => $order->id,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'filho_id' => $filho->id,
                'integration_type' => 'checkout_pix',
            ],
        ];
    }

    // =========================================================
    // CARTÃO DE CRÉDITO
    // =========================================================

    /**
     * Criar pagamento com Cartão
     * 
     * @param Order $order Pedido
     * @param string $cardToken Token do cartão (gerado no frontend)
     * @param int $installments Parcelas
     * @return array Resultado do pagamento
     */
    public function createCardPayment(
        Order $order,
        string $cardToken,
        int $installments = 1
    ): array {
        return DB::transaction(function () use ($order, $cardToken, $installments) {
            // Validar pedido
            $this->validateOrder($order);

            // Preparar dados para Mercado Pago
            $mpData = $this->buildCardPaymentData($order, $cardToken, $installments);

            try {
                // Criar payment no Mercado Pago
                $mpResponse = $this->mercadoPago->createPayment($mpData);

                // Criar PaymentIntent
                $intent = $this->createPaymentIntent($order, 'credit_card', $mpData, $mpResponse);

                // Atualizar order
                $order->update([
                    'payment_intent_id' => $intent->id,
                    'awaiting_external_payment' => true,
                    'payment_method_chosen' => 'mercadopago_card',
                    'status' => $mpResponse['status'] === 'approved' ? 'paid' : 'pending',
                ]);

                Log::info('Checkout Cartão criado', [
                    'order_id' => $order->id,
                    'mp_payment_id' => $mpResponse['id'],
                    'status' => $mpResponse['status'],
                    'installments' => $installments,
                ]);

                // Se já foi aprovado, processar
                if ($mpResponse['status'] === 'approved') {
                    $this->processApprovedPayment($intent, $mpResponse);
                }

                return [
                    'success' => true,
                    'payment_intent_id' => $intent->id,
                    'mp_payment_id' => $mpResponse['id'],
                    'status' => $mpResponse['status'],
                    'status_detail' => $mpResponse['status_detail'] ?? null,
                    'approved' => $mpResponse['status'] === 'approved',
                ];

            } catch (MercadoPagoException $e) {
                Log::error('Erro ao criar pagamento com cartão', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Construir dados do pagamento com cartão para MP
     */
    protected function buildCardPaymentData(Order $order, string $cardToken, int $installments): array
    {
        $filho = $order->filho;
        $user = $filho->user;

        return [
            'transaction_amount' => (float) $order->total,
            'token' => $cardToken,
            'description' => "Pedido #{$order->order_number} - Mãos Estendidas",
            'installments' => $installments,
            'payment_method_id' => 'credit_card', // Será determinado pelo token
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', $filho->cpf),
                ],
            ],
            'notification_url' => route('api.webhooks.mercadopago'),
            'external_reference' => $order->id,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'filho_id' => $filho->id,
                'integration_type' => 'checkout_card',
            ],
        ];
    }

    // =========================================================
    // PAYMENT INTENT
    // =========================================================

    /**
     * Criar PaymentIntent
     */
    protected function createPaymentIntent(
        Order $order,
        string $paymentMethod,
        array $mpRequest,
        array $mpResponse
    ): PaymentIntent {

    try{
        $data = [
            'order_id' => $order->id,
            'mp_payment_id' => $mpResponse['id'],
            'integration_type' => 'checkout',
            'payment_method' => $paymentMethod,
            'amount' => $order->total,
            'status' => $this->mapMercadoPagoStatus($mpResponse['status']),
            'status_detail' => $mpResponse['status_detail'] ?? null,
            'mp_request' => $mpRequest,
            'mp_response' => $mpResponse,
            'created_at_mp' => isset($mpResponse['date_created']) 
                ? Carbon::parse($mpResponse['date_created']) 
                : now(),
        ];

        // Dados específicos PIX
        if ($paymentMethod === 'pix') {
            $transactionData = $mpResponse['point_of_interaction']['transaction_data'] ?? [];
            
            $data['pix_qr_code'] = $transactionData['qr_code'] ?? null;
            $data['pix_qr_code_base64'] = $transactionData['qr_code_base64'] ?? null;
            $data['pix_ticket_url'] = $transactionData['ticket_url'] ?? null;
            $data['pix_expiration'] = $this->getPixExpirationDate($mpResponse);
        }

        // Dados específicos Cartão
        if ($paymentMethod === 'credit_card') {
            $data['card_last_digits'] = $mpResponse['card']['last_four_digits'] ?? null;
            $data['card_brand'] = $mpResponse['payment_type_id'] ?? null;
            $data['installments'] = $mpResponse['installments'] ?? 1;
        }

        return PaymentIntent::create($data);
        }catch(\Exception $e){
                    \Log::debug('Erro CreatePaymentIntent:' . $e->getMessage());
        }
    }

    // =========================================================
    // STATUS E VALIDAÇÕES
    // =========================================================

    /**
     * Validar pedido
     */
    protected function validateOrder(Order $order): void
    {
        if (!$order->filho_id) {
            throw new MercadoPagoException('Pedido não pertence a um filho válido');
        }

        if ($order->status === 'paid') {
            throw new MercadoPagoException('Pedido já foi pago');
        }

        if ($order->status === 'cancelled') {
            throw new MercadoPagoException('Pedido foi cancelado');
        }

        if ($order->total <= 0) {
            throw new MercadoPagoException('Valor do pedido inválido');
        }
    }

    /**
     * Mapear status do MP para status interno
     */
    protected function mapMercadoPagoStatus(string $mpStatus): string
    {
        return match($mpStatus) {
            'pending' => 'pending',
            'approved' => 'approved',
            'authorized' => 'approved',
            'in_process' => 'processing',
            'in_mediation' => 'processing',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'refunded',
            default => 'error',
        };
    }

    /**
     * Obter data de expiração do PIX
     */
    protected function getPixExpirationDate(array $mpResponse): ?Carbon
    {
        if (isset($mpResponse['date_of_expiration'])) {
            return Carbon::parse($mpResponse['date_of_expiration']);
        }

        // PIX expira em 30 minutos por padrão
        return now()->addMinutes(30);
    }

    /**
     * Verificar status de pagamento
     */
    public function checkPaymentStatus(PaymentIntent $intent): array
    {
        try {
            $mpResponse = $this->mercadoPago->getPayment($intent->mp_payment_id);

            // Atualizar intent
            $intent->update([
                'status' => $this->mapMercadoPagoStatus($mpResponse['status']),
                'status_detail' => $mpResponse['status_detail'] ?? null,
                'mp_response' => $mpResponse,
            ]);

            // Se foi aprovado, processar
            if ($mpResponse['status'] === 'approved' && !$intent->is_approved) {
                $this->processApprovedPayment($intent, $mpResponse);
            }

            return [
                'success' => true,
                'status' => $intent->status,
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

    /**
     * Processar pagamento aprovado
     */
    public function processApprovedPayment(PaymentIntent $intent, array $mpResponse): void
    {
        DB::transaction(function () use ($intent, $mpResponse) {
            $order = $intent->order;

            // Marcar intent como aprovado
            $intent->markAsApproved($mpResponse['transaction_amount'] ?? $intent->amount);

            // Criar Payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $intent->is_pix ? 'pix' : 'credito',
                'amount' => $intent->amount_paid,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                
                // Campos MP
                'mp_payment_id' => $mpResponse['id'],
                'mp_payment_intent_id' => $intent->id,
                'mp_status' => $mpResponse['status'],
                'mp_status_detail' => $mpResponse['status_detail'] ?? null,
                'mp_payment_type_id' => $mpResponse['payment_type_id'] ?? null,
                'mp_payment_method_id' => $mpResponse['payment_method_id'] ?? null,
                'mp_transaction_amount' => $mpResponse['transaction_amount'] ?? null,
                'mp_net_received_amount' => $mpResponse['transaction_details']['net_received_amount'] ?? null,
                'mp_response' => $mpResponse,
                'mp_webhook_received_at' => now(),
            ]);

            // Vincular payment ao intent
            $intent->update(['payment_id' => $payment->id]);

            // Atualizar order
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'awaiting_external_payment' => false,
            ]);

            Log::info('Pagamento aprovado processado', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
            ]);
        });
    }

    /**
     * Cancelar pagamento
     */
    public function cancelPayment(PaymentIntent $intent): void
    {
        DB::transaction(function () use ($intent) {
            // Cancelar no MP (se possível)
            try {
                if ($intent->mp_payment_id && !$intent->is_approved) {
                    // Nota: MP não permite cancelar PIX, apenas reembolsar após pago
                    // Para cartão, pode cancelar se ainda estiver pendente
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao cancelar no MP', [
                    'intent_id' => $intent->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Marcar intent como cancelado
            $intent->markAsCancelled('Cancelado pelo usuário');

            // Atualizar order
            $intent->order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Pagamento cancelado',
                'awaiting_external_payment' => false,
            ]);

        
        });
    }
}