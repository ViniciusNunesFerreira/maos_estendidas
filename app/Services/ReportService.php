<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Filho;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Relatório de Vendas
     * 
     * VALIDADO: Campos de orders conforme migration
     * - total (NÃO total_amount)
     * - status válidos conforme enum
     */
    public function salesReport(Carbon $startDate, Carbon $endDate): array
    {
        // Query base com filtro de datas
        $query = Order::query()
            ->whereBetween('created_at', [
                $startDate->startOfDay(), 
                $endDate->endOfDay()
            ]);

        // Status válidos conforme migration de orders
        // 'pending', 'paid', 'preparing', 'ready', 'delivered', 'cancelled'
        // Consideramos vendas: paid, preparing, ready, delivered
        $salesStatuses = ['paid', 'preparing', 'ready', 'delivered'];

        // Total de vendas (usando campo 'total', não 'total_amount')
        $totalSales = (clone $query)
            ->whereIn('status', $salesStatuses)
            ->sum('total'); // ✅ CAMPO CORRETO

        // Quantidade de pedidos
        $totalOrders = (clone $query)
            ->whereIn('status', $salesStatuses)
            ->count();

        // Ticket médio
        $averageTicket = $totalOrders > 0 
            ? $totalSales / $totalOrders 
            : 0;

        // Vendas por dia
        $salesByDay = (clone $query)
            ->whereIn('status', $salesStatuses)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as total_amount') // ✅ usando 'total'
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'orders' => (int) $item->orders_count,
                    'total' => (float) $item->total_amount,
                ];
            })
            ->toArray();

        // Vendas por origem (validar se campo existe)
        $originField = DB::getSchemaBuilder()->hasColumn('orders', 'origin') 
            ? 'origin' 
            : 'source';

        $salesByOrigin = (clone $query)
            ->whereIn('status', $salesStatuses)
            ->select(
                $originField . ' as origin',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as total_amount')
            )
            ->groupBy($originField)
            ->get()
            ->map(function ($item) {
                return [
                    'origin' => $item->origin ?? 'unknown',
                    'orders' => (int) $item->orders_count,
                    'total' => (float) $item->total_amount,
                ];
            })
            ->toArray();

        // Top produtos vendidos
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [
                $startDate->startOfDay(), 
                $endDate->endOfDay()
            ])
            ->whereIn('orders.status', $salesStatuses)
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total) as total_revenue') // ✅ order_items usa 'total'
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => (int) $item->total_quantity,
                    'revenue' => (float) $item->total_revenue,
                ];
            })
            ->toArray();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_sales' => (float) $totalSales,
                'total_orders' => (int) $totalOrders,
                'average_ticket' => (float) $averageTicket,
            ],
            'sales_by_day' => $salesByDay,
            'sales_by_origin' => $salesByOrigin,
            'top_products' => $topProducts,
        ];
    }

    /**
     * Relatório de Produtos
     * 
     * VALIDADO: Campos de products conforme migration
     */
    public function productsReport(Carbon $startDate, Carbon $endDate): array
    {
        // Top produtos vendidos
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [
                $startDate->startOfDay(), 
                $endDate->endOfDay()
            ])
            ->whereIn('orders.status', ['paid', 'preparing', 'ready', 'delivered'])
            ->select(
                'products.name',
                'products.sku',
                'products.price', // ✅ campo correto
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'price' => (float) $item->price,
                    'quantity_sold' => (int) $item->total_sold,
                    'revenue' => (float) $item->total_revenue,
                ];
            })
            ->toArray();

        // Produtos com estoque baixo
        // VALIDADO: stock_quantity e min_stock_alert existem
        $lowStockProducts = Product::query()
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->where('is_active', true)
            ->select('name', 'sku', 'stock_quantity', 'min_stock_alert')
            ->orderBy('stock_quantity')
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => (int) $product->stock_quantity,
                    'min_stock' => (int) $product->min_stock_alert,
                ];
            })
            ->toArray();

        // Produtos sem vendas no período
        $productsWithSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [
                $startDate->startOfDay(), 
                $endDate->endOfDay()
            ])
            ->whereIn('orders.status', ['paid', 'preparing', 'ready', 'delivered'])
            ->distinct()
            ->pluck('order_items.product_id');

        $noSalesProducts = Product::query()
            ->whereNotIn('id', $productsWithSales)
            ->where('is_active', true)
            ->select('name', 'sku', 'stock_quantity')
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'stock' => (int) $product->stock_quantity,
                ];
            })
            ->toArray();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'top_products' => $topProducts,
            'low_stock' => $lowStockProducts,
            'no_sales' => $noSalesProducts,
        ];
    }

    /**
     * Relatório Financeiro
     * 
     * VALIDADO: Campos de invoices e subscriptions
     */
    public function financialReport(Carbon $startDate, Carbon $endDate): array
    {
        // Receita de vendas (orders)
        $salesRevenue = Order::query()
            ->whereBetween('created_at', [
                $startDate->startOfDay(), 
                $endDate->endOfDay()
            ])
            ->whereIn('status', ['paid', 'preparing', 'ready', 'delivered'])
            ->sum('total'); // ✅ campo correto

        // Faturas pagas no período
        // VALIDADO: invoices usa 'total_amount' e 'paid_amount'
        $invoicesPaid = Invoice::query()
            ->whereBetween('paid_at', [
                $startDate->startOfDay(), 
                $endDate->endOfDay()
            ])
            ->where('status', 'paid')
            ->sum('total_amount'); // ✅ campo correto

        // Faturas pendentes (todas, não apenas do período)
        // Status válidos: 'draft', 'open', 'partial', 'paid', 'overdue', 'cancelled'
        $invoicesPending = Invoice::query()
            ->where('status', 'open') // ✅ status correto (não 'pending')
            ->sum('total_amount');

        // Faturas vencidas
        $invoicesOverdue = Invoice::query()
            ->where('status', 'overdue')
            ->sum('total_amount');

        // MRR (Monthly Recurring Revenue)
        // VALIDADO: subscriptions usa 'amount' (não 'total_amount')
        $mrr = Subscription::query()
            ->where('status', 'active')
            ->sum('amount'); // ✅ campo correto

        // Receita total
        $totalRevenue = $salesRevenue + $invoicesPaid;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'sales_revenue' => (float) $salesRevenue,
                'invoices_paid' => (float) $invoicesPaid,
                'total_revenue' => (float) $totalRevenue,
                'mrr' => (float) $mrr,
            ],
            'invoices' => [
                'pending' => (float) $invoicesPending,
                'overdue' => (float) $invoicesOverdue,
            ],
        ];
    }

    /**
     * Exportar relatório
     * 
     * TODO: Implementar exportação para Excel/PDF
     */
    public function export(
        string $type,
        Carbon $startDate,
        Carbon $endDate,
        string $format = 'xlsx'
    ): string {
        // Gerar dados do relatório
        $data = match($type) {
            'sales' => $this->salesReport($startDate, $endDate),
            'products' => $this->productsReport($startDate, $endDate),
            'financial' => $this->financialReport($startDate, $endDate),
            default => throw new \InvalidArgumentException("Tipo inválido: {$type}")
        };

        // Nome do arquivo
        $filename = "relatorio_{$type}_" . now()->format('Y-m-d_His') . ".{$format}";

        // TODO: Implementar geração de arquivo
        // Por enquanto, retornar nome do arquivo
        return $filename;
    }
}