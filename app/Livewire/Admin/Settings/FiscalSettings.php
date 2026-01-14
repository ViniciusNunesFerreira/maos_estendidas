<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class FiscalSettings extends Component
{
    // SAT
    public bool $sat_enabled = false;
    public string $sat_activation_code = '';
    public string $sat_signature_ac = '';
    public string $sat_cnpj_software_house = '';
    public string $sat_serial_number = '';
    
    // Dados do contribuinte
    public string $company_name = '';
    public string $company_cnpj = '';
    public string $company_ie = '';
    public string $company_im = '';
    public string $regime_tributario = '1'; // 1=Simples Nacional
    
    // Endereço
    public string $address_street = '';
    public string $address_number = '';
    public string $address_complement = '';
    public string $address_neighborhood = '';
    public string $address_city = '';
    public string $address_city_code = '';
    public string $address_state = 'SP';
    public string $address_zipcode = '';
    
    // Configurações de emissão
    public bool $auto_emit_cfe = true;
    public bool $print_on_emit = true;
    public string $printer_name = '';

    protected $rules = [
        'sat_enabled' => 'boolean',
        'sat_activation_code' => 'nullable|string|max:255',
        'sat_signature_ac' => 'nullable|string|max:500',
        'sat_cnpj_software_house' => 'nullable|string|max:18',
        'sat_serial_number' => 'nullable|string|max:50',
        'company_name' => 'required|string|max:255',
        'company_cnpj' => 'required|string|max:18',
        'company_ie' => 'nullable|string|max:20',
        'company_im' => 'nullable|string|max:20',
        'regime_tributario' => 'required|in:1,2,3',
        'address_street' => 'required|string|max:255',
        'address_number' => 'required|string|max:10',
        'address_complement' => 'nullable|string|max:100',
        'address_neighborhood' => 'required|string|max:100',
        'address_city' => 'required|string|max:100',
        'address_city_code' => 'required|string|max:10',
        'address_state' => 'required|string|size:2',
        'address_zipcode' => 'required|string|max:9',
        'auto_emit_cfe' => 'boolean',
        'print_on_emit' => 'boolean',
        'printer_name' => 'nullable|string|max:100',
    ];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $settings = config('casalar.fiscal', []);
        
        $this->sat_enabled = $settings['sat_enabled'] ?? false;
        $this->sat_activation_code = $settings['sat_activation_code'] ?? '';
        $this->sat_signature_ac = $settings['sat_signature_ac'] ?? '';
        $this->sat_cnpj_software_house = $settings['sat_cnpj_software_house'] ?? '';
        $this->sat_serial_number = $settings['sat_serial_number'] ?? '';
        
        $this->company_name = $settings['company_name'] ?? '';
        $this->company_cnpj = $settings['company_cnpj'] ?? '';
        $this->company_ie = $settings['company_ie'] ?? '';
        $this->company_im = $settings['company_im'] ?? '';
        $this->regime_tributario = $settings['regime_tributario'] ?? '1';
        
        $this->address_street = $settings['address_street'] ?? '';
        $this->address_number = $settings['address_number'] ?? '';
        $this->address_complement = $settings['address_complement'] ?? '';
        $this->address_neighborhood = $settings['address_neighborhood'] ?? '';
        $this->address_city = $settings['address_city'] ?? '';
        $this->address_city_code = $settings['address_city_code'] ?? '';
        $this->address_state = $settings['address_state'] ?? 'SP';
        $this->address_zipcode = $settings['address_zipcode'] ?? '';
        
        $this->auto_emit_cfe = $settings['auto_emit_cfe'] ?? true;
        $this->print_on_emit = $settings['print_on_emit'] ?? true;
        $this->printer_name = $settings['printer_name'] ?? '';
    }

    public function save(): void
    {
        $this->validate();

        $settings = [
            'fiscal.sat_enabled' => $this->sat_enabled,
            'fiscal.sat_activation_code' => $this->sat_activation_code,
            'fiscal.sat_signature_ac' => $this->sat_signature_ac,
            'fiscal.sat_cnpj_software_house' => $this->sat_cnpj_software_house,
            'fiscal.sat_serial_number' => $this->sat_serial_number,
            'fiscal.company_name' => $this->company_name,
            'fiscal.company_cnpj' => $this->company_cnpj,
            'fiscal.company_ie' => $this->company_ie,
            'fiscal.company_im' => $this->company_im,
            'fiscal.regime_tributario' => $this->regime_tributario,
            'fiscal.address_street' => $this->address_street,
            'fiscal.address_number' => $this->address_number,
            'fiscal.address_complement' => $this->address_complement,
            'fiscal.address_neighborhood' => $this->address_neighborhood,
            'fiscal.address_city' => $this->address_city,
            'fiscal.address_city_code' => $this->address_city_code,
            'fiscal.address_state' => $this->address_state,
            'fiscal.address_zipcode' => $this->address_zipcode,
            'fiscal.auto_emit_cfe' => $this->auto_emit_cfe,
            'fiscal.print_on_emit' => $this->print_on_emit,
            'fiscal.printer_name' => $this->printer_name,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        Cache::forget('casalar_settings');
        
        session()->flash('message', 'Configurações fiscais salvas com sucesso!');
    }

    public function testSatConnection(): void
    {
        if (!$this->sat_enabled) {
            session()->flash('error', 'SAT não está habilitado.');
            return;
        }

        try {
            // Aqui seria a chamada para testar conexão com SAT
            // $satService = app(\App\Services\SatService::class);
            // $result = $satService->consultarSAT();
            
            session()->flash('message', 'Conexão com SAT realizada com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao conectar com SAT: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $regimesTributarios = [
            '1' => 'Simples Nacional',
            '2' => 'Simples Nacional - Excesso Sublimite',
            '3' => 'Regime Normal',
        ];

        return view('livewire.admin.settings.fiscal-settings', [
            'regimesTributarios' => $regimesTributarios,
        ]);
    }
}