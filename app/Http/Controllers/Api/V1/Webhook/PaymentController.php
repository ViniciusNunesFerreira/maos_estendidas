<?php
// app/Http/Controllers/Api/V1/Webhook/PaymentController.php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Receber notificações de pagamento do gateway
     * 
     * POST /api/v1/webhooks/payment/notification
     */
    public function notification(Request $request): JsonResponse
    {
        try {
            Log::info('Payment webhook received', ['payload' => $request->all()]);

            // Verificar assinatura do webhook
            if (!$this->verifySignature($request)) {
                Log::warning('Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Processar notificação
            $result = $this->paymentService->processWebhookNotification($request->all());

            return response()->json([
                'success' => true,
                'processed' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing payment webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal error',
            ], 500);
        }
    }

    /**
     * Verificar assinatura do webhook
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Payment-Signature');
        $payload = $request->getContent();
        $secret = config('services.payment_gateway.webhook_secret');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}