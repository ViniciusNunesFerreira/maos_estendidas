<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Filho;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
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
            'operator',
            'payments',
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
            'status' => 'required|in:pending,confirmed,preparing,ready,completed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $previousStatus = $order->status;
        $newStatus = $request->status;

        // Validar transição
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (!in_array($newStatus, $allowedTransitions[$previousStatus] ?? [])) {
            return redirect()
                ->back()
                ->with('error', "Transição de '{$previousStatus}' para '{$newStatus}' não permitida.");
        }

        try {
            $order->update([
                'status' => $newStatus,
                'notes' => $request->notes ?? $order->notes,
            ]);

            // Se cancelado, estornar crédito
            if ($newStatus === 'cancelled' && $order->filho_id) {
                $this->orderService->cancelOrder($order, auth()->user());
            }

            // Emitir evento para KDS
            event(new \App\Events\OrderStatusChanged($order));

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
            $this->orderService->cancelOrder($order, auth()->user(), $request->reason);

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
     * Imprimir pedido
     */
    public function print(Order $order): View
    {
        $order->load(['filho', 'items.product', 'operator']);

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