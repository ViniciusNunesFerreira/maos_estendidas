<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductsReport extends Component
{
    // Filtros
    public string $startDate;
    public string $endDate;
    public ?string $categoryFilter = null;
    public string $reportType = 'sales'; // sales, revenue, stock

    // Métricas
    public int $totalSold = 0;
    public float $totalRevenue = 0.0;
    public int $activeProducts = 0;
    public int $lowStockProducts = 0;

    // Dados
    public array $products = [];
    public array $byCategory = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function updated($propertyName): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $salesStatuses = ['paid', 'preparing', 'ready', 'delivered'];

            // 1. Métricas Rápidas
            $this->activeProducts = Product::where('is_active', true)->count();
            
            $this->lowStockProducts = Product::where('is_active', true)
                ->where('track_stock', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
                ->count();

            // Totais consolidados
            $stats = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$start, $end])
                ->whereIn('orders.status', $salesStatuses)
                ->selectRaw('SUM(order_items.quantity) as qty, SUM(order_items.total) as rev')
                ->first();

            $this->totalSold = (int) ($stats->qty ?? 0);
            $this->totalRevenue = (float) ($stats->rev ?? 0);

            // 2. Consulta Principal (Base para Tabela e Exportação)
            $query = $this->buildMainQuery($start, $end, $salesStatuses);

            // Paginação/Limite para exibição em tela (Performance UI)
            $results = $query->limit(50)->get();

            // CORREÇÃO CRÍTICA: Converter stdClass para Array
            $this->products = $results->map(function($item) {
                return (array) $item;
            })->toArray();

        } catch (\Exception $e) {
            Log::error("Erro no ProductsReport: " . $e->getMessage());
        }
    }

    /**
     * Constrói a query principal para reutilização na tela e exportação
     */
    private function buildMainQuery($start, $end, $salesStatuses)
    {
        $query = DB::table('products')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function($join) use ($start, $end, $salesStatuses) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$start, $end])
                    ->whereIn('orders.status', $salesStatuses);
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.image_url',
                'products.stock_quantity',
                'products.min_stock_alert',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as total_revenue')
            )
            ->where('products.is_active', true)
            ->groupBy(
                'products.id', 
                'products.name', 
                'products.sku',
                'products.image_url', 
                'products.stock_quantity', 
                'products.min_stock_alert'
            );

        if ($this->categoryFilter) {
            $query->where('products.category_id', $this->categoryFilter);
        }

        return match ($this->reportType) {
            'revenue' => $query->orderByDesc('total_revenue'),
            'stock'   => $query->orderBy('products.stock_quantity'),
            default   => $query->orderByDesc('total_sold'),
        };
    }

    public function exportReport()
    {
        $fileName = 'Relatorio_Produtos_' . now()->format('d-m-Y_H-i') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            
            // BOM para Excel reconhecer acentos UTF-8 corretamente
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeçalho
            fputcsv($handle, ['Produto', 'SKU', 'Qtd Vendida', 'Receita (R$)', 'Estoque Atual', 'Estoque Mínimo'], ';');

            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $salesStatuses = ['paid', 'preparing', 'ready', 'delivered'];

            // Query sem limite para exportação total
            $this->buildMainQuery($start, $end, $salesStatuses)
                ->chunk(1000, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->name,
                            $row->sku ?? '-',
                            $row->total_sold,
                            number_format($row->total_revenue, 2, ',', '.'),
                            $row->stock_quantity,
                            $row->min_stock_alert
                        ], ';');
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.reports.products-report', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get()
        ]);
    }
}