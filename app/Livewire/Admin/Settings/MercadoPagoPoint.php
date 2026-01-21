<?php

namespace App\Livewire\Admin\Settings;

use App\Models\PaymentSetting;
use Livewire\Component;

class MercadoPagoPoint extends Component
{
    public PaymentSetting $config;
    
    public string $device_id = '';
    public string $store_id = '';
    public string $pos_id = '';
    public bool $auto_print_receipt = false;

    protected $rules = [
        'device_id' => 'nullable|string',
        'store_id' => 'nullable|string',
        'pos_id' => 'nullable|string',
        'auto_print_receipt' => 'boolean',
    ];

    public function mount()
    {
        $this->device_id = $this->config->device_id ?? '';
        $this->store_id = $this->config->store_id ?? '';
        $this->pos_id = $this->config->pos_id ?? '';
        $this->auto_print_receipt = $this->config->auto_print_receipt ?? false;
    }

    public function save()
    {
        $this->validate();

        $this->config->update([
            'device_id' => $this->device_id,
            'store_id' => $this->store_id,
            'pos_id' => $this->pos_id,
            'auto_print_receipt' => $this->auto_print_receipt,
        ]);

        $this->dispatch('toast', 
            message: 'Configurações do Point atualizadas!',
            type: 'success'
        );
    }

    public function render()
    {
        return view('livewire.admin.settings.mercado-pago-point');
    }
}