<?php
// app/Http/Controllers/Api/V1/App/InvoiceController.php - REFATORADO

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use App\Services\PaymentService;
use App\Services\PDFService;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private SubscriptionService $subscriptionService,
        private PaymentService $paymentService,
        private PDFService $pdfService,
    ) {}

    /**
     * Resumo financeiro completo
     * 
     * GET /api/v1/app/invoices/summary
     */
    public function summary(): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        $summary = [
            'total_pending' => $filho->invoices()
                ->where('status', 'pending')
                ->sum(DB::raw('total_amount - paid_amount')),
            
            'total_paid' => $filho->invoices()
                ->where('status', 'paid')
                ->sum('paid_amount'),
            
            'total_overdue' => $filho->invoices()
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->sum(DB::raw('total_amount - paid_amount')),
            
            'total_paid_this_month' => $filho->invoices()
                ->where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('paid_amount'),
            
            'pending_invoices_count' => $filho->invoices()
                ->where('status', 'pending')
                ->count(),
            
            'overdue_invoices_count' => $filho->invoices()
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Listar faturas de consumo
     * 
     * GET /api/v1/app/invoices/consumption
     */
    public function consumption(Request $request): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        $query = $filho->invoices()
            ->where('type', 'consumption')
            ->with(['items'])
            ->orderByDesc('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('period_start', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('period_end', '<=', $request->to);
        }

        $invoices = $query->get();

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Listar faturas de assinatura
     * 
     * GET /api/v1/app/invoices/subscription
     */
    public function subscription(Request $request): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        $query = $filho->invoices()
            ->where('type', 'subscription')
            ->with(['subscription'])
            ->orderByDesc('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->get();

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Detalhes de fatura de consumo
     * 
     * GET /api/v1/app/invoices/consumption/{invoice}
     */
    public function showConsumption(Invoice $invoice): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        if ($invoice->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada',
            ], 404);
        }
        
        $invoice->load(['items.product', 'payments', 'filho']);
        
        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Detalhes de fatura de assinatura
     * 
     * GET /api/v1/app/invoices/subscription/{invoice}
     */
    public function showSubscription(Invoice $invoice): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        if ($invoice->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada',
            ], 404);
        }
        
        $invoice->load(['subscription', 'items', 'filho']);
        
        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * NOVO: Gerar link/QR Code de pagamento PIX
     * 
     * POST /api/v1/app/invoices/{invoice}/payment-link
     */
    public function generatePaymentLink(Request $request, Invoice $invoice): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        if ($invoice->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada',
            ], 404);
        }
        
        if ($invoice->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura já está paga',
            ], 400);
        }
        
        // Gerar código PIX (integraria com gateway real em produção)
        $pixCode = $this->paymentService->generatePixCode([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->remaining_amount,
            'payer' => [
                'name' => $filho->name,
                'cpf' => $filho->cpf,
            ],
        ]);
        
        // Gerar QR Code em base64
        $qrCodeBase64 = base64_encode(QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->generate($pixCode));
        
        return response()->json([
            'success' => true,
            'data' => [
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->remaining_amount,
                'pix_key' => config('payment.pix_key'),
                'pix_receiver' => config('payment.pix_receiver', 'Casa Lar'),
                'pix_code' => $pixCode,
                'qr_code_base64' => $qrCodeBase64,
                'instructions' => 'Após o pagamento, a confirmação pode levar até 24 horas úteis.',
                'expiry_date' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }

    /**
     * NOVO: Confirmar pagamento manual
     * 
     * POST /api/v1/app/invoices/{invoice}/confirm-payment
     */
    public function confirmPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        if ($invoice->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada',
            ], 404);
        }
        
        if ($invoice->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura já está paga',
            ], 400);
        }
        
        // Registrar confirmação pendente
        $this->paymentService->registerPendingConfirmation($invoice, [
            'confirmed_by_user_at' => now(),
            'user_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Confirmação registrada. Aguardando processamento.',
            'data' => [
                'invoice_id' => $invoice->id,
                'status' => 'pending_confirmation',
                'estimated_processing_time' => '24 horas',
            ],
        ]);
    }

    /**
     * NOVO: Baixar fatura em PDF
     * 
     * GET /api/v1/app/invoices/{invoice}/download-pdf
     */
    public function downloadPDF(Invoice $invoice): mixed
    {
        $filho = auth()->user()->filho;
        if ($invoice->filho_id !== $filho->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $invoice->load(['items', 'filho']);
        $data = ['invoice' => $invoice];

        // Geramos o binário do PDF
        $pdf = Pdf::loadView('pdfs.invoice', $data)
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        // Retornamos como uma resposta de conteúdo binário pura
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="fatura.pdf"');
    }

    /**
     * NOVO: Compartilhar fatura (gerar link temporário)
     * 
     * POST /api/v1/app/invoices/{invoice}/share
     */
    public function shareInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        if ($invoice->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada',
            ], 404);
        }
        
        $request->validate([
            'method' => 'required|in:whatsapp,email,link',
        ]);
        
        $shareToken = $this->invoiceService->generateShareToken($invoice);
        $shareUrl = route('invoice.public', ['token' => $shareToken]);
        
        $response = [
            'share_url' => $shareUrl,
            'token' => $shareToken,
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ];
        
        if ($request->method === 'whatsapp') {
            $text = "Fatura {$invoice->invoice_number}\n";
            $text .= "Valor: R$ " . number_format($invoice->total_amount, 2, ',', '.') . "\n";
            $text .= "Vencimento: " . $invoice->due_date->format('d/m/Y') . "\n";
            $text .= "Ver fatura: {$shareUrl}";
            
            $response['whatsapp_url'] = "https://wa.me/?text=" . urlencode($text);
        }
        
        return response()->json([
            'success' => true,
            'data' => $response,
        ]);
    }

    /**
     * NOVO: Cancelar fatura (se permitido)
     * 
     * POST /api/v1/app/invoices/{invoice}/cancel
     */
    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        $filho = auth()->user()->filho;
        
        if ($invoice->filho_id !== $filho->id) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada',
            ], 404);
        }
        
        if ($invoice->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível cancelar fatura já paga',
            ], 400);
        }
        
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        $this->invoiceService->cancelInvoice($invoice, $request->reason);
        
        return response()->json([
            'success' => true,
            'message' => 'Solicitação de cancelamento registrada',
        ]);
    }
}