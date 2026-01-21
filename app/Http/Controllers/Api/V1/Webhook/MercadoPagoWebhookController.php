<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PaymentWebhook;
use App\Models\PaymentIntent;
use App\Services\CheckoutTransparenteService;
use App\Services\PointTefService;
use App\Services\MercadoPagoService;
use App\Jobs\ProcessMercadoPagoWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para receber e processar webhooks do Mercado Pago
 * 
 * Endpoints recebidos:
 * - payment.created
 * - payment.updated
 * - merchant_order
 */
class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mercadoPago,
        protected CheckoutTransparenteService $checkout,
        protected PointTefService $pointTef,
    ) {}

    /**
     * Receber notificação do Mercado Pago
     * 
     * POST /api/v1/webhooks/mercadopago
     */
    public function handle(Request $request): JsonResponse
    {
        // Log do webhook recebido
        Log::info('Webhook MP - Recebido', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            // Validar signature (segurança)
            if (!$this->validateSignature($request)) {
                Log::warning('Webhook MP - Signature inválida', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature',
                ], 403);
            }

            // Extrair dados do webhook
            $eventType = $request->input('type') ?? $request->input('topic');
            $action = $request->input('action');
            $data = $request->input('data');

            // Criar registro do webhook
            $webhook = $this->createWebhookRecord($request, $eventType, $action);

            // Processar de forma assíncrona (recomendado)
            ProcessMercadoPagoWebhook::dispatch($webhook);

            // Retornar 200 OK imediatamente (MP exige)
            return response()->json([
                'success' => true,
                'message' => 'Webhook received',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Webhook MP - Erro ao processar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mesmo com erro, retornar 200 para evitar retry excessivo do MP
            return response()->json([
                'success' => true,
                'message' => 'Webhook received',
            ], 200);
        }
    }

    /**
     * Processar webhook (chamado pelo Job)
     */
    public function process(PaymentWebhook $webhook): void
    {
        try {
            $webhook->update(['status' => 'processing']);

            // Processar baseado no tipo
            match($webhook->event_type) {
                'payment', 'payment.created', 'payment.updated' => $this->processPaymentWebhook($webhook),
                'merchant_order' => $this->processMerchantOrderWebhook($webhook),
                default => $this->ignoreWebhook($webhook, 'Tipo de evento não suportado'),
            };

            $webhook->markAsProcessed();

        } catch (\Exception $e) {
            Log::error('Webhook MP - Erro ao processar', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            $webhook->markAsFailed($e->getMessage());
            $webhook->scheduleRetry();
        }
    }

    /**
     * Processar webhook de payment
     */
    protected function processPaymentWebhook(PaymentWebhook $webhook): void
    {
        $paymentId = $webhook->payload['data']['id'] ?? null;

        if (!$paymentId) {
            $this->ignoreWebhook($webhook, 'Payment ID não encontrado');
            return;
        }

        // Buscar payment intent pelo mp_payment_id
        $intent = PaymentIntent::where('mp_payment_id', $paymentId)->first();

        if (!$intent) {
            Log::warning('Webhook MP - Intent não encontrado', [
                'mp_payment_id' => $paymentId,
            ]);
            $this->ignoreWebhook($webhook, 'Payment Intent não encontrado');
            return;
        }

        // Vincular webhook ao intent
        $webhook->update([
            'payment_intent_id' => $intent->id,
            'order_id' => $intent->order_id,
            'payment_id' => $intent->payment_id,
        ]);

        // Buscar dados atualizados do payment no MP
        $mpPayment = $this->mercadoPago->getPayment($paymentId);

        // Processar baseado no status
        if ($mpPayment['status'] === 'approved' && !$intent->is_approved) {
            // Pagamento aprovado
            $this->checkout->processApprovedPayment($intent, $mpPayment);
            
            Log::info('Webhook MP - Pagamento aprovado processado', [
                'intent_id' => $intent->id,
                'order_id' => $intent->order_id,
            ]);

        } elseif (in_array($mpPayment['status'], ['rejected', 'cancelled'])) {
            // Pagamento rejeitado/cancelado
            $intent->markAsRejected($mpPayment['status_detail'] ?? 'Transação não aprovada');
            
            Log::info('Webhook MP - Pagamento rejeitado', [
                'intent_id' => $intent->id,
                'status' => $mpPayment['status'],
                'detail' => $mpPayment['status_detail'] ?? null,
            ]);
        }
    }

    /**
     * Processar webhook de merchant_order
     */
    protected function processMerchantOrderWebhook(PaymentWebhook $webhook): void
    {
        // Merchant order é usado principalmente para Point
        // Por ora, apenas logar
        Log::info('Webhook MP - Merchant Order recebida', [
            'webhook_id' => $webhook->id,
            'payload' => $webhook->payload,
        ]);

        // TODO: Implementar lógica específica se necessário
    }

    /**
     * Ignorar webhook (não é relevante)
     */
    protected function ignoreWebhook(PaymentWebhook $webhook, string $reason): void
    {
        $webhook->markAsIgnored($reason);
        
        Log::info('Webhook MP - Ignorado', [
            'webhook_id' => $webhook->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Validar signature do webhook
     */
    protected function validateSignature(Request $request): bool
    {
        try {
            $headers = $request->headers->all();
            $payload = $request->getContent();

            return $this->mercadoPago->validateWebhookSignature($headers, $payload);

        } catch (\Exception $e) {
            Log::error('Webhook MP - Erro ao validar signature', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Criar registro do webhook
     */
    protected function createWebhookRecord(
        Request $request,
        ?string $eventType,
        ?string $action
    ): PaymentWebhook {
        return PaymentWebhook::create([
            'gateway' => 'mercadopago',
            'event_type' => $eventType ?? 'unknown',
            'action' => $action,
            'mp_payment_id' => $request->input('data.id'),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'signature' => $request->header('x-signature'),
            'signature_valid' => $this->validateSignature($request),
            'ip_address' => $request->ip(),
            'status' => 'received',
        ]);
    }
}