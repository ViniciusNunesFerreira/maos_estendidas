<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentController extends Controller
{
    /**
     * Processar pagamento com saldo interno
     * POST /api/v1/app/orders/process-payment
     */
    public function processBalancePayment(Request $request): JsonResponse
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

            // Verificar saldo disponível
            if ($filho->credit_available < $order->total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente',
                    'data' => [
                        'available' => $filho->credit_available,
                        'required' => $order->total,
                        'missing' => $order->total - $filho->credit_available,
                    ],
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Debitar do crédito disponível
                $filho->credit_used += $order->total;
                $filho->credit_available -= $order->total;
                $filho->save();

                // Criar registro de pagamento
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'filho_id' => $filho->id,
                    'method' => 'balance',
                    'amount' => $order->total,
                    'status' => 'confirmed',
                    'paid_at' => now(),
                ]);

                // Atualizar status do pedido
                $order->update([
                    'status' => 'confirmed',
                    'paid_at' => now(),
                ]);

                DB::commit();

                Log::info('Pagamento com saldo processado', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'amount' => $order->total,
                    'filho_id' => $filho->id,
                    'new_balance' => $filho->credit_available,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento realizado com sucesso',
                    'data' => [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'order_status' => 'confirmed',
                        'new_balance' => $filho->credit_available,
                    ],
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Erro ao processar pagamento com saldo', [
                'order_id' => $validated['order_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento',
            ], 500);
        }
    }
}