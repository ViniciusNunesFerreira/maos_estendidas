<?php

namespace App\Livewire\Admin\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

class SalesChart extends Component
{
    public $period = 7; // dias
    public $chartData;
    
    protected $listeners = ['refreshStats' => '$refresh'];
    
    public function mount(DashboardService $service)
    {
        $this->loadChartData($service);
    }
    
    public function updatedPeriod(DashboardService $service)
    {
        $this->loadChartData($service);
    }
    
    private function loadChartData(DashboardService $service)
    {
        $data = $service->getSalesChart($this->period);
        
        $this->chartData = [
            'labels' => $data['labels'], // ['01/12', '02/12', ...]
            'values' => $data['values'], // [1200, 1500, ...]
        ];
    }
    
    public function render()
    {
        return view('livewire.admin.dashboard.sales-chart');
    }
}