<?php

namespace App\Livewire\Admin\Settings;

use App\Models\PaymentSetting;
use Livewire\Component;

class MercadoPagoMethods extends Component
{
    public PaymentSetting $config;
    public array $selectedMethods = [];
    
    public array $availableMethods = [
        'pix' => [
            'name' => 'PIX',
            'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'description' => 'Pagamento instantâneo via PIX',
        ],
        'credit_card' => [
            'name' => 'Cartão de Crédito',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'description' => 'Cartão de crédito (App)',
        ],
        'debit_card' => [
            'name' => 'Cartão de Débito',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'description' => 'Cartão de débito (App)',
        ],
        'tef' => [
            'name' => 'TEF Point',
            'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            'description' => 'Maquininha Point (PDV)',
        ],
    ];

    public function mount()
    {
        $this->selectedMethods = $this->config->active_methods ?? [];
    }

    public function toggleMethod(string $method)
    {
        if (in_array($method, $this->selectedMethods)) {
            $this->selectedMethods = array_values(array_diff($this->selectedMethods, [$method]));
        } else {
            $this->selectedMethods[] = $method;
        }
    }

    public function save()
    {
        $this->config->update([
            'active_methods' => $this->selectedMethods,
        ]);

        $this->dispatch('toast', 
            message: 'Métodos de pagamento atualizados!',
            type: 'success'
        );
    }

    public function render()
    {
        return view('livewire.admin.settings.mercado-pago-methods');
    }
}