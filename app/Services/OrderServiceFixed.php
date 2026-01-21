<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Filho;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * ✅ MÉTODO CORRIGIDO - Recebe objeto Filho completo
     * 
     * Cria um pedido para um filho (SEM processar pagamento)
     */
    public function createOrderForFilho(
        Filho $filho,
        array $items,
        ?string $notes = null,
        string $source = 'app',
        ?string $createdBy = null,
    ): Order {
        return DB::transaction(function () use ($filho, $items, $notes, $source, $createdBy) {
            
            // 1. Validar se filho pode comprar
            if (!$filho->can_purchase) {
                throw new \Exception($filho->block_reason ?? 'Cliente bloqueado para compras');
            }

            // 2. Validar e preparar items
            $validatedItems = $this->validateAndPrepareItems($items);

            // 3. Calcular total
            $subtotal = collect($validatedItems)->sum('subtotal');

            // 4. Criar pedido
            $order = Order::create([
                'filho_id' => $filho->id,
                'user_id' => $filho->user_id,
                'created_by' => $createdBy ?? $filho->user_id,
                'customer_type' => 'filho',
                'order_number' => $this->generateOrderNumber($source),
                'source' => $source,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'total' => $subtotal,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $notes,
            ]);

            // 5. Criar items do pedido
            foreach ($validatedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // 6. Atualizar estoque (decrementar)
            $this->updateStock($validatedItems, 'decrement');

            Log::info('Pedido criado com sucesso', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'filho_id' => $filho->id,
                'total' => $order->total,
                'source' => $source,
            ]);

            return $order->load('items.product');
        });
    }

    /**
     * Validar produtos e preparar items para criação
     */
    private function validateAndPrepareItems(array $items): array
    {
        $validatedItems = [];
        $productIds = collect($items)->pluck('product_id')->unique();

        // Buscar produtos de uma vez
        $products = Product::whereIn('id', $productIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new \Exception("Produto não encontrado: {$item['product_id']}");
            }

            if (!$product->is_available) {
                throw new \Exception("Produto indisponível: {$product->name}");
            }

            if ($product->stock_quantity < $item['quantity']) {
                throw new \Exception("Estoque insuficiente para: {$product->name}");
            }

            $validatedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'subtotal' => $product->price * $item['quantity'],
            ];
        }

        return $validatedItems;
    }

    /**
     * Atualizar estoque dos produtos
     */
    private function updateStock(array $items, string $operation = 'decrement'): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            
            if ($product) {
                if ($operation === 'decrement') {
                    $product->decrement('stock_quantity', $item['quantity']);
                } else {
                    $product->increment('stock_quantity', $item['quantity']);
                }
            }
        }
    }

    /**
     * Gerar número único do pedido
     */
    private function generateOrderNumber(string $source = 'app'): string
    {
        $prefix = match($source) {
            'app' => 'APP',
            'pdv' => 'PDV',
            'totem' => 'TOM',
            default => 'ORD',
        };

        // Pegar último número do dia
        $today = now()->format('Ymd');
        $lastOrder = Order::where('order_number', 'like', "{$prefix}-{$today}-%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $today, $newNumber);
    }

    /**
     * Cancelar pedido e restaurar estoque
     */
    public function cancelOrder(Order $order, string $reason): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            
            // Validar se pode cancelar
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new \Exception('Pedido não pode ser cancelado neste status');
            }

            // Restaurar estoque
            $items = $order->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ])->toArray();

            $this->updateStock($items, 'increment');

            // Atualizar pedido
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Se havia pagamento com crédito, estornar
            if ($order->payment_status === 'paid') {
                $this->refundInternalCredit($order);
            }

            Log::info('Pedido cancelado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason,
            ]);

            return $order->fresh();
        });
    }

    /**
     * Estornar crédito interno
     */
    private function refundInternalCredit(Order $order): void
    {
        $creditPayment = $order->payments()
            ->where('payment_method', 'credit')
            ->where('status', 'approved')
            ->first();

        if ($creditPayment) {
            $filho = $order->filho;
            $filho->credit_used -= $creditPayment->amount;
            $filho->save();

            // Registrar estorno
            $creditPayment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);
        }
    }

    /**
     * Confirmar pedido (após pagamento aprovado)
     */
    public function confirmOrder(Order $order): Order
    {
        $order->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        Log::info('Pedido confirmado', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $order->fresh();
    }

    /**
     * Marcar pedido como preparando
     */
    public function startPreparing(Order $order): Order
    {
        $order->update([
            'status' => 'preparing',
        ]);

        return $order->fresh();
    }

    /**
     * Marcar pedido como pronto
     */
    public function markAsReady(Order $order): Order
    {
        $order->update([
            'status' => 'ready',
        ]);

        return $order->fresh();
    }

    /**
     * Marcar pedido como entregue
     */
    public function markAsDelivered(Order $order): Order
    {
        $order->update([
            'status' => 'delivered',
            'completed_at' => now(),
        ]);

        return $order->fresh();
    }
}





