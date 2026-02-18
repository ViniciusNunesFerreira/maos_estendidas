<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Payment\Getnet\GetnetCloudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para receber e processar webhooks da Getnet
 * 
 * Endpoint público com validação de assinatura para segurança
 */
class GetnetWebhookController extends Controller
{
    public function __construct(
        private readonly GetnetCloudService $getnetService
    ) {}

    /**
     * Recebe webhook da Getnet sobre status de pagamento
     * POST /api/v1/webhooks/getnet/payment-status
     */
    public function handlePaymentStatus(Request $request): JsonResponse
    {
        // Log de recebimento para debug
        Log::info('Getnet Webhook: Recebido', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        try {
            // Validação de assinatura para segurança
            $signature = $request->header('X-Getnet-Signature') ?? 
                        $request->header('Getnet-Signature');
            
            if ($signature) {
                $payload = $request->getContent();
                $isValid = $this->getnetService->validateWebhookSignature($signature, $payload);
                
                if (!$isValid) {
                    Log::warning('Getnet Webhook: Assinatura inválida', [
                        'signature' => $signature,
                        'ip' => $request->ip(),
                    ]);
                    
                    return response()->json([
                        'error' => 'Invalid signature',
                    ], 401);
                }
            }

            // Processa o webhook
            $transaction = $this->getnetService->processWebhook($request->all());

            Log::info('Getnet Webhook: Processado com sucesso', [
                'transaction_id' => $transaction->id,
                'payment_id' => $transaction->payment_id,
                'status' => $transaction->status,
            ]);

            // Retorna sucesso para Getnet
            return response()->json([
                'success' => true,
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Getnet Webhook: Erro ao processar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            // Mesmo com erro, retorna 200 para evitar retry infinito da Getnet
            // O erro já foi logado para análise posterior
            return response()->json([
                'success' => false,
                'error' => 'Internal error',
                'message' => config('app.debug') ? $e->getMessage() : 'Error processing webhook',
            ], 200);
        }
    }

    /**
     * Endpoint de verificação de saúde do webhook
     * GET /api/v1/webhooks/getnet/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'getnet-webhook',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}