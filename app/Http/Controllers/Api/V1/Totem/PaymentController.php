<?php

namespace App\Http\Controllers\Api\V1\Totem;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PixService $pixService
    ) {}

    /**
     * Gerar QR Code PIX para pagamento
     * POST /api/v1/totem/payments/pix
     */
    public function generatePix(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Verificar se pedido é de visitante (filho usa crédito)
        if ($order->filho_id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedidos de filhos são pagos via crédito',
            ], 422);
        }

        // Verificar se já existe pagamento pendente
        $existingPayment = $order->payments()
            ->where('method', 'pix')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $existingPayment->id,
                    'qr_code' => $existingPayment->pix_qr_code,
                    'qr_code_base64' => $existingPayment->pix_qr_code_base64,
                    'copy_paste' => $existingPayment->pix_copy_paste,
                    'amount' => $existingPayment->amount,
                    'expires_at' => $existingPayment->expires_at->toIso8601String(),
                    'expires_in_seconds' => now()->diffInSeconds($existingPayment->expires_at),
                ],
            ]);
        }

        try {
            // Gerar novo PIX
            $pixData = $this->pixService->generate([
                'amount' => $order->total,
                'description' => "Pedido #{$order->order_number}",
                'external_id' => $order->id,
            ]);

            // Criar registro de pagamento
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => 'pix',
                'amount' => $order->total,
                'status' => 'pending',
                'pix_qr_code' => $pixData['qr_code'],
                'pix_qr_code_base64' => $pixData['qr_code_base64'],
                'pix_copy_paste' => $pixData['copy_paste'],
                'pix_transaction_id' => $pixData['transaction_id'],
                'expires_at' => now()->addMinutes(15),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'qr_code' => $payment->pix_qr_code,
                    'qr_code_base64' => $payment->pix_qr_code_base64,
                    'copy_paste' => $payment->pix_copy_paste,
                    'amount' => $payment->amount,
                    'expires_at' => $payment->expires_at->toIso8601String(),
                    'expires_in_seconds' => 900, // 15 minutos
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PIX. Tente novamente.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verificar status do pagamento PIX
     * GET /api/v1/totem/payments/{payment}/status
     */
    public function checkStatus(Payment $payment): JsonResponse
    {
        // Verificar se expirou
        if ($payment->status === 'pending' && $payment->expires_at < now()) {
            $payment->update(['status' => 'expired']);
        }

        // Se ainda pendente, verificar no gateway
        if ($payment->status === 'pending' && $payment->pix_transaction_id) {
            try {
                $gatewayStatus = $this->pixService->checkStatus($payment->pix_transaction_id);

                if ($gatewayStatus['paid']) {
                    $this->paymentService->confirmPayment($payment, [
                        'gateway_id' => $gatewayStatus['gateway_id'],
                        'paid_at' => $gatewayStatus['paid_at'],
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::error('PIX status check failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $payment->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'is_paid' => $payment->status === 'paid',
                'is_expired' => $payment->status === 'expired',
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'order_status' => $payment->order->status,
            ],
        ]);
    }

    /**
     * Processar pagamento em cartão (integração com terminal)
     * POST /api/v1/totem/payments/card
     */
    public function processCard(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|uuid|exists:orders,id',
            'card_type' => 'required|in:credito,debito',
            'terminal_id' => 'required|string',
            'installments' => 'nullable|integer|min:1|max:12',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->filho_id) {
            return response()->json([
                'success' => false,
                'message' => 'Pedidos de filhos são pagos via crédito',
            ], 422);
        }

        try {
            // Criar pagamento pendente
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $request->card_type,
                'amount' => $order->total,
                'status' => 'processing',
                'installments' => $request->card_type === 'credito' ? ($request->installments ?? 1) : 1,
                'terminal_id' => $request->terminal_id,
            ]);

            // Aqui seria a integração com o terminal de pagamento
            // Por enquanto, retornamos instrução para o terminal
            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => 'awaiting_terminal',
                    'message' => 'Aguardando pagamento no terminal',
                    'terminal_instruction' => [
                        'amount' => $order->total,
                        'type' => $request->card_type,
                        'installments' => $payment->installments,
                        'reference' => $payment->id,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Confirmar pagamento do terminal (callback do terminal)
     * POST /api/v1/totem/payments/{payment}/confirm-card
     */
    public function confirmCard(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'authorization_code' => 'required|string',
            'card_last_digits' => 'required|string|size:4',
            'card_brand' => 'required|string',
            'nsu' => 'required|string',
        ]);

        if ($payment->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não está aguardando confirmação',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'authorization_code' => $request->authorization_code,
                'card_last_digits' => $request->card_last_digits,
                'card_brand' => $request->card_brand,
                'nsu' => $request->nsu,
            ]);

            // Confirmar pedido
            $payment->order->update([
                'status' => 'confirmed',
                'payment_method' => $payment->method,
                'payment_confirmed_at' => now(),
            ]);

            DB::commit();

            // Emitir evento para KDS
            event(new \App\Events\OrderPaid($payment->order));

            return response()->json([
                'success' => true,
                'message' => 'Pagamento confirmado!',
                'data' => [
                    'payment_id' => $payment->id,
                    'order_number' => $payment->order->order_number,
                    'order_status' => 'confirmed',
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao confirmar pagamento',
            ], 500);
        }
    }

    /**
     * Cancelar pagamento pendente
     * POST /api/v1/totem/payments/{payment}/cancel
     */
    public function cancel(Payment $payment): JsonResponse
    {
        if (!in_array($payment->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não pode ser cancelado',
            ], 422);
        }

        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pagamento cancelado',
        ]);
    }

    /**
     * Métodos de pagamento disponíveis no totem
     * GET /api/v1/totem/payments/methods
     */
    public function availableMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'id' => 'pix',
                    'name' => 'PIX',
                    'icon' => 'qr-code',
                    'description' => 'Pagamento instantâneo',
                    'enabled' => config('casalar.payments.pix_enabled', true),
                ],
                [
                    'id' => 'credito',
                    'name' => 'Cartão de Crédito',
                    'icon' => 'credit-card',
                    'description' => 'Até 3x sem juros',
                    'enabled' => config('casalar.payments.card_enabled', true),
                    'max_installments' => config('casalar.payments.max_installments', 3),
                ],
                [
                    'id' => 'debito',
                    'name' => 'Cartão de Débito',
                    'icon' => 'credit-card',
                    'description' => 'Débito à vista',
                    'enabled' => config('casalar.payments.card_enabled', true),
                ],
            ],
        ]);
    }
}