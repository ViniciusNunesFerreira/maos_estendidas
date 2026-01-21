<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Filho;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Listar pedidos
     */
    public function index(): View
    {
        $stats = [
            'today' => [
                'count' => Order::whereDate('created_at', today())->count(),
                'total' => Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total'),
            ],
            'pending' => Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'completed_today' => Order::whereDate('created_at', today())->where('status', 'completed')->count(),
        ];

        return view('admin.orders.index', compact('stats'));
    }

    /**
     * Exibir detalhes do pedido
     */
    public function show(Order $order): View
    {
        $order->load([
            'filho.user',
            'items.product.category',
            'createdBy',
            'payment',
            'invoice',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Atualizar status do pedido
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,completed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->orderService->updateStatus($order, $request->status);
            
            if ($request->notes) {
                $order->update(['notes' => $request->notes]);
            }

            // Se cancelado, estornar crédito
            if ($request->status === 'cancelled' && $order->filho_id) {
                $this->orderService->cancelOrder($order, 'Cancelado pelo administrador');
            }

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Status atualizado com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar pedido
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (in_array($order->status, ['completed', 'cancelled'])) {
            return redirect()
                ->back()
                ->with('error', 'Este pedido não pode ser cancelado.');
        }

        try {
            $this->orderService->cancelOrder($order, $request->reason);

            return redirect()
                ->route('admin.orders.index')
                ->with('success', 'Pedido cancelado com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao cancelar pedido: ' . $e->getMessage());
        }
    }

    /**
     * Confirmar pagamento manualmente (Admin)
     * POST /admin/orders/{order}/confirm-payment
     */
    public function confirmPayment(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|in:balance,pix,credit_card,debit_card,cash,other',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Verificar se já está pago
            if ($order->payment_status === 'paid') {
                return redirect()
                    ->back()
                    ->with('error', 'Pedido já foi pago.');
            }

            // Criar Payment manual
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'amount' => $request->amount,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'notes' => $request->notes,
                'confirmed_by_admin' => true,
                'confirmed_by_user_id' => auth()->id(),
            ]);

            // Atualizar Order
            $order->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            // Se for filho e método balance, debitar crédito
            if ($request->payment_method === 'balance' && $order->filho_id) {
                $filho = $order->filho;
                $filho->credit_used += $request->amount;
                $filho->save();
            }

            \Log::info('Pagamento confirmado manualmente pelo admin', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'admin_id' => auth()->id(),
                'method' => $request->payment_method,
                'amount' => $request->amount,
            ]);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Pagamento confirmado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao confirmar pagamento manual', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erro ao confirmar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Corrigir valores do pedido (Admin)
     * POST /admin/orders/{order}/adjust-values
     */
    public function adjustValues(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        try {
            // Verificar se pode ajustar
            if ($order->status === 'completed' || $order->payment_status === 'paid') {
                return redirect()
                    ->back()
                    ->with('error', 'Não é possível ajustar valores de pedido pago ou concluído.');
            }

            $oldTotal = $order->total;
            
            $order->update([
                'subtotal' => $request->subtotal,
                'discount_amount' => $request->discount_amount ?? 0,
                'total' => $request->total,
                'notes' => ($order->notes ?? '') . "\n\nAjuste de valores: {$request->reason}",
            ]);

            \Log::info('Valores do pedido ajustados pelo admin', [
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'old_total' => $oldTotal,
                'new_total' => $request->total,
                'reason' => $request->reason,
            ]);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Valores ajustados com sucesso!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao ajustar valores: ' . $e->getMessage());
        }
    }

    /**
     * Imprimir pedido
     */
    public function print(Order $order): View
    {
        $order->load(['filho', 'items.product', 'createdBy']);

        return view('admin.orders.print', compact('order'));
    }

    /**
     * Pedidos por filho
     */
    public function byFilho(Filho $filho): View
    {
        return view('admin.orders.by-filho', compact('filho'));
    }
}