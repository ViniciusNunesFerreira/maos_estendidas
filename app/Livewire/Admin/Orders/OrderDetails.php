<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class OrderDetails extends Component
{
    public Order $order;
    public string $cancelReason = '';
    public bool $showCancelModal = false;

    protected $listeners = ['refreshOrder' => '$refresh'];

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'filho.user',
            'items.product.category',
            'createdBy',
            'payments',
        ]);
    }

    public function updateStatus(string $newStatus): void
    {
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['delivered', 'cancelled'],
        ];

        if (!in_array($newStatus, $allowedTransitions[$this->order->status] ?? [])) {
            session()->flash('error', 'Transição de status não permitida.');
            return;
        }

        if ($newStatus === 'cancelled') {
            $this->showCancelModal = true;
            return;
        }

        $this->order->update(['status' => $newStatus]);
        
        // Emitir evento para KDS
        // event(new \App\Events\OrderStatusChanged($this->order));
        
        session()->flash('message', 'Pedido atualizado com sucesso!');
        $this->order->refresh();
    }

    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelReason = '';
    }

    public function confirmCancel(): void
    {
        $this->validate([
            'cancelReason' => 'required|string|min:5|max:500',
        ]);

        $orderService = app(OrderService::class);
        
        try {
            $orderService->cancelOrder(
                order: $this->order,
                reason: $this->cancelReason
            );

            session()->flash('message', 'Pedido cancelado com sucesso!');
            $this->closeCancelModal();
            $this->order->refresh();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao cancelar pedido: ' . $e->getMessage());
        }
    }

    public function printOrder(): void
    {
        $this->dispatch('printOrder', orderId: $this->order->id);
    }

    public function render()
    {
        $statusLabels = [
            'pending' => ['label' => 'Pendente', 'color' => 'yellow'],
            'confirmed' => ['label' => 'Confirmado', 'color' => 'blue'],
            'delivered' => ['label' => 'Entregue', 'color' => 'green'],
            'preparing' => ['label' => 'Preparando', 'color' => 'indigo'],
            'ready' => ['label' => 'Encomendado', 'color' => 'blue'],
            'completed' => ['label' => 'Entregue', 'color' => 'green'],
            'paid' => ['label' => 'Pago', 'color' => 'green'],
            'cancelled' => ['label' => 'Cancelado', 'color' => 'red'],
        ];

        $nextActions = [
            'pending' => ['confirmed' => 'Confirmar', 'cancelled' => 'Cancelar'],
            'confirmed' => ['preparing' => 'Iniciar Preparo', 'cancelled' => 'Cancelar'],
            'preparing' => ['ready' => 'Marcar Pronto', 'cancelled' => 'Cancelar'],
            'ready' => ['delivered' => 'Marcar Entregue', 'cancelled' => 'Cancelar'],
        ];

        return view('livewire.admin.orders.order-details', [
            'statusLabels' => $statusLabels,
            'nextActions' => $nextActions[$this->order->status] ?? [],
        ]);
    }
}