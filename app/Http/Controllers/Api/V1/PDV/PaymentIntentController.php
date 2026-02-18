<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentIntentRequest;
use App\Http\Resources\PaymentIntentResource;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\Payment\PaymentIntentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar Payment Intents no PDV
 * Endpoints: Criar PIX, Processar Cartão Manual, Consultar Status, Cancelar
 */
class PaymentIntentController extends Controller
{
    public function __construct(
        private readonly PaymentIntentService $paymentIntentService
    ) {}

    /**
     * Criar Payment Intent
     * POST /api/v1/pdv/payment-intents
     * 
     * Body:
     * {
     *   "order_id": "uuid",
     *   "payment_method": "pix|credit_card|debit_card",
     *   "amount": 50.00,
     *   "device_id": "pdv-123",
     *   "card_last_digits": "1234", // opcional, para cartão
     *   "card_brand": "visa", // opcional
     *   "installments": 1, // opcional
     *   "pos_transaction_id": "abc123" // opcional
     * }
     */
    public function create(CreatePaymentIntentRequest $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($request->input('order_id'));

            // Criar payment intent
            $intent = $this->paymentIntentService->createIntent($order, $request->validated());

            Log::info('PDV - Payment Intent criado', [
                'intent_id' => $intent->id,
                'order_id' => $order->id,
                'payment_method' => $request->input('payment_method'),
                'device_id' => $request->input('device_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getSuccessMessage($intent),
                'data' => new PaymentIntentResource($intent),
            ], 201);

        } catch (PaymentException $e) {
            Log::error('PDV - Erro ao criar Payment Intent', [
                'error' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PAYMENT_INTENT_ERROR',
            ], $e->getCode());

        } catch (\Exception $e) {
            Log::error('PDV - Erro inesperado ao criar Payment Intent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento. Tente novamente.',
                'error_code' => 'UNEXPECTED_ERROR',
            ], 500);
        }
    }

    /**
     * Consultar status de um Payment Intent (polling para PIX)
     * GET /api/v1/pdv/payment-intents/{intent}
     */
    public function show(PaymentIntent $intent): JsonResponse
    {
        try {
            // Se for PIX e ainda está pendente, consultar status no MP
            if ($intent->is_pix && $intent->is_pending) {
                $intent = $this->paymentIntentService->checkIntentStatus($intent);
            }

            return response()->json([
                'success' => true,
                'data' => new PaymentIntentResource($intent),
            ]);

        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'CHECK_STATUS_ERROR',
            ], $e->getCode());

        } catch (\Exception $e) {
            Log::error('PDV - Erro ao consultar status', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status do pagamento.',
                'error_code' => 'UNEXPECTED_ERROR',
            ], 500);
        }
    }

    /**
     * Cancelar Payment Intent
     * DELETE /api/v1/pdv/payment-intents/{intent}
     */
    public function cancel(PaymentIntent $intent, Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason', 'Cancelado pelo operador');

            $intent = $this->paymentIntentService->cancelIntent($intent, $reason);

            Log::info('PDV - Payment Intent cancelado', [
                'intent_id' => $intent->id,
                'reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento cancelado com sucesso.',
                'data' => new PaymentIntentResource($intent),
            ]);

        } catch (PaymentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'CANCEL_ERROR',
            ], $e->getCode());

        } catch (\Exception $e) {
            Log::error('PDV - Erro ao cancelar Payment Intent', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pagamento.',
                'error_code' => 'UNEXPECTED_ERROR',
            ], 500);
        }
    }

    /**
     * Webhook callback (verificação de status via polling manual)
     * POST /api/v1/pdv/payment-intents/{intent}/check
     */
    public function checkStatus(PaymentIntent $intent): JsonResponse
    {
        try {
            // Forçar verificação de status
            $intent = $this->paymentIntentService->checkIntentStatus($intent);

            return response()->json([
                'success' => true,
                'data' => new PaymentIntentResource($intent),
                'message' => $intent->getStatusMessage(),
            ]);

        } catch (\Exception $e) {
            Log::error('PDV - Erro ao forçar verificação de status', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status.',
            ], 500);
        }
    }

    /**
     * Gerar mensagem de sucesso baseada no tipo de pagamento
     */
    protected function getSuccessMessage(PaymentIntent $intent): string
    {
        if ($intent->is_pix) {
            return 'QR Code PIX gerado com sucesso. Aguardando pagamento...';
        }

        if ($intent->is_card) {
            return 'Pagamento registrado com sucesso!';
        }

        return 'Payment Intent criado com sucesso.';
    }
}