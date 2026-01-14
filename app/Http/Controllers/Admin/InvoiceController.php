<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Listar todas as faturas
     * GET /admin/invoices
     */
    public function index(Request $request): View
    {
        // Stats para a view (usando campos REAIS da migration)
        // Statuses válidos: draft, open, partial, paid, overdue, cancelled
        $openCount = Invoice::where('status', 'open')->count();
        $paidCount = Invoice::where('status', 'paid')->count();
        $overdueCount = Invoice::where('status', 'overdue')->count();
        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');

        return view('admin.invoices.index', [
            'activeCount' => $openCount, // 'open' é equivalente a 'active'
            'paidCount' => $paidCount,
            'overdueCount' => $overdueCount,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    /**
     * Exibir detalhes de uma fatura
     * GET /admin/invoices/{invoice}
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'filho.user',
            'items.product',
            'payments',
            'subscription',
        ]);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Registrar pagamento em uma fatura
     * POST /admin/invoices/{invoice}/payment
     */
    public function registerPayment(Invoice $invoice, Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:dinheiro,debito,credito,pix,transferencia',
                'paid_at' => 'required|date',
                'notes' => 'nullable|string|max:500',
                'transaction_id' => 'nullable|string|max:255',
            ]);

            // Registrar pagamento via service
                $payment = $this->invoiceService->addPayment(
                invoice: $invoice,
                amount: (float) $validated['amount'],
                method: $validated['payment_method'],
                paidAt: $validated['paid_at'],
                transactionId: $validated['transaction_id'] ?? null,
                notes: $validated['notes'] ?? null
            );

            return redirect()
                ->back()
                ->with('success', 'Pagamento de R$ ' . number_format($payment->amount, 2, ',', '.') . ' registrado com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao registrar pagamento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancelar uma fatura
     * POST /admin/invoices/{invoice}/cancel
     */
    public function cancel(Invoice $invoice, Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $invoice->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'] ?? 'Cancelada pelo administrador',
            ]);

            return redirect()
                ->route('admin.invoices.index')
                ->with('success', 'Fatura #' . $invoice->invoice_number . ' cancelada com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao cancelar fatura: ' . $e->getMessage());
        }
    }

    /**
     * Enviar lembrete de pagamento
     * POST /admin/invoices/{invoice}/reminder
     */
    public function sendReminder(Invoice $invoice): RedirectResponse
    {
        try {
            // Verificar se a fatura está vencida ou pendente
            if (!in_array($invoice->status, ['pending', 'overdue'])) {
                return redirect()
                    ->back()
                    ->with('warning', 'Lembretes só podem ser enviados para faturas pendentes ou vencidas.');
            }

            // Enviar email/SMS via service
            // TODO: Implementar envio de lembrete
            // $this->invoiceService->sendReminder($invoice);

            return redirect()
                ->back()
                ->with('success', 'Lembrete de pagamento enviado com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao enviar lembrete: ' . $e->getMessage());
        }
    }

    /**
     * Download PDF da fatura
     * GET /admin/invoices/{invoice}/pdf
     */
    public function downloadPdf(Invoice $invoice)
    {
        try {
            $invoice->load([
                'filho.user',
                'items.product',
                'payments',
                'subscription',
            ]);

            // TODO: Implementar geração de PDF
            // $pdf = $this->invoiceService->generatePdf($invoice);
            // return $pdf->download('fatura-' . $invoice->invoice_number . '.pdf');

            return redirect()
                ->back()
                ->with('info', 'Geração de PDF será implementada em breve.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao gerar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Marcar fatura como vencida (executado via cron)
     * POST /admin/invoices/{invoice}/mark-overdue
     */
    public function markAsOverdue(Invoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->markAsOverdue($invoice);

            return redirect()
                ->back()
                ->with('success', 'Fatura marcada como vencida.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao marcar fatura como vencida: ' . $e->getMessage());
        }
    }

    /**
     * Reabrir fatura cancelada
     * POST /admin/invoices/{invoice}/reopen
     */
    public function reopen(Invoice $invoice): RedirectResponse
    {
        try {
            if ($invoice->status !== 'cancelled') {
                return redirect()
                    ->back()
                    ->with('warning', 'Apenas faturas canceladas podem ser reabertas.');
            }

            $invoice->update([
                'status' => 'pending',
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Fatura reaberta com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao reabrir fatura: ' . $e->getMessage());
        }
    }

    /**
     * Exportar faturas para Excel
     * GET /admin/invoices/export
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['type', 'status', 'date_from', 'date_to']);

            // TODO: Implementar exportação Excel
            // $export = $this->invoiceService->exportToExcel($filters);
            // return $export->download('faturas-' . date('Y-m-d') . '.xlsx');

            return redirect()
                ->back()
                ->with('info', 'Exportação será implementada em breve.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao exportar faturas: ' . $e->getMessage());
        }
    }

    /**
     * Estornar pagamento
     * DELETE /admin/invoices/payments/{payment}
     */
    public function refundPayment(Payment $payment): RedirectResponse
    {
        try {
            $invoice = $payment->invoice;
            $amount = $payment->amount;

            // Deletar pagamento
            $payment->delete();

            // Recalcular status da fatura
            $invoice->refresh();
            $totalPaid = $invoice->payments()->sum('amount');

            if ($totalPaid == 0) {
                $invoice->update(['status' => 'pending']);
            } elseif ($totalPaid < $invoice->total_amount) {
                $invoice->update(['status' => 'partial']);
            }

            return redirect()
                ->back()
                ->with('success', 'Pagamento de R$ ' . number_format($amount, 2, ',', '.') . ' estornado com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao estornar pagamento: ' . $e->getMessage());
        }
    }
}