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
use App\DTOs\CreateOrderDTO;
use App\Services\StockService;


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
     */
    public function createOrderForFilho(Filho $filho, CreateOrderDTO $data): Order 
    {
        return DB::transaction(function () use ($filho, $data) {
            // 1. Bloqueio por Pessimistic Locking no Filho para evitar gasto duplo simultâneo
            $filho = Filho::where('id', $filho->id)->lockForUpdate()->first();

            if ($filho->is_blocked_by_debt) {
                throw new \App\Exceptions\FilhoBlockedException($filho->block_reason ?? 'Filho bloqueado para compras');
            }

            // 2. Validar e preparar itens (já possui lockForUpdate nos produtos internamente)
            $preparedItems = $this->validateAndPrepareItems($data->items);
            $subtotal = collect($preparedItems)->sum('subtotal');
            $total = $subtotal - ($data->discount ?? 0);

            // 3. Regra de Negócio: Pagamento via Carteira
            $isWallet = $data->payment_method === 'carteira';
            
            if ($isWallet) {
                if ($total > $filho->credit_available) {
                    throw new \App\Exceptions\InsufficientCreditException("Crédito insuficiente. Disponível: R$ " . number_format($filho->credit_available, 2, ',', '.'));
                }
                // Debitar da carteira
                $filho->increment('credit_used', $total);
            }

            // 4. Determinar status (PDV + Carteira = Delivered)
            $isDeliveredImmediately = ($data->origin === 'pdv' && $isWallet);
            $orderStatus = $isDeliveredImmediately ? 'delivered' : 'pending';
            $itemStatus = $isDeliveredImmediately ? 'delivered' : 'pending';


            // 5. Criar Pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber($data->origin),
                'filho_id' => $filho->id,
                'created_by_user_id' => $data->createdByUserId,
                'customer_type' => 'filho',
                'customer_name' => $filho->user->name ?? 'Filho',
                'customer_cpf' => $filho->cpf,
                'origin' => $data->origin,
                'status' => 'pending',
                'payment_method_chosen' => $data->payment_method,
                'subtotal' => $subtotal,
                'discount' => $data->discount ?? 0,
                'total' => $total,
                'paid_at' => $isWallet ? now() : null, // Se for carteira, já está "pago" (debitado no limite)
                'delivered_at' => $isDeliveredImmediately ? now() : null,
            ]);

            // 6. Criar Itens com status condicional
            foreach ($preparedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                    'preparation_status' => $itemStatus,
                ]);
            }

            $order->load(['items.product', 'filho.user']);
            // Reserva de Stock
            app(StockService::class)->reserveStock($order);

            if( $isDeliveredImmediately ){
                $order->update(['status' => $orderStatus]);
            }
            

            return $order;
        });
    }

   
    public function createOrderForGuest(  CreateOrderDTO $data ): Order {

        return DB::transaction(function () use ($data) {
            
            // 1. Validar e preparar items
            $preparedItems = $this->validateAndPrepareItems($data->items);

            // 2. Calcular totais
            $subtotal = collect($preparedItems)->sum('subtotal');
            $total = $subtotal - ($data->discount ?? 0);

            $methods = ['dinheiro', 'credito', 'debito'];
            // 4. Determinar status (PDV + Dinheiro = Delivered)
            $isDeliveredImmediately = ($data->origin === 'pdv' && in_array($data->payment_method, $methods) );
            $orderStatus = $isDeliveredImmediately ? 'completed' : 'pending';
            $itemStatus = $isDeliveredImmediately ? 'delivered' : 'pending';


            // 3. Criar pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber($data->origin),
                'created_by_user_id' => $data->createdByUserId,
                'customer_type' => $data->customerType,
                'customer_name' => $data->guestName,
                'guest_name' => $data->guestName,
                'origin' => $data->origin,
                'status' => 'pending',
                'is_invoiced' => $isDeliveredImmediately ? true : false,
                'invoiced_at' => $isDeliveredImmediately ? now() : null,
                'payment_method_chosen' => $data->payment_method,
                'subtotal' => $subtotal,
                'discount_amount' => $data->discount ?? 0,
                'total' => $total,
                'paid_at' => $isDeliveredImmediately ? now() : null,
                'delivered_at' => $isDeliveredImmediately ? now() : null,
            ]);

            // 4. Criar items
            foreach ($preparedItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                    'preparation_status' => $itemStatus,
                ]);
            }

            $order->load('items');

            // Reserva de Stock
            app(StockService::class)->reserveStock($order);

            if( $isDeliveredImmediately ){
                $order->update(['status' => $orderStatus]);
            }

            return $order;
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
            
         //   $this->incrementStock($items);

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
    protected function validateAndPrepareItems(iterable $rawItems): array
    {
        $prepared = [];
        
        $productIds = collect($rawItems)->map(fn($item) => $item->productId)->unique();

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($rawItems as $item) {
            $product = $products->get($item->productId);

            if (!$product) {
                throw new Exception("Produto ID {$item->productId} não encontrado ou inativo");
            }

            if ($product->track_stock && $product->stock_quantity < $item->quantity) {
                throw new Exception("Estoque insuficiente para o produto: {$product->name}");
            }

            $quantity = (int) $item->quantity;
            $unitPrice = (float) $product->price;

            $prepared[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $quantity * $unitPrice,
                'total' => $quantity * $unitPrice
            ];
        }

        return $prepared;
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