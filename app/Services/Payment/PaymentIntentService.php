<?php

namespace App\Services\Payment;

use App\Events\PaymentApproved;
use App\Exceptions\PaymentPDVException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service para gerenciar Payment Intents
 * Orquestra criação de PIX via Mercado Pago e pagamentos manuais (crédito/débito)
 */
class PaymentIntentService
{
    public function __construct(
        private readonly MercadoPagoService $mercadoPagoService
    ) {}

    /**
     * Criar Payment Intent baseado no método de pagamento
     * 
     * @param Order $order
     * @param array $data
     * @return PaymentIntent
     * @throws PaymentPDVException
     */
    public function createIntent(Order $order, array $data): PaymentIntent
    {
        // Validar que pedido não foi pago
        if ($order->isPaid()) {
            throw new PaymentPDVException('Pedido já foi pago.');
        }

        // Validar que não há intent pendente
        $this->cancelPendingIntents($order);

        // Criar intent baseado no método
        $paymentMethod = $data['payment_method'];

        return match($paymentMethod) {
            'pix' => $this->createPixIntent($order, $data),
           // 'credit_card', 'debit_card' => $this->createManualCardIntent($order, $data),
            default => throw new PaymentPDVException("Método de pagamento inválido: {$paymentMethod}"),
        };
    }

