<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentIntent;
use App\Models\Filho;
use App\Exceptions\MercadoPagoException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\CreditConsumptionService;

/**
 * Payment Service - Orquestrador Central de Pagamentos
 * 
 * Responsabilidades:
 * - Orquestrar todos os métodos de pagamento
 * - Delegar para services especializados
 * - Processar saldo interno (balance)
 * - Gerenciar status de pagamentos
 * - Reembolsos e cancelamentos
 * 
 * Fluxo:
 * OrderController → PaymentService → CheckoutTransparenteService | Lógica Interna
 */
class PaymentService
{
    public function __construct(
        protected CheckoutTransparenteService $checkoutTransparente,
        protected MercadoPagoService $mercadoPago,
        protected CreditConsumptionService $creditConsumption
    ) {}

    // =========================================================
    // ORQUESTRAÇÃO PRINCIPAL
    // =========================================================

    /**
     * Processar pagamento de um pedido
     * 
     * @param Order $order Pedido a ser pago
     * @param string $method Método: 'balance' | 'pix' | 'credit_card'
     * @param array $data Dados adicionais (card_token, installments, etc)
     * @return array Resultado do processamento
     * @throws Exception
     */
    public function processOrderPayment(Order $order, string $method, array $data = []): array
    {
        // Validar pedido
        $this->validateOrderForPayment($order);

        // Validar método
        if (!in_array($method, ['balance', 'pix', 'credit_card', 'debit_card'])) {
            throw new Exception("Método de pagamento inválido: {$method}");
        }

        // Delegar para método específico
        return match($method) {
            'balance' => $this->processBalancePayment($order),
            'pix' => $this->processPixPayment($order),
            'credit_card' => $this->processCardPayment($order, $data),
            'debit_card' => $this->processCardPayment($order, $data),
        };
    }

    // =========================================================
    // SALDO INTERNO (BALANCE)
    // =========================================================

