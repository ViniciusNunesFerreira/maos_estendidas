<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\CheckoutTransparenteService;
use App\DTOs\ProcessCheckoutDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CheckoutTransparenteService $checkoutService
    ) {}

    /**
     * Criar pagamento PIX
     * POST /api/v1/app/payments/create-pix
     */
    public function createPixPayment(Request $request): JsonResponse
    {
        
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
        ]);


        try {
            $order = Order::with('items.product')->findOrFail($validated['order_id']);

            // Verificar se pedido pertence ao filho autenticado
            $filho = $request->user()->filho;
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            // Verificar se pedido já foi pago
            if ($order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já foi pago',
                ], 422);
            }

            // Criar payment intent PIX
            $result = $this->checkoutService->createPixPayment($order);

            if (is_object($result)) {
                $result = (array) $result;
            }

            
            if (!isset($result['success']) || $result['success'] !== true) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao criar pagamento PIX',
                ], 422);
            }

           
            $expiration_date = $result['pix']['expiration_date'] ?? null;

            return response()->json([
                'success' => true,
                'message' => 'QR Code PIX gerado com sucesso',
                'data' => [
                    'payment_intent_id' => $result['payment_intent_id'],
                    'mp_payment_id' => $result['mp_payment_id'],
                    'status' => $result['status'],
                    'pix' => [
                        'qr_code' => $result['pix']['qr_code'],
                        'qr_code_base64' => $result['pix']['qr_code_base64'],
                        'ticket_url' => $result['pix']['ticket_url'],
                        'expiration_date' =>$expiration_date instanceof \Carbon\Carbon 
                                ? $expiration_date->toIso8601String() 
                                : $expiration_date,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento PIX', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pagamento PIX',
            ], 500);
        }
    }

    /**
     * Criar pagamento com Cartão
     * POST /api/v1/app/payments/create-card
     */
    public function createCardPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
            'card_token' => 'required|string',
            'installments' => 'required|integer|min:1|max:12',
            'payer' => 'sometimes|array',
            'payer.email' => 'required_with:payer|email',
            'payer.identification' => 'required_with:payer|array',
            'payer.identification.type' => 'required_with:payer.identification|string',
            'payer.identification.number' => 'required_with:payer.identification|string',
        ]);

        try {
            $order = Order::with('items.product')->findOrFail($validated['order_id']);

            // Verificar se pedido pertence ao filho autenticado
            $filho = $request->user()->filho;
            if ($order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            // Verificar se pedido já foi pago
            if ($order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido já foi pago',
                ], 422);
            }

            // Criar DTO
            $dto = ProcessCheckoutDTO::fromRequest([
                'order_id' => $order->id,
                'amount' => $order->total,
                'payment_method' => 'credit_card',
                'card_token' => $validated['card_token'],
                'installments' => $validated['installments'],
                'payer' => $validated['payer'] ?? [
                    'email' => $filho->user->email,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $filho->cpf,
                    ],
                ],
            ]);

            // Criar pagamento
            $result = $this->checkoutService->createCardPayment(
                $order,
                $dto->cardToken,
                $dto->installments,
                $dto->payer
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao processar pagamento',
                ], 422);
            }

            $paymentIntent = $result['payment_intent'];

            // Se foi aprovado imediatamente
            if ($paymentIntent->status === 'approved') {
                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento aprovado!',
                    'data' => [
                        'payment_intent_id' => $paymentIntent->id,
                        'status' => 'approved',
                        'approved' => true,
                        'order_id' => $order->id,
                    ],
                ]);
            }

            // Se está pendente
            return response()->json([
                'success' => true,
                'message' => 'Pagamento em processamento',
                'data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'mp_payment_id' => $paymentIntent->mp_payment_id,
                    'status' => $paymentIntent->status,
                    'approved' => false,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao criar pagamento com cartão', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
            ], 500);
        }
    }

    /**
     * Verificar status do pagamento
     * GET /api/v1/app/payments/{paymentIntent}/status
     */
    public function checkStatus(string $paymentIntentId): JsonResponse
    {
        try {
            $paymentIntent = PaymentIntent::findOrFail($paymentIntentId);

            // Verificar se pertence ao filho autenticado
            $filho = auth()->user()->filho;
            if ($paymentIntent->order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não encontrado',
                ], 404);
            }

            // Verificar status atualizado
            if (in_array($paymentIntent->status, ['pending', 'processing'])) {
                $this->checkoutService->checkPaymentStatus($paymentIntent);
                $paymentIntent->refresh();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $paymentIntent->status,
                    'approved' => $paymentIntent->status === 'approved',
                    'order_id' => $paymentIntent->order_id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar status do pagamento', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status',
            ], 500);
        }
    }

    /**
     * Cancelar pagamento
     * POST /api/v1/app/payments/{paymentIntent}/cancel
     */
    public function cancelPayment(string $paymentIntentId): JsonResponse
    {
        try {
            $paymentIntent = PaymentIntent::findOrFail($paymentIntentId);

            // Verificar se pertence ao filho autenticado
            $filho = auth()->user()->filho;
            if ($paymentIntent->order->filho_id !== $filho->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não encontrado',
                ], 404);
            }

            // Verificar se pode cancelar
            if (!in_array($paymentIntent->status, ['created', 'pending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não pode ser cancelado',
                ], 422);
            }

            // Cancelar
            $result = $this->checkoutService->cancelPayment($paymentIntent);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Pagamento cancelado',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao cancelar pagamento', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pagamento',
            ], 500);
        }
    }
}