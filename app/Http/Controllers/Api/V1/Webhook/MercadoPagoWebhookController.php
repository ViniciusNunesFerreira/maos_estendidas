<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntent;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use App\Events\PaymentApproved;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mercadoPago
    ) {}

    /**
     * Receber notificação
     * Middleware: 'verify.mercadopago.signature' (Configurado no Kernel/Route)
     */
    public function handle(Request $request): JsonResponse
    {
        $topic = $request->input('type') ?? $request->input('topic');
        $id = $request->input('data.id') ?? $request->input('id');

        Log::info("Webhook MP Recebido: [$topic] ID: $id");

        if (!$id) {
            return response()->json(['status' => 'ignored', 'reason' => 'no_id'], 200);
        }

        try {
            // Processamos Payment (Online/Pix) e Merchant Order (Point/TEF)
            if ($topic === 'payment') {
                $this->handlePayment($id);
            } elseif ($topic === 'merchant_order') {
                $this->handleMerchantOrder($id);
            }
        } catch (\Exception $e) {
            Log::error("Erro Webhook MP: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Retorna 200 para o MP parar de tentar, mas logamos o erro
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Processar Atualização de Pagamento (PIX, Cartão Online)
     */
    private function handlePayment(string $mpPaymentId): void
    {
        // 1. Buscar status atual no Mercado Pago (Single Source of Truth)
        $mpPayment = $this->mercadoPago->getPayment($mpPaymentId);
        
        if (!$mpPayment) return;

        $externalReference = $mpPayment['external_reference'] ?? null;
        
        $typeWhere = 'order_id';
        
        if ($externalReference !== null && str_starts_with($externalReference, "invoice")) {
            //verificar se a referencia externa é de uma ordem ou uma fatura;
            $typeWhere = 'invoice_id';
            $externalReference = trim(str_replace('invoice_', '', $externalReference));
        }else{
            $externalReference = trim(str_replace('order_', '', $externalReference));
        }
        
        
        $paymentIntent = PaymentIntent::where('mp_payment_id', $mpPaymentId)->first();

        if (!$paymentIntent) {
            $paymentIntent = PaymentIntent::where($typeWhere, $externalReference)->latest() ->first();
        }

        if (!$paymentIntent) {
            Log::warning("PaymentIntent não encontrado para MP ID: $mpPaymentId");
            return;
        }

        // 2. Atualizar PaymentIntent
        $status = $mpPayment['status']; // approved, pending, rejected, cancelled
        $statusDetail = $mpPayment['status_detail'] ?? null;
        
        $paymentIntent->update([
            'status' => $status,
            'status_detail' => $statusDetail,
            'mp_payment_id' => $mpPaymentId, // Garante vínculo se achou por external_reference
            'mp_response' => $mpPayment, // Salva payload completo para auditoria
        ]);

        if ($status === 'approved') {
            $paymentIntent->markAsApproved($mpPayment['transaction_amount']);
            $this->finalizeSystemPayment($paymentIntent, $mpPayment);
        } elseif (in_array($status, ['rejected', 'cancelled'])) {
            $paymentIntent->markAsRejected($statusDetail);
            // Se necessário, cancelar a Order/Invoice ou apenas liberar para tentar outro meio
        }

        // 3. DISPARAR EVENTO WEBSOCKET (Real-time)
        PaymentApproved::dispatch($paymentIntent);
    }

    /**
     * Processar Merchant Order (TEF / Point)
     */
    private function handleMerchantOrder(string $merchantOrderId): void
    {
        // Buscar dados da Merchant Order
        $merchantOrder = $this->mercadoPago->getMerchantOrder($merchantOrderId);
        
        // A lógica do Point é: Merchant Order contém Payments dentro dela.
        // Precisamos iterar sobre os pagamentos e ver se o total pago >= total pedido.
        
        if (empty($merchantOrder['payments'])) return;

        foreach ($merchantOrder['payments'] as $paymentData) {
            if ($paymentData['status'] === 'approved') {
                // Reutilizamos a lógica de pagamento
                $this->handlePayment($paymentData['id']);
            }
        }
    }

    /**
     * Finalizar Pagamento no Sistema (Order ou Invoice)
     */
    private function finalizeSystemPayment(PaymentIntent $intent, array $mpData): void
    {
        DB::transaction(function () use ($intent, $mpData) {
            
            // Verifica se já existe um registro financeiro (Payment) para evitar duplicidade
            $exists = Payment::where('gateway_transaction_id', $mpData['id'])->exists();
            if ($exists) return;

            // Marcar intent como aprovado
            $intent->markAsApproved($mpData['transaction_amount'] ?? $intent->amount);

            // Criar registro financeiro oficial (Payment)
            $payment = Payment::create([
                'order_id' => $intent?->order_id,
                'invoice_id' => $intent?->invoice_id,
                'method' => $intent->payment_method, // pix, credit_card
                'amount' => $mpData['transaction_amount'],
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'installments' => $intent->installments ?? 1,

                'gateway_name' => 'mercadopago',
                'gateway_transaction_id' => $mpData['id'],
                'authorized_at' => now(),
                
                'mp_payment_id' => $intent->mp_payment_id,
                'mp_payment_intent_id' => $intent->id,
                'mp_status' => $mpData['status'],
                'mp_status_detail' => $mpData['status_detail'] ?? null,
                'mp_payment_type_id' => $mpData['payment_type_id'] ?? null,
                'mp_payment_method_id' => $mpData['payment_method_id'] ?? null,
                'mp_transaction_amount' => $mpData['transaction_amount'] ?? null,
                'mp_response' => $mpData,
                'mp_webhook_received_at' => now(),

                
            ]);


            $intent->payment()->associate($payment);
            $intent->save();

            // Baixar Order
            if ($intent->order_id) {
                $order = Order::find($intent->order_id);
                if ($order && !$order->isPaid()) {
                    $order->markAsPaid(); 
                    // Se for Totem/PDV, talvez marcar como 'preparing' ou 'ready' dependendo da regra
                }
            }
            
            // Baixar Invoice
            if ($intent->invoice_id) {
                $invoice = Invoice::find($intent->invoice_id);
                if ($invoice) {
                    $invoice->markAsPaid($payment->amount);
                    $invoice->recalculateTotals(); // Importante para atualizar status partial/paid
                }
            }

            Log::info('Pagamento aprovado processado', [
                'order_id' => $intent->order_id,
                'invoice_id' => $intent->invoice_id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
            ]);

        });
    }
}