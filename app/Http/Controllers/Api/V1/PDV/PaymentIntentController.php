<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreatePaymentIntentRequest;
use App\Http\Resources\PaymentIntentResource;
use App\Models\Order;
use App\Models\Device;
use App\Models\PaymentIntent;
use App\Services\Payment\PaymentIntentService;
use App\Services\MercadoPagoTefService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar Payment Intents no PDV e Totem Kiosk
 * Endpoints: Criar PIX, Acionar TEF, Consultar Status, Cancelar
 */
class PaymentIntentController extends Controller
{
    public function __construct(
        private readonly PaymentIntentService $paymentIntentService,
        private readonly MercadoPagoTefService $mercadoPagoTefService
    ) {}

    public function create(CreatePaymentIntentRequest $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($request->input('order_id'));
            $paymentMethod = $request->input('payment_method');
            $deviceId = $request->input('device_id');

            // 1. Criar payment intent no banco via service existente (Segurança Mágna mantida)
            $intent = $this->paymentIntentService->createIntent($order, $request->validated());

            // 2. BUSCA DO DISPOSITIVO (Unificado)
            $device = Device::where('internal_id', $deviceId)->first();

            // 3. FLUXO KIOSK TEF: Se for Cartão e tiver Maquininha MP vinculada
            if (in_array($paymentMethod, ['credit_card', 'debit_card']) && $device && $device->tef_provider === 'mercadopago' && $device->tef_device_id) {
                try {
                    // Aciona a maquininha física
                    $mpIntentId = $this->mercadoPagoTefService->createIntent(
                        $device->tef_device_id, 
                        $request->input('amount'), 
                        $paymentMethod
                    );

                    // Atualiza o Intent no banco amarrando ao TEF
                    $intent->update([
                        'mp_payment_intent_id' => $mpIntentId,
                        'integration_type' => 'point_tef',
                        'tef_device_id' => $device->tef_device_id
                    ]);

                } catch (\Exception $tefException) {
                    // Trata falha crítica (maquininha desligada/offline) na hora
                    $intent->markAsError($tefException->getMessage());
                    throw new PaymentException($tefException->getMessage(), 400);
                }
            }

            Log::info('PDV - Payment Intent criado', [
                'intent_id' => $intent->id,
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'device_id' => $deviceId,
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getSuccessMessage($intent),
                'data' => new PaymentIntentResource($intent),
            ], 201);

        } catch (PaymentException $e) {
            Log::error('PDV - Erro ao criar Payment Intent', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'PAYMENT_INTENT_ERROR',
            ], $e->getCode() ?: 400);

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

    public function show(PaymentIntent $intent): JsonResponse
    {
        try {
            if ($intent->is_pix && $intent->is_pending) {
                $intent = $this->paymentIntentService->checkIntentStatus($intent);
            } elseif ($intent->is_card && $intent->is_pending && $intent->integration_type === 'point_tef') {
                // Polling nativo para a Maquininha TEF
                $intent = $this->syncTefStatus($intent);
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
            Log::error('PDV - Erro ao consultar status', ['intent_id' => $intent->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status do pagamento.',
                'error_code' => 'UNEXPECTED_ERROR',
            ], 500);
        }
    }

    public function checkStatus(PaymentIntent $intent): JsonResponse
    {
        try {
            if ($intent->integration_type === 'point_tef') {
                $intent = $this->syncTefStatus($intent);
            } else {
                $intent = $this->paymentIntentService->checkIntentStatus($intent);
            }

            return response()->json([
                'success' => true,
                'data' => new PaymentIntentResource($intent),
                'message' => $intent->getStatusMessage(),
            ]);

        } catch (\Exception $e) {
            Log::error('PDV - Erro ao forçar verificação de status', ['intent_id' => $intent->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status.',
            ], 500);
        }
    }

    public function cancel(PaymentIntent $intent, Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason', 'Cancelado pelo operador/cliente');

            // 1. Se for TEF do MP, aborta na máquina física primeiro para liberar a tela
            if ($intent->is_pending && $intent->integration_type === 'point_tef' && $intent->mp_payment_intent_id && $intent->tef_device_id) {
                try {
                    $this->mercadoPagoTefService->cancelIntent($intent->tef_device_id, $intent->mp_payment_intent_id);
                } catch (\Exception $e) {
                    Log::warning("Não foi possível cancelar o intent {$intent->mp_payment_intent_id} fisicamente na maquininha.", ['error' => $e->getMessage()]);
                }
            }

            // 2. Continua com o fluxo padrão de cancelamento local
            $intent = $this->paymentIntentService->cancelIntent($intent, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento cancelado com sucesso.',
                'data' => new PaymentIntentResource($intent),
            ]);

        } catch (\Exception $e) {
            Log::error('PDV - Erro ao cancelar Payment Intent', ['intent_id' => $intent->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pagamento.',
                'error_code' => 'UNEXPECTED_ERROR',
            ], 500);
        }
    }

    /**
     * Sincroniza o status físico da maquininha com o banco de dados e notifica via WebSocket.
     */
    private function syncTefStatus(PaymentIntent $intent): PaymentIntent
    {
        if (!$intent->is_pending || !$intent->mp_payment_intent_id) {
            return $intent;
        }

        try {
            $mpStatus = $this->mercadoPagoTefService->getPaymentIntentStatus($intent->mp_payment_intent_id);

            if ($mpStatus === 'FINISHED') {
                $intent->markAsApproved();
                if ($intent->order) {
                    $intent->order->update(['status' => 'paid']);
                }
                $this->broadcastUpdate($intent);

            } elseif (in_array($mpStatus, ['CANCELED', 'ERROR', 'REJECTED'])) {
                $intent->markAsRejected('Cancelado ou recusado no terminal físico.');
                $this->broadcastUpdate($intent);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar status TEF MP', ['intent' => $intent->id, 'error' => $e->getMessage()]);
        }

        return $intent->fresh();
    }

    /**
     * Dispara notificação WebSocket (Reverb) para a tela do Totem atualizar instantaneamente.
     */
    private function broadcastUpdate(PaymentIntent $intent): void
    {
        // Usa a classe genérica de Broadcast de Pagamento se existir no seu ecossistema, 
        // ou adapte para a classe exata que o seu Reverb escuta.
        if (class_exists(\App\Events\PaymentStatusUpdated::class)) {
            broadcast(new \App\Events\PaymentStatusUpdated($intent));
        }
    }

    protected function getSuccessMessage(PaymentIntent $intent): string
    {
        if ($intent->is_pix) {
            return 'QR Code PIX gerado com sucesso. Aguardando pagamento...';
        }

        if ($intent->is_card && $intent->integration_type === 'point_tef') {
            return 'Instruções enviadas para a maquininha. Aguardando cliente...';
        }

        return 'Payment Intent criado com sucesso.';
    }
}