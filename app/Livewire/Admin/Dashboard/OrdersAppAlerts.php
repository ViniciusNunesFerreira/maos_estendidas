<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Collection;

class OrdersAppAlerts extends Component
{


    public function getOrdersProperty(): Collection
    {
        return Order::query()
            ->where('origin', 'app')
            ->where('customer_type', 'filho')
            ->where('status', 'ready')
            ->select('id', 'order_number', 'customer_name', 'status', 'total', 'payment_method_chosen', 'is_invoiced', 'paid_at', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function getTotalOrdersAlertsProperty(): int
    {
        return $this->orders->count();
    }



    public function render()
    {
       return view('livewire.admin.dashboard.orders-app-alerts', [
            'orders' => $this->orders,
            'total' => $this->total_orders_alerts
        ]);
    }
}
