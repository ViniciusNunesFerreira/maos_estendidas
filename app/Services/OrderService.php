<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Filho;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Order Service - Gestão Unificada de Pedidos
 * 
 * Responsabilidades:
 * - Criar pedidos (App, PDV, Admin)
 * - Validar estoque e preços
 * - Gerenciar status de pedidos
 * - Cancelamentos e estornos
 * - Estatísticas
 * 
 * Atende:
 * - App (Filhos)
 * - PDV (Filhos e Visitantes)
 * - Admin (Manual)
 */
class OrderService
{
    // =========================================================
    // CRIAÇÃO DE PEDIDOS - APP/PDV
    // =========================================================

    /**
     * Criar pedido para FILHO (App ou PDV)
     * 
     * @param Filho $filho Filho que está comprando
     * @param array $items Array de items: [['product_id' => uuid, 'quantity' => int]]
     * @param string $origin 'app' | 'pdv' | 'totem'
     * @param string|null $notes Observações
     * @param string|null $createdById ID do usuário que criou (operador PDV)
     * @return Order
     */
    public function createOrderForFilho(
        Filho $filho,
        array $items,
        string $origin = 'app',
        ?string $notes = null,
        ?string $createdById = null
    ): Order {
        return DB::transaction(function () use ($filho, $items, $origin, $notes) {
            
            // 1. Validações de negócio
            if ($filho->is_blocked_by_debt) {
                throw new Exception($filho->block_reason ?? 'Filho bloqueado para compras');
            }

            if (!$filho->can_purchase) {
                throw new Exception('Filho não pode realizar compras no momento');
            }

            // 2. Validar e preparar items
            $preparedItems = $this->validateAndPrepareItems($items);

            // 3. Calcular totais
            $subtotal = collect($preparedItems)->sum('subtotal');
            $discount = 0; // Implementar lógica de cupom se necessário
            $total = $subtotal - $discount;

            // 4. Criar pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber($origin),
                'filho_id' => $filho->id,
                'created_by_user_id' => $createdById ?? $filho->user_id,
                'customer_type' => 'filho',
                'customer_name' => $filho->user->name ?? 'Filho',
                'customer_cpf' => $filho->cpf,
                'origin' => $origin,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $total,
                'notes' => $notes,
            ]);

            // 5. Criar items
            foreach ($preparedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                    'preparation_status' => $item['preparation_status'],
                ]);
            }

            // 6. Reservar estoque (será decrementado de fato no pagamento)
            $this->decrementStock($preparedItems);

            Log::info('Pedido criado para filho', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'filho_id' => $filho->id,
                'total' => $total,
                'origin' => $origin,
            ]);

            return $order->load(['items.product', 'filho.user']);
        });
    }

    /**
     * Criar pedido para VISITANTE (PDV ou Totem)
     * 
     * @param array $items Array de items
     * @param string $guestName Nome do visitante
     * @param string|null $guestDocument CPF/RG (opcional)
     * @param string|null $guestPhone Telefone (opcional)
     * @param string $origin 'pdv' | 'totem'
     * @param string|null $notes Observações
     * @param int|null $createdById ID do operador
     * @return Order
     */
    public function createOrderForGuest(
        array $items,
        string $guestName,
        ?string $guestDocument = null,
        ?string $guestPhone = null,
        string $origin = 'pdv',
        ?string $notes = null,
        ?int $createdById = null
    ): Order {
        return DB::transaction(function () use (
            $items, $guestName, $guestDocument, $guestPhone, $origin, $notes, $createdById
        ) {
            
            // 1. Validar e preparar items
            $preparedItems = $this->validateAndPrepareItems($items);

            // 2. Calcular totais
            $subtotal = collect($preparedItems)->sum('subtotal');
            $discount = 0;
            $total = $subtotal - $discount;

            // 3. Criar pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber($origin),
                'created_by_user_id' => $createdById,
                'customer_type' => 'guest',
                'customer_name' => $guestName,
                'guest_name' => $guestName,
                'guest_document' => $guestDocument,
                'customer_phone' => $guestPhone,
                'origin' => $origin,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $total,
                'notes' => $notes,
            ]);

            // 4. Criar items
            foreach ($preparedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                    'preparation_status' => $item['preparation_status'],
                ]);
            }

            // 5. Decrementar estoque
            $this->decrementStock($preparedItems);

            Log::info('Pedido criado para visitante', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'guest_name' => $guestName,
                'total' => $total,
                'origin' => $origin,
            ]);

            return $order->load('items.product');
        });
    }

    // =========================================================
    // SINCRONIZAÇÃO OFFLINE (PDV)
    // =========================================================

    /**
     * Criar pedido a partir de sincronização offline
     */
    public function createFromSync(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $orderNumber = $this->generateOrderNumber('pdv');

            $order = Order::create([
                'order_number' => $orderNumber,
                'local_id' => $data['local_id'],
                'device_id' => $data['device_id'],
                'filho_id' => $data['filho_id'] ?? null,
                'guest_name' => $data['guest_name'] ?? null,
                'guest_document' => $data['guest_document'] ?? null,
                'customer_type' => $data['customer_type'] ?? 'guest',
                'origin' => $data['origin'] ?? 'pdv',
                'status' => 'completed',
                'payment_status' => 'paid',
                'total' => $data['total'],
                'payment_method' => $data['payment_method'] ?? null,
                'created_by_user_id' => $data['operator_id'] ?? null,
                'is_synced' => true,
                'synced_at' => now(),
                'created_at' => $data['created_at_local'] ?? now(),
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
                if ($product && $product->track_stock) {
                    $product->decrement('stock_quantity', $item['quantity']);
                }
            }

            Log::info('Pedido sincronizado do offline', [
                'order_id' => $order->id,
                'local_id' => $data['local_id'],
            ]);

            return $order;
        });
    }

    // =========================================================
    // CANCELAMENTO
    // =========================================================

    /**
     * Cancelar pedido
     * Restaura estoque
     */
    public function cancelOrder(Order $order, string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason) {
            // Validar se pode cancelar
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new Exception('Este pedido não pode mais ser cancelado');
            }

            // Restaurar estoque
            $items = $order->items->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity
                ];
            })->toArray();
            
            $this->incrementStock($items);

            // Atualizar status
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            Log::info('Pedido cancelado', [
                'order_id' => $order->id,
                'reason' => $reason,
            ]);

            return $order->fresh();
        });
    }

    // =========================================================
    // GESTÃO DE STATUS
    // =========================================================

    /**
     * Atualizar status do pedido com validação de transições
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
            throw new Exception("Status atual '{$currentStatus}' não permite transições");
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new Exception(
                "Transição de '{$currentStatus}' para '{$newStatus}' não é permitida"
            );
        }

        $timestampField = match ($newStatus) {
            'confirmed' => 'confirmed_at',
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

        Log::info('Status do pedido atualizado', [
            'order_id' => $order->id,
            'from' => $currentStatus,
            'to' => $newStatus,
        ]);

        return $order->fresh();
    }

    // =========================================================
    // VALIDAÇÃO E PREPARAÇÃO DE ITEMS
    // =========================================================

    /**
     * Validar estoque e preparar items com preços atuais
     */
    protected function validateAndPrepareItems(array $rawItems): array
    {
        $prepared = [];
        $productIds = collect($rawItems)->pluck('product_id')->unique();

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($rawItems as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new Exception("Produto ID {$item['product_id']} não encontrado ou inativo");
            }

            if ($product->track_stock && $product->stock_quantity < $item['quantity']) {
                throw new Exception("Estoque insuficiente para o produto: {$product->name}");
            }

            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price;

            $prepared[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $quantity * $unitPrice,
                'total' => $quantity * $unitPrice,
                'preparation_status' => $product->requires_preparation ? 'pending' : 'delivered',
            ];
        }

        return $prepared;
    }

    // =========================================================
    // GESTÃO DE ESTOQUE
    // =========================================================

    /**
     * Decrementar estoque
     */
    protected function decrementStock(array $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->track_stock) {
                $product->decrement('stock_quantity', $item['quantity']);
            }
        }
    }

    /**
     * Incrementar estoque (cancelamento)
     */
    protected function incrementStock(array $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->track_stock) {
                $product->increment('stock_quantity', $item['quantity']);
            }
        }
    }

    // =========================================================
    // UTILIDADES
    // =========================================================

    /**
     * Gerar número único do pedido
     */
    protected function generateOrderNumber(string $origin): string
    {
        $prefix = match($origin) {
            'pdv' => 'PDV',
            'totem' => 'TOT',
            default => 'APP',
        };

        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));

        $orderNumber = "{$prefix}-{$date}-{$random}";

        // Garantir unicidade
        while (Order::where('order_number', $orderNumber)->exists()) {
            $random = strtoupper(Str::random(4));
            $orderNumber = "{$prefix}-{$date}-{$random}";
        }

        return $orderNumber;
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
            'total_revenue' => (float) (clone $query)->where('status', 'completed')->sum('total'),
            'average_ticket' => (float) (clone $query)->where('status', 'completed')->avg('total') ?? 0,
        ];
    }
}