<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Events\PaymentConfirmed;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Confirmar pagamento
     */
    public function confirmPayment(Payment $payment, array $gatewayData = []): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayData) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'gateway_id' => $gatewayData['gateway_id'] ?? null,
                'gateway_response' => $gatewayData,
            ]);

            // Atualizar pedido
            $order = $payment->order;
            $order->update([
                'status' => 'confirmed',
                'payment_confirmed_at' => now(),
            ]);

            // Disparar evento
            event(new PaymentConfirmed($payment));

            return $payment;
        });
    }

    /**
     * Cancelar pagamento
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): Payment
    {
        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $payment;
    }

    /**
     * Reembolsar pagamento
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment
    {
        $refundAmount = $amount ?? $payment->amount;

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_amount' => $refundAmount,
        ]);

        return $payment;
    }

    /**
     * Criar pagamento pendente
     */
    public function createPendingPayment(
        Order $order,
        string $method,
        float $amount,
        array $metadata = []
    ): Payment {
        return Payment::create([
            'order_id' => $order->id,
            'method' => $method,
            'amount' => $amount,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Verificar pagamentos expirados
     */
    public function expirePendingPayments(): int
    {
        return Payment::query()
            ->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
                'expired_at' => now(),
            ]);
    }

    /**
     * Obter estatísticas de pagamentos
     */
    public function getStatistics(string $period = 'today'): array
    {
        $query = Payment::query();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
        }

        $byMethod = (clone $query)
            ->where('status', 'paid')
            ->groupBy('method')
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->pluck('total', 'method')
            ->toArray();

        return [
            'total_received' => (clone $query)->where('status', 'paid')->sum('amount'),
            'total_pending' => (clone $query)->where('status', 'pending')->sum('amount'),
            'total_refunded' => (clone $query)->where('status', 'refunded')->sum('refund_amount'),
            'by_method' => $byMethod,
            'count_by_status' => [
                'paid' => (clone $query)->where('status', 'paid')->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
                'refunded' => (clone $query)->where('status', 'refunded')->count(),
            ],
        ];
    }


    public function generatePixCode(array $data): string
    {
        // Integrar com gateway real (PagSeguro, MercadoPago, etc)
        // Por enquanto, mock:
        return "00020126580014br.gov.bcb.pix0136" . $data['invoice_id'] . "...";
    }
    
    public function registerPendingConfirmation($invoice, array $metadata): void
    {
        // Salvar confirmação pendente
        $invoice->update([
            'pending_confirmation' => true,
            'confirmed_by_user_at' => $metadata['confirmed_by_user_at'],
        ]);
        
        // Enviar notificação para admin
        // ...
    }
}