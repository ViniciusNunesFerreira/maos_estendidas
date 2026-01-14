<?php
// app/Services/Order/OrderService.php

namespace App\Services\Order;

use App\DTOs\Order\OrderDTO;
use App\Events\OrderCreated;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientStockException;
use App\Models\Filho;
use App\Models\Order;
use App\Models\Product;
use App\Services\Filho\BalanceService;
use App\Services\Product\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\DTOs\CreateOrderDTO;
use App\Exceptions\InsufficientCreditException;
use App\Exceptions\BlockedByDebtException;
use App\Exceptions\FilhoNotActiveException;


class OrderService
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly StockService $stockService,
        private SatService $satService,
    ) {}

    public function create(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // Determinar tipo de cliente
            $isGuest = empty($dto->filhoId);
            
            // Validações específicas para Filho
            $filho = null;
            if (!$isGuest) {
                $filho = Filho::findOrFail($dto->filhoId);
                $this->validateFilhoCanPurchase($filho, $dto->totalAmount);
            }
            
            // Criar pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_type' => $isGuest ? 'guest' : 'filho',
                'filho_id' => $isGuest ? null : $filho->id,
                'guest_name' => $dto->guestName,
                'guest_document' => $dto->guestDocument,
                'user_id' => $dto->userId,
                'device_id' => $dto->deviceId,
                'source' => $dto->source,
                'location' => $dto->location,
                'subtotal' => $dto->subtotal,
                'discount_amount' => $dto->discountAmount ?? 0,
                'discount_reason' => $dto->discountReason,
                'total_amount' => $dto->totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $dto->notes,
            ]);
            
            // Adicionar itens
            foreach ($dto->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? $product->price,
                    'subtotal' => $item['quantity'] * ($item['unit_price'] ?? $product->price),
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total' => ($item['quantity'] * ($item['unit_price'] ?? $product->price)) - ($item['discount_amount'] ?? 0),
                ]);
                
                // Dar baixa no estoque
                $this->stockService->decrementStock($product, $item['quantity']);
            }
            
            // Se for FILHO: usar crédito (sistema pós-pago)
            if (!$isGuest) {
                $filho->useCredit($dto->totalAmount);
            }
            
            return $order;
        });
    }

    /**
     * Validar se filho pode comprar
     */
    private function validateFilhoCanPurchase(Filho $filho, float $amount): void
    {
        // Verificar status ativo
        if ($filho->status !== 'active') {
            throw new FilhoNotActiveException(
                "Cadastro não está ativo. Status atual: {$filho->status}"
            );
        }
        
        // Verificar bloqueio por inadimplência
        if ($filho->is_blocked_by_debt) {
            throw new BlockedByDebtException(
                "Compras bloqueadas por inadimplência. {$filho->block_reason}"
            );
        }
        
        // Verificar limite de faturas vencidas
        if (!$filho->can_purchase) {
            throw new BlockedByDebtException(
                "Limite de faturas vencidas atingido ({$filho->total_overdue_invoices}/{$filho->max_overdue_invoices}). Regularize suas pendências."
            );
        }
        
        // Verificar crédito disponível
        if ($amount > $filho->credit_available) {
            throw new InsufficientCreditException(
                "Crédito insuficiente. Disponível: R$ " . number_format($filho->credit_available, 2, ',', '.') .
                " | Necessário: R$ " . number_format($amount, 2, ',', '.')
            );
        }
    }


    /**
     * Finalizar pedido
     */
    public function complete(Order $order, array $payments = []): Order
    {
        return DB::transaction(function () use ($order, $payments) {
            // Para visitantes: processar pagamento
            if ($order->is_guest) {
                $this->processGuestPayments($order, $payments);
            }
            // Para filhos: não precisa pagamento imediato (vai para fatura)
            
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            // Emitir SAT se configurado
            if (config('casalar.sat.enabled')) {
                $this->satService->emitCupom($order);
            }
            
            return $order->fresh();
        });
    }

    /**
     * Processar pagamentos de visitante
     */
    private function processGuestPayments(Order $order, array $payments): void
    {
        $totalPaid = 0;
        
        foreach ($payments as $payment) {
            $order->payments()->create([
                'amount' => $payment['amount'],
                'method' => $payment['method'],
                'reference' => $payment['reference'] ?? null,
                'status' => 'completed',
                'paid_at' => now(),
            ]);
            
            $totalPaid += $payment['amount'];
        }
        
        if ($totalPaid >= $order->total_amount) {
            $order->update(['payment_status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $order->update(['payment_status' => 'partial']);
        }
    }


    /**
     * Cancelar pedido
     */
    public function cancel(Order $order, string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            // Restaurar estoque
            foreach ($order->items as $item) {
                if ($item->product) {
                    $this->stockService->incrementStock($item->product, $item->quantity);
                }
            }
            
            // Se for filho: restaurar crédito
            if ($order->is_filho && $order->filho) {
                $order->filho->restoreCredit($order->total_amount);
            }
            
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            
            return $order->fresh();
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        // Atualizar timestamps específicos
        match ($status) {
            'paid' => $order->markAsPaid(),
            'preparing' => $order->markAsPreparing(),
            'ready' => $order->markAsReady(),
            'delivered' => $order->markAsDelivered(),
            default => null,
        };

        return $order->fresh();
    }

    /**
     * Gerar número do pedido
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        return "PED-{$date}-{$random}";
    }
}