<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntent;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use App\Services\ExternalPaymentService;
use App\Events\PaymentApproved;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mercadoPago,
        protected ExternalPaymentService $externalPaymentService
    ) {}

    /**
     * Receber notificação
     * Middleware: 'verify.mercadopago.signature' (Configurado no Kernel/Route)
     */
    public function handle(Request $request): JsonResponse
    {
        $topic = $request->input('type') ?? $request->input('topic');
        $id = $request->input('data.id') ?? $request->input('id');

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
            return response()->json(['status' => 'success'], 200);
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

        
        $paymentIntent = PaymentIntent::where('mp_payment_id', $mpPaymentId)->first();
        

        // 2. Fallback pelo External Reference
        if (!$paymentIntent && !empty($mpPayment['external_reference'])) {
            $ref = $mpPayment['external_reference'];
            
            // Remove prefixos conhecidos para pegar o ID limpo
            $id = str_replace(['order_', 'invoice_'], '', $ref);
            
            // Determina se é Order ou Invoice baseado no prefixo original ou contexto
            if (str_contains($ref, 'order_')) {
                $paymentIntent = PaymentIntent::where('order_id', $id)->latest()->first();
            } elseif (str_contains($ref, 'invoice_')) {
                $paymentIntent = PaymentIntent::where('invoice_id', $id)->latest()->first();
            }
        }

        if (!$paymentIntent) {
            Log::warning("PaymentIntent não encontrado para MP ID: $mpPaymentId");
            return;
        }

        $this->externalPaymentService->processPaymentConfirmation($paymentIntent, $mpPayment);

        if ($mpPayment['status'] === 'approved') {
            PaymentApproved::dispatch($paymentIntent);
        }

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

    
}