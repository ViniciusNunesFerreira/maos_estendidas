<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Order;
use Livewire\Component;

class OrdersCard extends Component
{
    public int $todayOrders = 0;
    public int $yesterdayOrders = 0;
    public int $pendingOrders = 0;
    public float $trend = 0;

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->todayOrders = Order::whereDate('created_at', today())->count();
        $this->yesterdayOrders = Order::whereDate('created_at', today()->subDay())->count();
        $this->pendingOrders = Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count();

        if ($this->yesterdayOrders > 0) {
            $this->trend = round((($this->todayOrders - $this->yesterdayOrders) / $this->yesterdayOrders) * 100, 1);
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.orders-card');
    }
}