    /**
     * Processar pagamento com saldo interno
     * ✅ ATUALIZADO: Usa CreditConsumptionService
     */
    protected function processBalancePayment(Order $order): array
    {
        try {
            // Delegar para CreditConsumptionService
            $result = $this->creditConsumption->consumeLimit($order);
            
            Log::info('Pagamento com saldo interno processado via CreditConsumptionService', [
                'order_id' => $order->id,
                'amount' => $order->total,
                'filho_id' => $order->filho_id,
                'new_credit_available' => $result['new_credit_available'],
            ]);
            
            return [
                'success' => true,
                'method' => 'balance',
                'order_status' => 'paid',
                'payment_status' => 'paid',
                'balance' => [
                    'current' => $result['new_credit_available'],
                    'credit_limit' => $result['credit_limit'],
                    'credit_used' => $result['credit_used'],
                ],
                'message' => $result['message'],
            ];
            
        } catch (\App\Exceptions\InsufficientCreditException $e) {
            throw $e;
        } catch (\App\Exceptions\PaymentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erro ao processar saldo interno', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // =========================================================
    // PIX (Mercado Pago)
    // =========================================================

    /**
     * Processar pagamento PIX
     * Delega para CheckoutTransparenteService
     */
    protected function processPixPayment(Order $order): array
    {
        try {
            $result = $this->checkoutTransparente->createPixPayment($order);

            if (!isset($result['payment_intent_id']) || !isset($result['mp_payment_id'])) {
                Log::error('Resposta PIX inválida');
                throw new Exception('Resposta inválida');
            }


            
            return [
                'success' => true,
                'method' => 'pix',
                'payment_intent_id' => $result['payment_intent_id'],
                'mp_payment_id' => $result['mp_payment_id'],
                'status' => $result['status'],
                'pix' => $result['pix'],
                'message' => 'QR Code PIX gerado com sucesso',
                'next_step' => 'scan_qr_code',
            ];

        } catch (MercadoPagoException $e) {
            Log::error('Erro ao processar PIX', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Erro ao gerar PIX: ' . $e->getMessage());
        }
    }

    // =========================================================
    // CARTÃO (Mercado Pago)
    // =========================================================

    /**
     * Processar pagamento com cartão
     * Delega para CheckoutTransparenteService
     */
    protected function processCardPayment(Order $order, array $data): array
    {
        // Validar dados do cartão
        if (empty($data['card_token'])) {
            throw new Exception('Token do cartão é obrigatório');
        }

        $cardToken = $data['card_token'];
        $installments = $data['installments'] ?? 1;

        // Validar parcelas
        if ($installments < 1 || $installments > 12) {
            throw new Exception('Número de parcelas inválido (1-12)');
        }

        try {
            $result = $this->checkoutTransparente->createCardPayment(
                $order,
                $cardToken,
                $installments
            );

            Log::info('Pagamento com cartão processado', [
                'order_id' => $order->id,
                'payment_intent_id' => $result['payment_intent_id'],
                'mp_payment_id' => $result['mp_payment_id'],
                'status' => $result['status'],
                'approved' => $result['approved'],
            ]);

            return [
                'success' => true,
                'method' => 'credit_card',
                'payment_intent_id' => $result['payment_intent_id'],
                'mp_payment_id' => $result['mp_payment_id'],
                'status' => $result['status'],
                'approved' => $result['approved'],
                'status_detail' => $result['status_detail'] ?? null,
                'message' => $result['approved'] 
                    ? 'Pagamento aprovado com sucesso!' 
                    : 'Pagamento em processamento',
                'next_step' => $result['approved'] ? 'completed' : 'wait_approval',
            ];

        } catch (MercadoPagoException $e) {
            Log::error('Erro ao processar cartão', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Erro ao processar cartão: ' . $e->getMessage());
        }
    }

    // =========================================================
    // STATUS E CONSULTAS
    // =========================================================

    /**
     * Verificar status de um PaymentIntent
     */
    public function checkPaymentStatus(PaymentIntent $intent): array
    {
        try {
            // Se for balance, já está confirmado
            if ($intent->payment_method === 'balance') {
                return [
                    'success' => true,
                    'status' => 'approved',
                    'approved' => true,
                    'order_status' => $intent->order->status,
                ];
            }

            // Para PIX e Cartão, consultar no CheckoutTransparente
            $result = $this->checkoutTransparente->checkPaymentStatus($intent);

            return [
                'success' => true,
                'status' => $result['status'],
                'approved' => $result['approved'],
                'order_id' => $intent->order_id,
                'order_status' => $intent->order->fresh()->status,
            ];

        } catch (Exception $e) {
            Log::error('Erro ao verificar status de pagamento', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // =========================================================
    // CANCELAMENTO E REEMBOLSO
    // =========================================================

    /**
     * Cancelar PaymentIntent
     */
    public function cancelPaymentIntent(PaymentIntent $intent, string $reason = null): void
    {
        DB::transaction(function () use ($intent, $reason) {
            // Se for balance e já foi aprovado, estornar crédito
            if ($intent->payment_method === 'balance' && $intent->is_approved) {
                $this->refundBalancePayment($intent);
            }

            // Se for PIX/Card, delegar para CheckoutTransparente
            if (in_array($intent->payment_method, ['pix', 'credit_card', 'debit_card'])) {
                $this->checkoutTransparente->cancelPayment($intent);
            }

            Log::info('PaymentIntent cancelado', [
                'intent_id' => $intent->id,
                'order_id' => $intent->order_id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Reembolsar pagamento com saldo interno
     */
    protected function refundBalancePayment(PaymentIntent $intent): void
    {
        $order = $intent->order;
        $filho = $order->filho;
        $payment = $intent->payment;

        if (!$filho || !$payment) {
            throw new Exception('Não é possível estornar: dados incompletos');
        }

        // Estornar crédito
        $filho->credit_used -= $order->total;
        $filho->save();

        // Atualizar payment
        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);

        // Atualizar order
        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);

        Log::info('Saldo interno estornado', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => $order->total,
            'filho_id' => $filho->id,
        ]);
    }

    /**
     * Reembolsar pagamento completo
     */
    public function refundPayment(Payment $payment, ?float $amount = null): Payment
    {
        return DB::transaction(function () use ($payment, $amount) {
            $refundAmount = $amount ?? $payment->amount;

            // Se tem MP payment ID, reembolsar no gateway
            if ($payment->gateway_transaction_id) {
                try {
                    $this->mercadoPago->refundPayment($payment->gateway_transaction_id);
                } catch (Exception $e) {
                    Log::error('Erro ao reembolsar no MP', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // Se for balance, estornar crédito
            if ($payment->method === 'balance') {
                $filho = $payment->order->filho;
                if ($filho) {
                    $filho->credit_used -= $refundAmount;
                    $filho->save();
                }
            }

            // Atualizar payment
            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_amount' => $refundAmount,
            ]);

            // Atualizar order
            $payment->order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);

            Log::info('Pagamento reembolsado', [
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
            ]);

            return $payment->fresh();
        });
    }

    // =========================================================
    // VALIDAÇÕES
    // =========================================================

    /**
     * Validar se pedido pode ser pago
     */
    protected function validateOrderForPayment(Order $order): void
    {
        if ($order->status === 'cancelled') {
            throw new Exception('Pedido foi cancelado');
        }

        if ($order->payment_status === 'paid') {
            throw new Exception('Pedido já foi pago');
        }

        if ($order->total <= 0) {
            throw new Exception('Valor do pedido inválido');
        }

        // Validar filho se for pedido de filho
        if ($order->customer_type === 'filho' && !$order->filho_id) {
            throw new Exception('Pedido não possui filho vinculado');
        }
    }

    /**
     * Obter métodos de pagamento disponíveis para um pedido
     */
    public function getAvailablePaymentMethods(Order $order): array
    {
        $methods = [];

        // Balance - apenas para filhos
        if ($order->customer_type === 'filho' && $order->filho) {
            $methods[] = [
                'method' => 'balance',
                'name' => 'Saldo Interno',
                'available' => $order->filho->credit_available >= $order->total,
                'balance' => [
                    'available' => $order->filho->credit_available,
                    'required' => $order->total,
                    'sufficient' => $order->filho->credit_available >= $order->total,
                ],
            ];
        }

        // PIX - disponível para todos
        $methods[] = [
            'method' => 'pix',
            'name' => 'PIX',
            'available' => $this->mercadoPago->isConfigured(),
        ];

        // Cartão - disponível para todos
        $methods[] = [
            'method' => 'credit_card',
            'name' => 'Cartão de Crédito',
            'available' => $this->mercadoPago->isConfigured(),
            'max_installments' => 12,
        ];

        return $methods;
    }

    // =========================================================
    // ESTATÍSTICAS
    // =========================================================

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
            ->where('status', 'confirmed')
            ->groupBy('method')
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->get()
            ->mapWithKeys(fn($item) => [$item->method => [
                'count' => $item->count,
                'total' => (float) $item->total,
            ]]);

        return [
            'total_received' => (float) (clone $query)->where('status', 'confirmed')->sum('amount'),
            'total_pending' => (float) (clone $query)->whereIn('status', ['pending', 'processing'])->sum('amount'),
            'total_refunded' => (float) (clone $query)->where('status', 'refunded')->sum('refund_amount'),
            'by_method' => $byMethod->toArray(),
            'count_by_status' => [
                'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
                'pending' => (clone $query)->whereIn('status', ['pending', 'processing'])->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
                'refunded' => (clone $query)->where('status', 'refunded')->count(),
            ],
        ];
    }
}