/* OrderService Anterior */


<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Filho;
use App\Models\User;
use App\DTOs\CreateOrderDTO;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\OrderCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly CreditService $creditService
    ) {}

    /**
     * Criar novo pedido
     */
    public function create(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // 1. Validações de negócio
            $this->validateOrder($dto);
            
            // 2. Enriquecer items com dados dos produtos
            $enrichedItems = $this->enrichItems($dto->items);
            
            // 3. Criar pedido
            $order = $this->createOrder($dto, $enrichedItems);
            
            // 4. Criar items do pedido
            $this->createOrderItems($order, $enrichedItems);
            
            // 5. Atualizar estoque
            $this->updateStock($enrichedItems);
            
            // 6. Se filho: debitar crédito
            if ($dto->isFilho()) {
                $this->debitCredit($dto->filhoId, $dto->total);
            }
            
            // 7. Carregar relacionamentos
            return $order->load(['items.product', 'filho.user']);
        });
    }

     /**
     * Enriquecer items com dados dos produtos
     */
    private function enrichItems($items)
    {
        return $items->map(function (CreateOrderItemDTO $item) {
            $product = Product::findOrFail($item->productId);
            
            return new CreateOrderItemDTO(
                productId: $item->productId,
                quantity: $item->quantity,
                unitPrice: $product->price, // Usar preço atual do produto
                subtotal: $product->price * $item->quantity,
                discount: $item->discount,
                total: ($product->price * $item->quantity) - $item->discount,
                notes: $item->notes,
                modifiers: $item->modifiers,
                productName: $product->name,
                productSku: $product->sku,
            );
        });
    }

    /**
     * Criar registro do pedido
     */
    private function createOrder(CreateOrderDTO $dto, $enrichedItems): Order
    {
        $orderData = [
            'order_number' => $this->generateOrderNumber(),
            'customer_type' => $dto->customerType,
            'origin' => $dto->origin,
            'device_id' => $dto->deviceId,
            'created_by_user_id' => $dto->createdByUserId,
            'subtotal' => $dto->subtotal,
            'discount' => $dto->discount,
            'total' => $dto->total,
            'notes' => $dto->notes,
            'kitchen_notes' => $dto->kitchenNotes,
            'status' => 'pending',
        ];
        
        // Dados do filho
        if ($dto->isFilho()) {
            $orderData['filho_id'] = $dto->filhoId;
            $filho = Filho::find($dto->filhoId);
            $orderData['customer_name'] = $filho->user->name ?? null;
            $orderData['customer_cpf'] = $filho->cpf ?? null;
        }
        
        // Dados do visitante
        if ($dto->isGuest()) {
            $orderData['guest_name'] = $dto->guestName;
            $orderData['guest_document'] = $dto->guestDocument;
            $orderData['customer_phone'] = $dto->guestPhone;
        }
        
        return Order::create($orderData);
    }

    /**
     * Criar items do pedido
     */
    private function createOrderItems(Order $order, $items): void
    {
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->productId,
                'product_name' => $item->productName,
                'product_sku' => $item->productSku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unitPrice,
                'subtotal' => $item->subtotal,
                'discount' => $item->discount,
                'total' => $item->total,
                'notes' => $item->notes,
                'modifiers' => $item->modifiers,
                'preparation_status' => 'pending',
            ]);
        }
    }

    /**
     * Atualizar estoque dos produtos
     */
    private function updateStock($items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item->productId);
            
            if ($product && $product->track_stock) {
                $product->decrement('stock_quantity', $item->quantity);
            }
        }
    }

    /**
     * Debitar crédito do filho
     */
    private function debitCredit(string $filhoId, float $amount): void
    {
        $filho = Filho::findOrFail($filhoId);
        $filho->increment('credit_used', $amount);
    }

    /**
     * Criar pedido a partir de sincronização offline
     */
    public function createFromSync(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $orderNumber = $this->generateOrderNumber();

            $order = Order::create([
                'order_number' => $orderNumber,
                'local_id' => $data['local_id'],
                'device_id' => $data['device_id'],
                'filho_id' => $data['filho_id'],
                'guest_name' => $data['guest_name'],
                'guest_document' => $data['guest_document'],
                'origin' => $data['origin'],
                'status' => 'completed', // Já foi processado offline
                'total' => $data['total'],
                'payment_method' => $data['payment_method'],
                'operator_id' => $data['operator_id'],
                'synced_at' => now(),
                'created_at' => $data['created_at_local'],
            ]);

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // Decrementar estoque (já foi vendido offline)
                $product = Product::find($item['product_id']);
                if ($product) {
                    $this->stockService->decrement(
                        product: $product,
                        quantity: $item['quantity'],
                        reason: "Venda offline - Pedido #{$orderNumber}",
                        orderId: $order->id
                    );
                }
            }

            // Debitar crédito se for filho
            if ($data['filho_id']) {
                $filho = Filho::find($data['filho_id']);
                if ($filho) {
                    $this->creditService->debit(
                        filho: $filho,
                        amount: $data['total'],
                        description: "Pedido offline #{$orderNumber}",
                        orderId: $order->id
                    );
                }
            }

            return $order;
        });
    }

   /**
     * Cancelar pedido
     */
    public function cancel(Order $order, string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            // Validar se pode cancelar
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new \Exception('Este pedido não pode mais ser cancelado');
            }
            
            // Restaurar estoque
            foreach ($order->items as $item) {
                if ($item->product && $item->product->track_stock) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }
            
            // Restaurar crédito (se filho)
            if ($order->isFilho() && $order->filho) {
                $order->filho->decrement('credit_used', $order->total);
            }
            
            // Atualizar status
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
            
            return $order->fresh();
        });
    }

    /**
     * Gerar número único do pedido
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        
        // Garantir unicidade
        $orderNumber = "PED-{$date}-{$random}";
        
        while (Order::where('order_number', $orderNumber)->exists()) {
            $random = strtoupper(Str::random(4));
            $orderNumber = "PED-{$date}-{$random}";
        }
        
        return $orderNumber;
    }

    /**
     * Atualizar status do pedido
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['delivered'],
            'delivered' => ['completed'],
        ];
        
        $currentStatus = $order->status;
        
        if (!isset($validTransitions[$currentStatus])) {
            throw new \Exception("Status atual '{$currentStatus}' não permite transições");
        }
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \Exception(
                "Transição de '{$currentStatus}' para '{$newStatus}' não é permitida"
            );
        }
        
        $timestampField = match ($newStatus) {
            'confirmed' => null,
            'preparing' => 'preparing_at',
            'ready' => 'ready_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
            default => null,
        };
        
        $updateData = ['status' => $newStatus];
        
        if ($timestampField) {
            $updateData[$timestampField] = now();
        }
        
        $order->update($updateData);
        
        return $order->fresh();
    }

    

    /**
     * Obter estatísticas de pedidos
     */
    public function getStatistics(string $period = 'today'): array
    {
        $query = Order::query();

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
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        return [
            'total_orders' => (clone $query)->count(),
            'completed_orders' => (clone $query)->where('status', 'completed')->count(),
            'cancelled_orders' => (clone $query)->where('status', 'cancelled')->count(),
            'pending_orders' => (clone $query)->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'total_revenue' => (clone $query)->where('status', 'completed')->sum('total'),
            'average_ticket' => (clone $query)->where('status', 'completed')->avg('total') ?? 0,
        ];
    }
}