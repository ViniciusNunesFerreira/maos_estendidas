<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\Payment;
use App\Exceptions\MercadoPagoException;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Service para Checkout Transparente (Pagamentos Externos)
 * 
 * VERSÃO 2.0 - UNIFICADO E CORRIGIDO
 * 
 * @version 2.0
 * @author Sistema Mãos Estendidas
 * @date Janeiro 2026
 */
class CheckoutTransparenteService
{
    public function __construct(
        protected MercadoPagoService $mercadoPago
    ) {}

    // =========================================================
    // PIX - ORDERS
    // =========================================================

    /**
     * Criar pagamento PIX para Order
     * 
     * @param Order $order Pedido a ser pago
     * @return array Dados do PIX (QR Code, payment_intent_id, etc)
     * @throws PaymentException
     * @throws MercadoPagoException
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

                // Validar resposta
                if (!isset($mpResponse['id'])) {
                    throw new MercadoPagoException('Resposta inválida do Mercado Pago: ID não retornado');
                }

                // Criar PaymentIntent
                $intent = $this->createPaymentIntent(
                    order: $order,
                    invoice: null,
                    paymentMethod: 'pix',
                    mpRequest: $mpData,
                    mpResponse: $mpResponse
                );

                // Atualizar order
                $order->update([
                    'payment_intent_id' => $intent->id,
                    'awaiting_external_payment' => true,
                    'payment_method_chosen' => 'mercadopago_pix',
                    'status' => 'pending',
                ]);

               
                // Retorno padronizado
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

            } catch (MercadoPagoException $e) {
                Log::error('Erro Mercado Pago ao criar PIX para Order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    // =========================================================
    // PIX - INVOICES (NOVO!)
    // =========================================================

    public function createPixPaymentForInvoice(Invoice $invoice, array $mpData): array
    {
        return DB::transaction(function () use ($invoice, $mpData) {
            // 1. Validar invoice
            $this->validateInvoice($invoice);

            // --- VERIFICAÇÃO DE IDEMPOTÊNCIA ---
            $existingIntent = PaymentIntent::where('invoice_id', $invoice->id)
                ->where('payment_method', 'pix')
                ->where('status', 'pending')
                ->where('pix_expiration', '>', now())
                ->first();

            if ($existingIntent && isset($existingIntent->mp_response['id'])) {
                Log::info('Reutilizando PIX existente para Invoice', ['invoice_id' => $invoice->id]);
                return $this->formatMpResponse($existingIntent->mp_response, $existingIntent);
            }
            // ---------------------------------------------

            try {
                // Criar payment no Mercado Pago
                $mpResponse = $this->mercadoPago->createPayment($mpData);

                if (!isset($mpResponse['id'])) {
                    throw new MercadoPagoException('Resposta inválida do Mercado Pago: ID não retornado');
                }

                // 2. Usar UPDATE OR CREATE para evitar o erro de duplicidade
                // Caso o mp_payment_id já exista (por uma falha de rede anterior), ele apenas atualiza
                $intent = PaymentIntent::updateOrCreate(
                    ['mp_payment_id' => $mpResponse['id']], // Chave de busca (Unique)
                    [
                        'payment_method' => 'pix',
                        'amount' => $mpData['transaction_amount'],
                        'integration_type' => 'checkout',
                        'status' => 'pending',
                        'pix_qr_code' => $mpResponse['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                        'pix_qr_code_base64' => $mpResponse['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                        'pix_expiration' => $this->getPixExpirationDate($mpResponse),
                        'mp_request' => $mpData,
                        'mp_response' => $mpResponse,
                    ]
                );

                Log::info('PIX criado/atualizado para Invoice', [
                    'invoice_id' => $invoice->id,
                    'payment_intent_id' => $intent->id,
                    'mp_payment_id' => $mpResponse['id'],
                ]);

                return $this->formatMpResponse($mpResponse, $intent);

            } catch (MercadoPagoException $e) {
                Log::error('Erro Mercado Pago ao criar PIX para Invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Helper para formatar o retorno e evitar código duplicado
     */
    private function formatMpResponse(array $mpResponse, $intent): array
    {
        return [
            'success' => true,
            'payment_intent_id' => $intent->id,
            'payment_id' => $mpResponse['id'], 
            'mp_payment_id' => $mpResponse['id'],
            'status' => $mpResponse['status'] ?? 'pending',
            'qr_code' => $mpResponse['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'qr_code_base64' => $mpResponse['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            'ticket_url' => $mpResponse['point_of_interaction']['transaction_data']['ticket_url'] ?? '',
            'expiration_date' => $intent->pix_expiration ? $intent->pix_expiration->toIso8601String() : $this->getPixExpirationDate($mpResponse),
        ];
    }


    // =========================================================
    // CARTÃO - ORDERS (CORRIGIDO!)
    // =========================================================

    public function createCardPayment(
        Order $order,
        string $cardToken,
        string $paymentMethodId,
        int $installments = 1
    ): array {
        return DB::transaction(function () use ($order, $cardToken, $paymentMethodId, $installments) {
            // Validar pedido
            $this->validateOrder($order);

            // Validar parâmetros
            if (empty($cardToken)) {
                throw new PaymentException('Token do cartão é obrigatório', 400);
            }

            if ($installments < 1 || $installments > 12) {
                throw new PaymentException('Número de parcelas inválido (1-12)', 400);
            }

            // Preparar dados para Mercado Pago
            $mpData = $this->buildCardPaymentData(
                $order,
                $cardToken,
                $paymentMethodId,
                $installments
            );

            try {
                // Criar payment no Mercado Pago
                $mpResponse = $this->mercadoPago->createPayment($mpData);

                // Validar resposta
                if (!isset($mpResponse['id'])) {
                    throw new MercadoPagoException('Resposta inválida do Mercado Pago: ID não retornado');
                }

                // Criar PaymentIntent
                $intent = $this->createPaymentIntent(
                    order: $order,
                    invoice: null,
                    paymentMethod: 'credit_card',
                    mpRequest: $mpData,
                    mpResponse: $mpResponse
                );

                // Atualizar order
                $order->update([
                    'payment_intent_id' => $intent->id,
                    'awaiting_external_payment' => true,
                    'payment_method_chosen' => 'mercadopago_card',
                    'status' => $mpResponse['status'] === 'approved' ? 'paid' : 'pending',
                ]);

                Log::info('Cartão criado para Order', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $intent->id,
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
                Log::error('Erro ao criar pagamento com cartão para Order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    // =========================================================
    // CARTÃO - INVOICES (NOVO!)
    // =========================================================

    public function createCardPaymentForInvoice(Invoice $invoice, array $mpData): array
    {
        return DB::transaction(function () use ($invoice, $mpData) {
            // Validar invoice
            $this->validateInvoice($invoice);

            try {
                // Criar payment no Mercado Pago
                $mpResponse = $this->mercadoPago->createPayment($mpData);

                // Validar resposta
                if (!isset($mpResponse['id'])) {
                    throw new MercadoPagoException('Resposta inválida do Mercado Pago: ID não retornado');
                }

                // Criar PaymentIntent
                $intent = $this->createPaymentIntent(
                    order: null,
                    invoice: $invoice,
                    paymentMethod: 'credit_card',
                    mpRequest: $mpData,
                    mpResponse: $mpResponse
                );

                Log::info('Cartão criado para Invoice', [
                    'invoice_id' => $invoice->id,
                    'payment_intent_id' => $intent->id,
                    'mp_payment_id' => $mpResponse['id'],
                    'status' => $mpResponse['status'],
                ]);

                // Se já foi aprovado, processar
                if ($mpResponse['status'] === 'approved') {
                    $this->processApprovedPayment($intent, $mpResponse);
                }

                // Retorno padronizado (compatível com ExternalPaymentService)
                return [
                    'success' => true,
                    'payment_intent_id' => $intent->id,
                    'payment_id' => $mpResponse['id'], // Alias
                    'mp_payment_id' => $mpResponse['id'],
                    'status' => $mpResponse['status'],
                    'status_detail' => $mpResponse['status_detail'] ?? null,
                    'approved' => $mpResponse['status'] === 'approved',
                    'card_last_digits' => $mpResponse['card']['last_four_digits'] ?? null,
                    'card_brand' => $mpResponse['payment_type_id'] ?? null,
                    'installments' => $mpResponse['installments'] ?? 1,
                ];

            } catch (MercadoPagoException $e) {
                Log::error('Erro ao criar pagamento com cartão para Invoice', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    // =========================================================
    // BUILD PAYMENT DATA
    // =========================================================

    /**
     * Construir dados do pagamento PIX para Order
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
            'notification_url' => "https://eomlimb9byboaq8.m.pipedream.net", // route('api.webhooks.mercadopago'),
            'external_reference' => "order_{$order->id}",
            'metadata' => [
                'entity_type' => 'order',
                'entity_id' => $order->id,
                'order_number' => $order->order_number,
                'filho_id' => $filho->id,
                'integration_type' => 'checkout_pix',
            ],
        ];
    }

    /**
     * Construir dados do pagamento com cartão para Order
     * 
     * CORRIGIDO: Agora aceita paymentMethodId
     */
    protected function buildCardPaymentData(
        Order $order,
        string $cardToken,
        string $paymentMethodId,
        int $installments
    ): array {
        $filho = $order->filho;
        $user = $filho->user;

        return [
            'transaction_amount' => (float) $order->total,
            'token' => $cardToken,
            'description' => "Pedido #{$order->order_number} - Mãos Estendidas",
            'installments' => $installments,
            'payment_method_id' => $paymentMethodId, // CORRIGIDO: Agora usa o parâmetro
            'payer' => [
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', $filho->cpf),
                ],
            ],
            'notification_url' => route('api.webhooks.mercadopago'),
            'external_reference' => "order_{$order->id}",
            'metadata' => [
                'entity_type' => 'order',
                'entity_id' => $order->id,
                'order_number' => $order->order_number,
                'filho_id' => $filho->id,
                'integration_type' => 'checkout_card',
            ],
        ];
    }

    // =========================================================
    // PAYMENT INTENT UNIFICADO
    // =========================================================

    /**
     * Criar PaymentIntent (funciona para Order OU Invoice)
     * 
     * UNIFICADO: Suporta ambas entidades
     */
    protected function createPaymentIntent(
        ?Order $order,
        ?Invoice $invoice,
        string $paymentMethod,
        array $mpRequest,
        array $mpResponse
    ): PaymentIntent {
        try {
            $data = [
                'order_id' => $order?->id,
                'invoice_id' => $invoice?->id,
                'mp_payment_id' => $mpResponse['id'],
                'integration_type' => 'checkout',
                'payment_method' => $paymentMethod,
                'amount' => $order?->total ?? $invoice?->total_amount ?? 0,
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

        } catch (Exception $e) {
            Log::error('Erro ao criar PaymentIntent', [
                'order_id' => $order?->id,
                'invoice_id' => $invoice?->id,
                'error' => $e->getMessage(),
            ]);
            throw new PaymentException('Erro ao criar PaymentIntent: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================
    // VALIDAÇÕES
    // =========================================================

    /**
     * Validar pedido (Order)
     */
    protected function validateOrder(Order $order): void
    {
        if (!$order->filho_id) {
            throw new PaymentException('Pedido não pertence a um filho válido', 400);
        }

        if ($order->status === 'paid') {
            throw PaymentException::alreadyPaid('order', $order->id);
        }

        if ($order->status === 'cancelled') {
            throw PaymentException::cancelled('order', $order->id, 'Pedido foi cancelado');
        }

        if ($order->total <= 0) {
            throw PaymentException::invalidAmount($order->total, 'Valor deve ser maior que zero');
        }
    }

    /**
     * Validar fatura (Invoice)
     */
    protected function validateInvoice(Invoice $invoice): void
    {
        if (!$invoice->filho_id) {
            throw new PaymentException('Fatura não pertence a um filho válido', 400);
        }

        if ($invoice->status === 'paid') {
            throw PaymentException::alreadyPaid('invoice', $invoice->id);
        }

        if ($invoice->status === 'cancelled') {
            throw PaymentException::cancelled('invoice', $invoice->id, 'Fatura foi cancelada');
        }

        if ($invoice->total_amount <= 0) {
            throw PaymentException::invalidAmount($invoice->total_amount, 'Valor deve ser maior que zero');
        }
    }

    // =========================================================
    // STATUS E HELPERS
    // =========================================================

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
     * Processar pagamento aprovado (funciona para Order e Invoice)
     */
    public function processApprovedPayment(PaymentIntent $intent, array $mpResponse): void
    {
        DB::transaction(function () use ($intent, $mpResponse) {
            $order = $intent->order;
            $invoice = $intent->invoice;

            // Marcar intent como aprovado
            $intent->markAsApproved($mpResponse['transaction_amount'] ?? $intent->amount);

            // Criar Payment
            $payment = Payment::create([
                'order_id' => $order?->id,
                'invoice_id' => $invoice?->id,
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

            // Atualizar Order OU Invoice
            if ($order) {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'awaiting_external_payment' => false,
                ]);
            }

            if ($invoice) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_amount' => $invoice->paid_amount + $intent->amount_paid,
                    'awaiting_external_payment' => false,
                ]);
            }

            Log::info('Pagamento aprovado processado', [
                'order_id' => $order?->id,
                'invoice_id' => $invoice?->id,
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
                }
            } catch (Exception $e) {
                Log::warning('Erro ao cancelar no MP', [
                    'intent_id' => $intent->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Marcar intent como cancelado
            $intent->markAsCancelled('Cancelado pelo usuário');

            // Atualizar Order ou Invoice
            if ($intent->order) {
                $intent->order->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'Pagamento cancelado',
                    'awaiting_external_payment' => false,
                ]);
            }

            if ($intent->invoice) {
                $intent->invoice->update([
                    'awaiting_external_payment' => false,
                ]);
            }
        });
    }
}