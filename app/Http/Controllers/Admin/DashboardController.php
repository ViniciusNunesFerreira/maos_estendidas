<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}
    
    public function index(): View
    {
        $metrics = $this->dashboardService->getMetrics();
        
        return view('admin.dashboard.index', [
            // Métricas principais
            'todaySales' => $metrics['today_sales'],
            'todayOrders' => $metrics['today_orders'],
            'activeFilhos' => $metrics['active_filhos'],
            'overdueInvoices' => $metrics['overdue_invoices'],
            // Gráficos
            'salesChart' => $metrics['sales_chart'], // 7 dias
            'topProducts' => $metrics['top_products'], // Top 10
            'recentOrders' => $metrics['recent_orders'], // Últimos 10
            'stockAlerts' => $metrics['stock_alerts'], // Produtos baixos
        ]);
    }
}