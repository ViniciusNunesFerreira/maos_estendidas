<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Order;
use App\Services\ReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Carbon\Carbon;

class SalesReport extends Component
{
    // Filtros e Datas
    public string $startDate;
    public string $endDate;
    public string $groupBy = 'day'; 
    public string $originFilter = 'all'; 

    // Propriedades de Dados
    public array $summary = [];
    public array $chartData = [];
    public array $byOrigin = [];
    public array $byPaymentMethod = [];
    public array $topProducts = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'groupBy', 'originFilter'])) {
            $this->loadData();
        }
    }

    public function loadData(): void
    {
        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $salesStatuses = ['paid', 'preparing', 'ready', 'delivered'];

            // 1. Query Base
            $query = Order::query()
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', $salesStatuses);

            if ($this->originFilter !== 'all') {
                $query->where(function($q) {
                    $q->where('origin', $this->originFilter)
                      ->orWhere('source', $this->originFilter);
                });
            }

            // 2. Resumo Otimizado (Summary)
            $stats = (clone $query)->selectRaw('
                SUM(total) as total_sales, 
                COUNT(*) as total_orders,
                COUNT(DISTINCT customer_id) as unique_customers
            ')->first();

            $this->summary = [
                'total_orders'     => (int) ($stats->total_orders ?? 0),
                'total_revenue'    => (float) ($stats->total_sales ?? 0),
                'average_ticket'   => $stats->total_orders > 0 ? ($stats->total_sales / $stats->total_orders) : 0,
                'unique_customers' => (int) ($stats->unique_customers ?? 0),
            ];

            // 3. Gráfico (Cross-Database: PGSQL/MySQL)
            $this->chartData = $this->getChartData(clone $query);

            // 4. Vendas por Origem
            $this->byOrigin = (clone $query)
                ->selectRaw("COALESCE(origin, 'desconhecido') as origin_name, COUNT(*) as orders_count, SUM(total) as total_amount")
                ->groupBy('origin_name')
                ->get()
                ->map(fn($r) => [
                    'origin' => $r->origin_name,
                    'orders' => (int) $r->orders_count,
                    'total'  => (float) $r->total_amount,
                ])->toArray();

            // 5. Métodos de Pagamento
            $this->byPaymentMethod = (clone $query)
                ->selectRaw("payment_method, COUNT(*) as count, SUM(total) as total")
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(fn($r) => [
                    $r->payment_method ?? 'outro' => [
                        'total' => (float) $r->total,
                        'count' => (int) $r->count
                    ]
                ])->toArray();

            // 6. Top Produtos
            $this->topProducts = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->whereBetween('orders.created_at', [$start, $end])
                ->whereIn('orders.status', $salesStatuses)
                ->whereNull('orders.deleted_at')
                ->select(
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.total) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get()->toArray();

        } catch (\Exception $e) {
            Log::error("Erro no Relatório: " . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Erro ao carregar dados.']);
        }
    }

    private function getChartData($query): array
    {
        $driver = DB::getDriverName();
        $format = match ($this->groupBy) {
            'day'   => ($driver === 'pgsql' ? 'YYYY-MM-DD' : '%Y-%m-%d'),
            'week'  => ($driver === 'pgsql' ? 'IYYY-IW'   : '%Y-%u'),
            'month' => ($driver === 'pgsql' ? 'YYYY-MM'   : '%Y-%m'),
            default => ($driver === 'pgsql' ? 'YYYY-MM-DD' : '%Y-%m-%d'),
        };

        $rawExpr = $driver === 'pgsql' 
            ? "TO_CHAR(created_at, '$format')" 
            : "DATE_FORMAT(created_at, '$format')";

        return $query
            ->selectRaw("$rawExpr as period, COUNT(*) as orders, SUM(total) as revenue")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.reports.sales-report');
    }
}