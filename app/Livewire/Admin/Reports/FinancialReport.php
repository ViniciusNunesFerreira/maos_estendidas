<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Order;
use App\Models\Invoice;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinancialReport extends Component
{
    // Filtros
    public string $dateFrom;
    public string $dateTo;
    public string $reportType = 'revenue'; // 'revenue', 'invoices'

    // KPI Cards
    public float $totalRevenue = 0.0;
    public int $paidInvoicesCount = 0;
    public float $pendingAmount = 0.0;
    public float $overdueAmount = 0.0;

    // Gráficos e Tabela
    public array $revenueByDay = [];
    public array $reportData = [];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->loadData();
    }

    public function updated($propertyName): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        try {
            $start = Carbon::parse($this->dateFrom)->startOfDay();
            $end = Carbon::parse($this->dateTo)->endOfDay();
            
            // 1. KPIs (Cards)
            $salesRevenue = Order::whereBetween('created_at', [$start, $end])
                ->whereIn('status', ['paid', 'preparing', 'ready', 'delivered'])
                ->sum('total');

            $invoicesPaidQuery = Invoice::whereBetween('paid_at', [$start, $end])
                ->where('status', 'paid');
            
            $this->paidInvoicesCount = $invoicesPaidQuery->count();
            $invoicesRevenue = $invoicesPaidQuery->sum('total_amount');

            $this->totalRevenue = (float) $salesRevenue + (float) $invoicesRevenue;

            // Pendentes e Vencidos (Snapshot atual, não depende da data do filtro geralmente, mas se quiser histórico, ajuste a query)
            $this->pendingAmount = (float) Invoice::whereIn('status', ['pending', 'partial'])->sum('total_amount');
            $this->overdueAmount = (float) Invoice::where('status', 'overdue')->sum('total_amount');

            // 2. Gráfico (Agrupamento Diário)
            $this->generateChartData($start, $end);

            // 3. Tabela Detalhada (Top 50 mais recentes)
            $this->loadTableData($start, $end);

        } catch (\Exception $e) {
            Log::error("Erro Financeiro: " . $e->getMessage());
        }
    }

    private function generateChartData($start, $end)
    {
        $driver = DB::getDriverName();
        // Compatibilidade SQL
        $dateFunc = $driver === 'pgsql' ? "TO_CHAR(created_at, 'YYYY-MM-DD')" : "DATE(created_at)";
        $paidDateFunc = $driver === 'pgsql' ? "TO_CHAR(paid_at, 'YYYY-MM-DD')" : "DATE(paid_at)";

        $salesByDay = Order::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'preparing', 'ready', 'delivered'])
            ->select(DB::raw("$dateFunc as day"), DB::raw('SUM(total) as amount'))
            ->groupBy('day')->pluck('amount', 'day')->toArray();

        $invoicesByDay = Invoice::whereBetween('paid_at', [$start, $end])
            ->where('status', 'paid')
            ->select(DB::raw("$paidDateFunc as day"), DB::raw('SUM(total_amount) as amount'))
            ->groupBy('day')->pluck('amount', 'day')->toArray();

        // Merge e Sort
        $days = array_unique(array_merge(array_keys($salesByDay), array_keys($invoicesByDay)));
        sort($days);

        $this->revenueByDay = [];
        foreach ($days as $day) {
            $this->revenueByDay[] = [
                'date' => Carbon::parse($day)->format('d/m'),
                'total' => (float) ($salesByDay[$day] ?? 0) + (float) ($invoicesByDay[$day] ?? 0)
            ];
        }
    }

    private function loadTableData($start, $end): void
    {
        // Une Vendas e Faturas em uma coleção única
        $sales = Order::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['paid', 'preparing', 'ready', 'delivered'])
            ->select(
                'created_at as date', 
                'order_number as ref',
                DB::raw("'Venda PDV/App' as description"), 
                'total as amount', 
                'status'
            )
            ->limit(100)->get();

        $invoices = Invoice::whereBetween('paid_at', [$start, $end])
            ->where('status', 'paid')
            ->select(
                'paid_at as date', 
                'invoice_number as ref',
                DB::raw("'Fatura Recebida' as description"), 
                'total_amount as amount', 
                'status'
            )
            ->limit(100)->get();

        $this->reportData = $sales->concat($invoices)
            ->sortByDesc('date')
            ->map(fn($item) => [
                'date' => Carbon::parse($item->date)->format('d/m/Y H:i'),
                'ref' => $item->ref ?? '-',
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'status' => ucfirst($item->status),
                'type_color' => $item->description === 'Venda PDV/App' ? 'text-blue-600' : 'text-purple-600'
            ])->toArray();
    }

    public function exportReport()
    {
        $fileName = 'Relatorio_Financeiro_' . now()->format('d-m-Y_H-i') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
            fputcsv($handle, ['Data', 'Referência', 'Descrição', 'Valor (R$)', 'Status'], ';');

            $start = Carbon::parse($this->dateFrom)->startOfDay();
            $end = Carbon::parse($this->dateTo)->endOfDay();

            // Exportação em Streaming para não travar memória
            
            // 1. Orders
            Order::whereBetween('created_at', [$start, $end])
                ->whereIn('status', ['paid', 'preparing', 'ready', 'delivered'])
                ->chunk(500, function($orders) use ($handle) {
                    foreach($orders as $order) {
                        fputcsv($handle, [
                            $order->created_at->format('d/m/Y H:i'),
                            $order->order_number,
                            'Venda PDV/App',
                            number_format($order->total, 2, ',', '.'),
                            $order->status
                        ], ';');
                    }
                });

            // 2. Invoices
            Invoice::whereBetween('paid_at', [$start, $end])
                ->where('status', 'open')
                ->chunk(500, function($invoices) use ($handle) {
                    foreach($invoices as $inv) {
                        fputcsv($handle, [
                            $inv->paid_at->format('d/m/Y H:i'),
                            $inv->invoice_number,
                            'Fatura Recebida',
                            number_format($inv->total_amount, 2, ',', '.'),
                            $inv->status
                        ], ';');
                    }
                });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.admin.reports.financial-report');
    }
}