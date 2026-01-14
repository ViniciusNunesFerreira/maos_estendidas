<?php
// app/Services/Report/ReportService.php

namespace App\Services\Report;

use App\Models\Filho;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getDashboardMetrics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $startDate = $startDate ?? today()->startOfMonth();
        $endDate = $endDate ?? now();

        return [
            'sales' => $this->getSalesMetrics($startDate, $endDate),
            'products' => $this->getProductMetrics(),
            'filhos' => $this->getFilhosMetrics(),
            'kitchen' => $this->getKitchenMetrics(),
        ];
    }

    private function getSalesMetrics(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        return [
            'total_revenue' => $orders->sum('total'),
            'total_orders' => $orders->count(),
            'average_ticket' => $orders->avg('total'),
            'by_origin' => $orders->select('origin', DB::raw('count(*) as total'))
                ->groupBy('origin')
                ->pluck('total', 'origin'),
            'by_day' => $orders->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as orders')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    private function getProductMetrics(): array
    {
        return [
            'total_products' => Product::active()->count(),
            'low_stock' => Product::lowStock()->count(),
            'top_selling' => DB::table('order_items')
                ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'product' => Product::find($item->product_id),
                    'quantity_sold' => $item->total_sold,
                ]),
        ];
    }

    private function getFilhosMetrics(): array
    {
        return [
            'total_active' => Filho::active()->count(),
            'low_balance' => Filho::withLowBalance()->count(),
            'total_balance' => Filho::active()->sum('balance'),
            'mensalidades_due' => Filho::mensalidadeDueToday()->count(),
        ];
    }

    private function getKitchenMetrics(): array
    {
        return [
            'pending_orders' => Order::pending()->count(),
            'preparing_orders' => Order::preparing()->count(),
            'ready_orders' => Order::ready()->count(),
            'average_preparation_time' => Order::whereNotNull('preparing_at')
                ->whereNotNull('ready_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (ready_at - preparing_at))/60) as avg_minutes')
                ->value('avg_minutes'),
        ];
    }

    public function getSalesReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::with(['items.product', 'filho', 'payment'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        return [
            'summary' => [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total'),
                'total_discount' => $orders->sum('discount'),
                'average_ticket' => $orders->avg('total'),
            ],
            'by_payment_method' => $orders->groupBy('payment.method')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ]),
            'by_origin' => $orders->groupBy('origin')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ]),
            'orders' => $orders,
        ];
    }
}