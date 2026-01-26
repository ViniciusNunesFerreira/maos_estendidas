<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Order;
use Livewire\Component;

class RecentOrders extends Component
{
    public array $orders = [];

    protected $listeners = [
        'refreshDashboard' => '$refresh',
        'echo:orders,OrderCreated' => '$refresh',
    ];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->orders = Order::query()
            ->with(['filho:id,user_id', 'filho.user:id,name,email', 'items'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->filho?->user->name ?? $order->guest_name ?? 'Visitante',
                'total' => $order->total,
                'status' => $order->status,
                'status_label' => $this->getStatusLabel($order->status),
                'status_color' => $this->getStatusColor($order->status),
                'origin' => $order->origin,
                'items_count' => $order->items->count(),
                'created_at' => $order->created_at->format('d/m H:i'),
                'time_ago' => $order->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendente',
            'confirmed' => 'Confirmado',
            'preparing' => 'Preparando',
            'ready' => 'Pronto',
            'completed' => 'Entregue',
            'cancelled' => 'Cancelado',
            default => $status,
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'preparing' => 'indigo',
            'ready' => 'green',
            'completed' => 'gray',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function render()
    {
        return view('livewire.admin.dashboard.recent-orders');
    }
}