    /**
     * Criar Payment Intent para PIX via Mercado Pago
     */
    protected function createPixIntent(Order $order, array $data): PaymentIntent
    {
        // Validar que Mercado Pago está configurado
        if (!$this->mercadoPagoService->isConfigured()) {
            throw new PaymentPDVException(
                'Mercado Pago não configurado. Configure em Admin > Configurações > Pagamentos.'
            );
        }

        DB::beginTransaction();
        try {
            // Criar Payment Intent local
            $intent = PaymentIntent::create([
                'order_id' => $order->id,
                'integration_type' => 'checkout',
                'payment_method' => 'pix',
                'amount' => $data['amount'],
                'status' => 'pending',
                'pix_expiration' => now()->addMinutes($data['pix_expiration_minutes'] ?? 30),
            ]);

            // Preparar dados para Mercado Pago
            $mpPayload = $this->buildMercadoPagoPixPayload($order, $data);

            // Log da requisição
            Log::info('PaymentIntent - Criando PIX no Mercado Pago', [
                'intent_id' => $intent->id,
                'order_id' => $order->id,
                'amount' => $data['amount'],
            ]);

            // Criar pagamento no Mercado Pago
            $intent->update(['mp_request' => $mpPayload]);
            $intent->incrementAttempts();

            $mpResponse = $this->mercadoPagoService->createPayment($mpPayload);

            // Atualizar intent com resposta do MP
            $intent->update([
                'mp_payment_id' => $mpResponse['id'],
                'mp_response' => $mpResponse,
                'status' => $this->mapMercadoPagoStatus($mpResponse['status']),
                'status_detail' => $mpResponse['status_detail'] ?? null,
                'pix_qr_code' => $mpResponse['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                'pix_qr_code_base64' => $mpResponse['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                'pix_ticket_url' => $mpResponse['point_of_interaction']['transaction_data']['ticket_url'] ?? null,
                'created_at_mp' => now(),
            ]);

            // Marcar pedido como aguardando pagamento externo
            $order->update([
                'payment_intent_id' => $intent->id,
                'awaiting_external_payment' => true,
                'payment_method_chosen' => 'pix',
                'status' => 'pending',
            ]);

            DB::commit();

            Log::info('PaymentIntent - PIX criado com sucesso', [
                'intent_id' => $intent->id,
                'mp_payment_id' => $intent->mp_payment_id,
                'qr_code_exists' => !empty($intent->pix_qr_code),
            ]);

            return $intent->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($intent)) {
                $intent->markAsError($e->getMessage());
            }

            Log::error('PaymentIntent - Erro ao criar PIX', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new PaymentPDVException(
                'Erro ao gerar PIX: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Criar Payment Intent para pagamentos manuais (crédito/débito)
     * Esses pagamentos já foram processados na maquininha POS
     */
    protected function createManualCardIntent(Order $order, array $data): PaymentIntent
    {
        DB::beginTransaction();
        try {
            // Criar Payment Intent como "aprovado" imediatamente
            $intent = PaymentIntent::create([
                'order_id' => $order->id,
                'integration_type' => 'manual_pos',
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'amount_paid' => $data['amount'],
                'status' => 'approved',
                'status_detail' => 'Pagamento manual via POS',
                'installments' => $data['installments'] ?? 1,
                'approved_at' => now(),
            ]);

            // Criar Payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $data['payment_method'] === 'credit_card' ? 'credito' : 'debito',
                'amount' => $data['amount'],
                'status' => 'confirmed',
                'installments' => $data['installments'] ?? 1,
                'gateway_transaction_id' => $data['pos_transaction_id'] ?? null,
                'gateway_name' => 'manual_pos',
                'confirmed_at' => now(),
            ]);

            // Atualizar intent com payment
            $intent->update(['payment_id' => $payment->id]);

            // Marcar pedido como pago
            $order->update([
                'payment_intent_id' => $intent->id,
                'awaiting_external_payment' => false,
                'payment_method_chosen' => $data['payment_method'],
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            DB::commit();

            // Disparar evento de pagamento aprovado (para consistência)
            event(new PaymentApproved($intent));

            Log::info('PaymentIntent - Pagamento manual criado', [
                'intent_id' => $intent->id,
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
            ]);

            return $intent->fresh();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PaymentIntent - Erro ao criar pagamento manual', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentPDVException(
                'Erro ao processar pagamento manual: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Cancelar payment intents pendentes do pedido
     */
    protected function cancelPendingIntents(Order $order): void
    {
        $pendingIntents = PaymentIntent::where('order_id', $order->id)
            ->whereIn('status', ['created', 'pending', 'processing'])
            ->get();

        foreach ($pendingIntents as $intent) {
            $intent->markAsCancelled('Novo intent criado');
            
            Log::info('PaymentIntent - Intent pendente cancelado', [
                'intent_id' => $intent->id,
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Construir payload para Mercado Pago PIX
     */
    protected function buildMercadoPagoPixPayload(Order $order, array $data): array
    {
        $expirationMinutes = $data['pix_expiration_minutes'] ?? 30;
        $user = auth()->user();

         return [
            'transaction_amount' => (float) $order->total,
            'description' => "Pedido #{$order->order_number} - Mãos Estendidas PDV",
            'payment_method_id' => 'pix',
            'external_reference' => "order_{$order->id}",
            'payer' => [
                'email' => 'suporte@paitonayede.com.br',
                'first_name' => explode(' ', $user->name)[0] ?? $user->name,
                'last_name' => substr($user->name, strlen(explode(' ', $user->name)[0])) ?: $user->name,
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', '449.765.008-10'),
                ],
            ],
            'notification_url' => "https://eomlimb9byboaq8.m.pipedream.net",  //route('api.webhooks.mercadopago'),
            
            'metadata' => [
                'entity_type' => 'order',
                'entity_id' => $order->id,
                'order_number' => $order->order_number,
                'origin' => 'pdv',
                'integration_type' => 'checkout_pix',
            ],
        ];
        
    }

    /**
     * Mapear status do Mercado Pago para status interno
     */
    protected function mapMercadoPagoStatus(string $mpStatus): string
    {
        return match($mpStatus) {
            'pending' => 'pending',
            'approved' => 'approved',
            'authorized' => 'processing',
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
     * Gerar email a partir do CPF (para Mercado Pago)
     */
    protected function generateEmailFromCpf(string $cpf): string
    {
        $sanitized = preg_replace('/\D/', '', $cpf);
        return "cliente{$sanitized}@maosestendidas.local";
    }

    /**
     * Sanitizar CPF
     */
    protected function sanitizeCpf(?string $cpf): string
    {
        if (!$cpf) {
            return '00000000000';
        }

        $sanitized = preg_replace('/\D/', '', $cpf);
        return str_pad($sanitized, 11, '0', STR_PAD_LEFT);
    }

    /**
     * Buscar status atual de um Payment Intent no Mercado Pago
     */
    public function checkIntentStatus(PaymentIntent $intent): PaymentIntent
    {
        // Se for pagamento manual, já está aprovado
        if ($intent->integration_type === 'manual_pos') {
            return $intent;
        }

        // Se não tem mp_payment_id, não pode consultar
        if (!$intent->mp_payment_id) {
            throw new PaymentPDVException('Payment Intent sem ID do Mercado Pago');
        }

        try {
            // Consultar status no Mercado Pago
            $mpResponse = $this->mercadoPagoService->getPayment($intent->mp_payment_id);

            // Atualizar status local
            $newStatus = $this->mapMercadoPagoStatus($mpResponse['status']);
            
            $intent->update([
                'status' => $newStatus,
                'status_detail' => $mpResponse['status_detail'] ?? null,
                'mp_response' => $mpResponse,
            ]);

            // Se foi aprovado, processar aprovação
            if ($newStatus === 'approved' && $intent->status !== 'approved') {
                $this->processApproval($intent, $mpResponse);
            }

            Log::info('PaymentIntent - Status atualizado via polling', [
                'intent_id' => $intent->id,
                'status' => $newStatus,
                'mp_payment_id' => $intent->mp_payment_id,
            ]);

            return $intent->fresh();

        } catch (\Exception $e) {
            Log::error('PaymentIntent - Erro ao consultar status', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentPDVException(
                'Erro ao verificar status do pagamento: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Processar aprovação do pagamento
     */
    public function processApproval(PaymentIntent $intent, array $mpResponse): void
    {
        DB::beginTransaction();
        try {
            // Atualizar intent
            $intent->markAsApproved($mpResponse['transaction_amount'] ?? $intent->amount);

            // Criar Payment record se não existe
            if (!$intent->payment_id) {
                $payment = Payment::create([
                    'order_id' => $intent->order_id,
                    'method' => 'pix',
                    'amount' => $intent->amount_paid,
                    'status' => 'confirmed',
                    'mp_payment_id' => $intent->mp_payment_id,
                    'mp_status' => $mpResponse['status'],
                    'mp_status_detail' => $mpResponse['status_detail'] ?? null,
                    'mp_response' => $mpResponse,
                    'confirmed_at' => now(),
                ]);

                $intent->update(['payment_id' => $payment->id]);
            }

            // Atualizar pedido
            $intent->order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'awaiting_external_payment' => false,
            ]);

            DB::commit();

            // Disparar evento WebSocket
            event(new PaymentApproved($intent));

            Log::info('PaymentIntent - Pagamento aprovado e processado', [
                'intent_id' => $intent->id,
                'order_id' => $intent->order_id,
                'mp_payment_id' => $intent->mp_payment_id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PaymentIntent - Erro ao processar aprovação', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentPDVException(
                'Erro ao processar aprovação do pagamento: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Cancelar Payment Intent
     */
    public function cancelIntent(PaymentIntent $intent, string $reason = 'Cancelado pelo operador'): PaymentIntent
    {
        // Se já foi aprovado, não pode cancelar
        if ($intent->is_approved) {
            throw new PaymentPDVException('Não é possível cancelar pagamento já aprovado');
        }

        DB::beginTransaction();
        try {
            // Cancelar no Mercado Pago se for PIX
            if ($intent->is_pix && $intent->mp_payment_id) {
                try {
                    // MP não tem endpoint de cancelamento para PIX pending
                    // Apenas deixar expirar naturalmente
                    Log::info('PaymentIntent - PIX deixado expirar naturalmente', [
                        'intent_id' => $intent->id,
                        'mp_payment_id' => $intent->mp_payment_id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('PaymentIntent - Erro ao cancelar no MP', [
                        'intent_id' => $intent->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Cancelar localmente
            $intent->markAsCancelled($reason);

            // Atualizar pedido
            $intent->order->update([
                'awaiting_external_payment' => false,
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            DB::commit();

            Log::info('PaymentIntent - Intent cancelado', [
                'intent_id' => $intent->id,
                'reason' => $reason,
            ]);

            return $intent->fresh();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PaymentIntent - Erro ao cancelar intent', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentPDVException(
                'Erro ao cancelar pagamento: ' . $e->getMessage(),
                previous: $e
            );
        }
    }
}