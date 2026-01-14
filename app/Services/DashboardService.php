<?php

namespace App\Services;

use App\Models\Filho;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Obter todas as métricas do dashboard
     * Cache de 5 minutos
     */
    public function getMetrics(): array
    {
        return Cache::remember('dashboard.metrics', 300, function () {
            return [
                'today_sales' => $this->getTodaySales(),
                'today_orders' => $this->getTodayOrders(),
                'active_filhos' => $this->getActiveFilhos(),
                'overdue_invoices' => $this->getOverdueInvoices(),
                'pending_approval' => $this->getPendingApproval(),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'mrr' => $this->getMonthlyRecurringRevenue(),
                'sales_chart' => $this->getSalesChart(7),
                'top_products' => $this->getTopProducts(10),
                'recent_orders' => $this->getRecentOrders(10),
                'stock_alerts' => $this->getStockAlerts(),
                'sales_by_origin' => $this->getSalesByOrigin(30),
                'sales_by_type' => $this->getSalesByProductType(30),
                'recent_stock_movements' => $this->getRecentStockMovements(10),
                'order_conversion_rate' => $this->getOrderConversionRate(30),
                'average_ticket' => $this->getAverageTicket(30),
            ];
        });
    }

    /**
     * Vendas de hoje vs ontem
     * CAMPO CORRETO: total (NÃO total_amount)
     */
    public function getTodaySales(): array
    {
        // CORREÇÃO: Campo 'total' não 'total_amount'
        $today = Order::whereDate('created_at', today())
            ->whereNotIn('status', ['cancelled', 'draft', 'pending'])
            ->sum('total');

        $yesterday = Order::whereDate('created_at', now()->subDay())
            ->whereNotIn('status', ['cancelled', 'draft', 'pending'])
            ->sum('total');

        $percentage = $yesterday > 0 
            ? round((($today - $yesterday) / $yesterday) * 100, 2)
            : 0;

        return [
            'today' => $today,
            'yesterday' => $yesterday,
            'trend' => $today >= $yesterday ? 'up' : 'down',
            'percentage' => abs($percentage),
        ];
    }

    /**
     * Pedidos de hoje vs ontem
     */
    public function getTodayOrders(): array
    {
        $today = Order::whereDate('created_at', today())->count();
        $yesterday = Order::whereDate('created_at', now()->subDay())->count();

        $percentage = $yesterday > 0 
            ? round((($today - $yesterday) / $yesterday) * 100, 2)
            : 0;

        return [
            'today' => $today,
            'yesterday' => $yesterday,
            'trend' => $today >= $yesterday ? 'up' : 'down',
            'percentage' => abs($percentage),
        ];
    }

    /**
     * Total de filhos ativos
     */
    public function getActiveFilhos(): int
    {
        return Cache::remember('dashboard.active_filhos', 3600, function () {
            return Filho::where('status', 'active')->count();
        });
    }

    /**
     * Faturas vencidas
     */
    public function getOverdueInvoices(): array
    {
        $count = Invoice::where('status', 'overdue')->count();
        
        // CORREÇÃO: Campo 'total_amount' está correto em invoices
        $amount = Invoice::where('status', 'overdue')->sum('total_amount');

        return [
            'count' => $count,
            'amount' => $amount,
        ];
    }

    /**
     * Filhos aguardando aprovação
     */
    public function getPendingApproval(): int
    {
        return Filho::where('status', 'pending')->count();
    }

    /**
     * Receita mensal (mês atual vs anterior)
     * CAMPO CORRETO: total (NÃO total_amount)
     */
    public function getMonthlyRevenue(): array
    {
        $thisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotIn('status', ['cancelled', 'draft', 'pending'])
            ->sum('total');

        $lastMonth = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereNotIn('status', ['cancelled', 'draft', 'pending'])
            ->sum('total');

        $percentage = $lastMonth > 0 
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 0;

        return [
            'current' => $thisMonth,
            'previous' => $lastMonth,
            'trend' => $thisMonth >= $lastMonth ? 'up' : 'down',
            'percentage' => abs($percentage),
        ];
    }

    /**
     * MRR - Monthly Recurring Revenue
     */
    public function getMonthlyRecurringRevenue(): float
    {
        return Cache::remember('dashboard.mrr', 21600, function () {
            return Subscription::where('status', 'active')->sum('amount');
        });
    }

    /**
     * Gráfico de vendas (últimos N dias)
     * CAMPO CORRETO: total (NÃO total_amount)
     */
    public function getSalesChart(int $days = 7): array
    {
        $data = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => date('d/m', strtotime($d)))->toArray(),
            'values' => $data->pluck('total')->toArray(),
        ];
    }

    /**
     * Top produtos mais vendidos (últimos 30 dias)
     */
    public function getTopProducts(int $limit = 10): Collection
    {
        return Cache::remember("dashboard.top_products.{$limit}", 3600, function () use ($limit) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->whereNotIn('orders.status', ['cancelled'])
                ->select(
                    'products.id',
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(order_items.quantity) as quantity_sold'),
                    DB::raw('SUM(order_items.total) as revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('quantity_sold')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Pedidos recentes
     */
    public function getRecentOrders(int $limit = 10): Collection
    {
        return Order::with(['filho.user', 'items'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_type === 'filho' 
                        ? $order->filho?->user?->name ?? 'N/A'
                        : $order->guest_name ?? 'Visitante',
                    'total' => $order->total,
                    'status' => $order->status,
                    'origin' => $order->origin ?? $order->source ?? 'pdv',
                    'created_at' => $order->created_at,
                ];
            });
    }

    /**
     * Alertas de estoque (produtos abaixo do mínimo)
     */
    public function getStockAlerts(): Collection
    {
        return Product::where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->orderBy('stock_quantity')
            ->get()
            ->map(function ($product) {
                $percentage = $product->min_stock_alert > 0 
                    ? ($product->stock_quantity / $product->min_stock_alert) * 100
                    : 0;

                return [
                    'product' => $product,
                    'severity' => match(true) {
                        $percentage <= 0 => 'critical',
                        $percentage <= 25 => 'high',
                        $percentage <= 50 => 'medium',
                        default => 'low',
                    },
                ];
            });
    }

    /**
     * Vendas por origem (PDV, Totem, App)
     * CAMPO CORRETO: origin OU source (validando ambos)
     */
    public function getSalesByOrigin(int $days = 30): array
    {
        // Orders migration usa 'origin' (pdv, totem, app)
        // Alguns chats mostram 'source' - vamos tentar ambos
        $originField = DB::getSchemaBuilder()->hasColumn('orders', 'origin') ? 'origin' : 'source';
        
        return Order::select(
                $originField,
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->groupBy($originField)
            ->get()
            ->mapWithKeys(function ($item) use ($originField) {
                return [
                    $item->$originField => [
                        'count' => $item->count,
                        'total' => $item->total,
                    ]
                ];
            })
            ->toArray();
    }

    /**
     * Vendas por tipo de produto (loja vs cantina)
     */
    public function getSalesByProductType(int $days = 30): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->whereNotIn('orders.status', ['cancelled', 'draft'])
            ->select(
                'products.type',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->groupBy('products.type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->type ?? 'loja' => [
                        'orders' => $item->order_count,
                        'revenue' => $item->revenue,
                    ]
                ];
            })
            ->toArray();
    }

    /**
     * Movimentações recentes de estoque
     */
    public function getRecentStockMovements(int $limit = 10): Collection
    {
        return StockMovement::with(['product', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Taxa de conversão de pedidos
     */
    public function getOrderConversionRate(int $days = 30): array
    {
        $total = Order::where('created_at', '>=', now()->subDays($days))->count();
        
        $completed = Order::where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['delivered', 'paid'])
            ->count();
        
        $cancelled = Order::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'cancelled')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'conversion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Ticket médio
     * CAMPO CORRETO: total (NÃO total_amount)
     */
    public function getAverageTicket(int $days = 30): array
    {
        $orders = Order::where('created_at', '>=', now()->subDays($days))
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->select(
                DB::raw('AVG(total) as average'),
                DB::raw('MIN(total) as minimum'),
                DB::raw('MAX(total) as maximum')
            )
            ->first();

        return [
            'average' => $orders->average ?? 0,
            'minimum' => $orders->minimum ?? 0,
            'maximum' => $orders->maximum ?? 0,
        ];
    }

    /**
     * Limpar cache do dashboard
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard.metrics');
        Cache::forget('dashboard.active_filhos');
        Cache::forget('dashboard.mrr');
        
        // Limpar cache de top products de todos os limits
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget("dashboard.top_products.{$i}");
        }
    }

    /**
     * Refresh das métricas (limpar cache e buscar novamente)
     */
    public function refreshMetrics(): array
    {
        $this->clearCache();
        return $this->getMetrics();
    }
}