<?php
// app/Services/Payment/PaymentService.php

namespace App\Services\Payment;

use App\DTOs\Payment\PaymentDTO;
use App\Events\PaymentProcessed;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Filho\BalanceService;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly PaymentGatewayService $gatewayService,
    ) {}

    public function process(Order $order, PaymentDTO $dto): Payment
    {
        return DB::transaction(function () use ($order, $dto) {
            // Criar registro de pagamento
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $dto->method,
                'amount' => $dto->amount,
                'change_amount' => $dto->changeAmount,
                'status' => 'pending',
                'installments' => $dto->installments,
            ]);

            // Processar de acordo com o método
            match ($dto->method) {
                'saldo_filho' => $this->processSaldoFilho($order, $payment),
                'dinheiro' => $this->processDinheiro($payment),
                'pix', 'credito', 'debito' => $this->processGateway($payment, $dto),
                default => throw new \Exception("Método de pagamento inválido"),
            };

            // Atualizar status do pedido
            $order->markAsPaid();

            // Disparar evento
            event(new PaymentProcessed($payment));

            return $payment->fresh(['order']);
        });
    }

    private function processSaldoFilho(Order $order, Payment $payment): void
    {
        if (!$order->filho) {
            throw new \Exception("Pedido sem filho associado");
        }

        // Debitar do saldo do filho
        $this->balanceService->debit(
            $order->filho,
            $payment->amount,
            "Pedido #{$order->order_number}",
            $order
        );

        $payment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    private function processDinheiro(Payment $payment): void
    {
        // Pagamento em dinheiro é confirmado imediatamente
        $payment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    private function processGateway(Payment $payment, PaymentDTO $dto): void
    {
        try {
            // Processar via gateway
            $response = $this->gatewayService->process($dto);

            $payment->update([
                'status' => 'confirmed',
                'gateway_transaction_id' => $response['transaction_id'],
                'gateway_name' => $response['gateway'],
                'gateway_response' => $response,
                'card_last_digits' => $dto->cardLastDigits,
                'card_brand' => $dto->cardBrand,
                'pix_key' => $dto->pixKey,
                'pix_qrcode' => $dto->pixQrcode,
                'confirmed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);

            throw $e;
        }
    }

    public function refund(Payment $payment): bool
    {
        if (!$payment->isConfirmed()) {
            throw new \Exception("Pagamento não pode ser estornado");
        }

        return DB::transaction(function () use ($payment) {
            // Se foi pago com saldo, creditar de volta
            if ($payment->method === 'saldo_filho' && $payment->order->filho) {
                $this->balanceService->refund(
                    $payment->order->filho,
                    $payment->amount,
                    "Estorno pagamento #{$payment->order->order_number}",
                    $payment->order
                );
            }

            // Se foi gateway, processar estorno
            if ($payment->requiresGateway()) {
                $this->gatewayService->refund($payment);
            }

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            return true;
        });
    }
}