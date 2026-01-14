<?php

namespace App\Livewire\Admin\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class SalesCard extends Component
{
    public $todaySales;
    public $yesterdaySales;
    public $trend;
    public $trendPercentage;
    
    protected $listeners = ['refreshStats' => '$refresh'];
    
    public function mount(DashboardService $service)
    {
        $data = $service->getTodaySales();
        
        $this->todaySales = $data['today'];
        $this->yesterdaySales = $data['yesterday'];
        $this->trend = $data['trend']; // 'up' or 'down'
        $this->trendPercentage = $data['percentage'];
    }
    
    public function render()
    {
        return view('livewire.admin.dashboard.sales-card');
    }
}