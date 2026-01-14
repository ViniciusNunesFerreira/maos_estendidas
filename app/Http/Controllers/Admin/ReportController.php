<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Relatório de Vendas
     * GET /admin/reports/sales
     */
    public function sales(): View
    {
        return view('admin.reports.sales');
    }

    /**
     * Relatório de Produtos
     * GET /admin/reports/products
     */
    public function products(): View
    {
        return view('admin.reports.products');
    }

    /**
     * Relatório Financeiro
     * GET /admin/reports/financial
     */
    public function financial(): View
    {
        return view('admin.reports.financial');
    }

    /**
     * Exportar relatório
     * POST /admin/reports/export
     */
    public function export(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:sales,products,financial,inventory',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'format' => 'required|in:xlsx,csv,pdf',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            $filePath = $this->reportService->export(
                type: $validated['type'],
                startDate: $startDate,
                endDate: $endDate,
                format: $validated['format']
            );

            return response()->download(
                storage_path('app/public/' . $filePath)
            )->deleteFileAfterSend();

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao exportar relatório: ' . $e->getMessage());
        }
    }
}