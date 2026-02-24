<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use App\Models\Order;

class OrdersAppAlerts extends Component
{
    public array $orders = [];
    public int $totalOrdersAlerts = 0;

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount(): void
    {
        $this->loadOrders();
    }

    public function loadOrders(): void
    {
        $this->orders =  Order::query()
            ->where('origin', 'app')
            ->where('customer_type', 'filho')
            ->where('status', 'ready')
            ->select('id', 'order_number','customer_name', 'status', 'total', 'payment_method_chosen', 'is_invoiced', 'paid_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name, 
                'status' => $order->status, 
                'total' => $order->total, 
                'payment_method_chosen' => $order->payment_method_chosen, 
                'is_invoiced' => $order->is_invoiced, 
                'paid_at' => $order->paid_at
            ])
            ->toArray();
        
        $this->totalOrdersAlerts = count($this->orders);

    }

    public function render()
    {
        return view('livewire.admin.dashboard.orders-app-alerts');
    }
}
