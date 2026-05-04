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

            if ($order->status === 'paid') {
                throw new PaymentException('Este pedido já foi pago.', 400);
            }

            $device = Device::where('internal_id', $deviceId)->first();
            $isKiosk = $device && $device->type === 'kiosk';
            
            $isTefIntegration = in_array($paymentMethod, ['credit_card', 'debit_card']) 
                                && $device 
                                && $device->tef_provider === 'mercadopago' 
                                && !empty($device->tef_device_id);

            if ($isKiosk && in_array($paymentMethod, ['credit_card', 'debit_card']) && !$isTefIntegration) {
                throw new PaymentException('O terminal de cartão não está configurado neste totem.', 400);
            }

            if ($isTefIntegration) {
                // 1. GARBAGE COLLECTION FÍSICO COM TRAVA DE SEGURANÇA
                $ghostIntents = PaymentIntent::where('tef_device_id', $device->tef_device_id)
                    ->where('integration_type', 'point_tef')
                    ->whereIn('status', ['created', 'pending', 'processing'])
                    ->get();

                foreach ($ghostIntents as $ghost) {
                    if ($ghost->mp_payment_intent_id) {
                        try {
                            $this->mercadoPagoTefService->cancelIntent($device->tef_device_id, $ghost->mp_payment_intent_id);
                        } catch (\Exception $e) {
                            if ($e->getCode() === 409) {
                                throw new PaymentException("A maquininha está ocupada com uma transação anterior. Pressione a tecla vermelha (X) nela antes de iniciar uma nova cobrança.", 400);
                            }
                            Log::warning("Falha ao abortar ghost intent {$ghost->mp_payment_intent_id}");
                        }
                    }
                    $ghost->markAsCancelled('Cancelado automaticamente (Sobreposição).');
                }

                // 2. CRIAR A NOVA TRANSAÇÃO
                $intent = PaymentIntent::create([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $request->input('amount'),
                    'status' => 'pending',
                    'integration_type' => 'point_tef',
                    'tef_device_id' => $device->tef_device_id
                ]);

                try {
                    $mpIntentId = $this->mercadoPagoTefService->createIntent(
                        $device->tef_device_id, 
                        $intent->amount, 
                        $paymentMethod
                    );
                    $intent->update(['mp_payment_intent_id' => $mpIntentId]);
                } catch (\Exception $tefException) {
                    $intent->markAsError($tefException->getMessage());
                    throw new PaymentException($tefException->getMessage(), 400);
                }

                Log::info('Kiosk - Payment Intent TEF criado', ['intent_id' => $intent->id]);

                return response()->json([
                    'success' => true,
                    'message' => 'Instruções enviadas para a maquininha.',
                    'data' => new PaymentIntentResource($intent),
                ], 201);
            }

            // Fluxo Original PDV Manual
            $intent = $this->paymentIntentService->createIntent($order, $request->validated());
            return response()->json(['success' => true, 'message' => $this->getSuccessMessage($intent), 'data' => new PaymentIntentResource($intent)], 201);

        } catch (PaymentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'error_code' => 'PAYMENT_INTENT_ERROR'], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar.', 'error_code' => 'UNEXPECTED_ERROR'], 500);
        }
    }

    public function show(PaymentIntent $intent): JsonResponse
    {
        try {
            if ($intent->is_pix && $intent->is_pending) {
                $intent = $this->paymentIntentService->checkIntentStatus($intent);
            } elseif ($intent->is_card && $intent->is_pending && $intent->integration_type === 'point_tef') {
                $intent = $this->syncTefStatus($intent);
            }
            return response()->json(['success' => true, 'data' => new PaymentIntentResource($intent)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao verificar status.'], 500);
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
            return response()->json(['success' => true, 'data' => new PaymentIntentResource($intent), 'message' => $intent->getStatusMessage()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao verificar status.'], 500);
        }
    }

    public function cancel(PaymentIntent $intent, Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason', 'Cancelado pelo operador/cliente');

            if ($intent->is_pending && $intent->integration_type === 'point_tef' && $intent->mp_payment_intent_id && $intent->tef_device_id) {
                try {
                    $this->mercadoPagoTefService->cancelIntent($intent->tef_device_id, $intent->mp_payment_intent_id);
                } catch (\Exception $e) {
                    if ($e->getCode() === 409) {
                        return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
                    }
                    Log::warning("TEF: Falha ao abortar terminal {$intent->tef_device_id}.", ['error' => $e->getMessage()]);
                }
            }

            if ($intent->integration_type === 'point_tef') {
                $intent->markAsCancelled($reason);
                if ($intent->order && $intent->order->status === 'pending') {
                     $intent->order->update(['status' => 'cancelled']);
                }
            } else {
                $intent = $this->paymentIntentService->cancelIntent($intent, $reason);
            }

            return response()->json(['success' => true, 'message' => 'Pagamento cancelado com sucesso.', 'data' => new PaymentIntentResource($intent)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao cancelar pagamento.'], 500);
        }
    }

    private function syncTefStatus(PaymentIntent $intent): PaymentIntent
    {
        if (!$intent->is_pending || !$intent->mp_payment_intent_id) return $intent;

        try {
            $mpStatus = $this->mercadoPagoTefService->getPaymentIntentStatus($intent->mp_payment_intent_id);
            if ($mpStatus === 'FINISHED') {
                $intent->markAsApproved();
                if ($intent->order) $intent->order->update(['status' => 'paid']);
                $this->broadcastUpdate($intent);
            } elseif (in_array($mpStatus, ['CANCELED', 'ERROR', 'REJECTED'])) {
                $intent->markAsRejected('Cancelado ou recusado na maquininha.');
                $this->broadcastUpdate($intent);
            }
        } catch (\Exception $e) {}
        return $intent->fresh();
    }

    private function broadcastUpdate(PaymentIntent $intent): void
    {
        if (class_exists(\App\Events\PaymentStatusUpdated::class)) {
            broadcast(new \App\Events\PaymentStatusUpdated($intent));
        }
    }

    protected function getSuccessMessage(PaymentIntent $intent): string
    {
        if ($intent->is_pix) return 'QR Code PIX gerado com sucesso. Aguardando pagamento...';
        if ($intent->is_card) return 'Pagamento registrado com sucesso!';
        return 'Payment Intent criado com sucesso.';
    }
}