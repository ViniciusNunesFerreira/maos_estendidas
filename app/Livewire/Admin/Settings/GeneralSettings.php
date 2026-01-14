<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Cache;

class GeneralSettings extends Component
{
    use WithFileUploads;

    // Informações da instituição
    public string $institution_name = '';
    public string $institution_cnpj = '';
    public string $institution_phone = '';
    public string $institution_email = '';
    public string $institution_address = '';
    
    // Logo
    public $logo = null;
    public ?string $currentLogo = null;
    
    // Configurações de operação
    public string $timezone = 'America/Sao_Paulo';
    public string $currency = 'BRL';
    public string $date_format = 'd/m/Y';
    public string $time_format = 'H:i';
    
    // Horário de funcionamento
    public string $opening_time = '07:00';
    public string $closing_time = '22:00';
    public bool $open_weekends = true;

    protected $rules = [
        'institution_name' => 'required|string|max:255',
        'institution_cnpj' => 'nullable|string|max:18',
        'institution_phone' => 'nullable|string|max:20',
        'institution_email' => 'nullable|email|max:255',
        'institution_address' => 'nullable|string|max:500',
        'timezone' => 'required|string',
        'currency' => 'required|string|size:3',
        'opening_time' => 'required|date_format:H:i',
        'closing_time' => 'required|date_format:H:i',
        'open_weekends' => 'boolean',
        'logo' => 'nullable|image|max:2048',
    ];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $settings = config('casalar');
        
        $this->institution_name = $settings['institution_name'] ?? '';
        $this->institution_cnpj = $settings['institution_cnpj'] ?? '';
        $this->institution_phone = $settings['institution_phone'] ?? '';
        $this->institution_email = $settings['institution_email'] ?? '';
        $this->institution_address = $settings['institution_address'] ?? '';
        $this->timezone = $settings['timezone'] ?? 'America/Sao_Paulo';
        $this->currency = $settings['currency'] ?? 'BRL';
        $this->opening_time = $settings['opening_time'] ?? '07:00';
        $this->closing_time = $settings['closing_time'] ?? '22:00';
        $this->open_weekends = $settings['open_weekends'] ?? true;
        $this->currentLogo = $settings['logo_url'] ?? null;
    }

    public function save(): void
    {
        $this->validate();

        $settings = [
            'institution_name' => $this->institution_name,
            'institution_cnpj' => $this->institution_cnpj,
            'institution_phone' => $this->institution_phone,
            'institution_email' => $this->institution_email,
            'institution_address' => $this->institution_address,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'open_weekends' => $this->open_weekends,
        ];

        if ($this->logo) {
            $settings['logo_url'] = $this->logo->store('settings', 'public');
            $this->currentLogo = $settings['logo_url'];
        }

        // Salvar no banco ou arquivo de configuração
        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : $value]
            );
        }

        Cache::forget('casalar_settings');
        
        session()->flash('message', 'Configurações salvas com sucesso!');
    }

    public function removeLogo(): void
    {
        if ($this->currentLogo) {
            \Storage::disk('public')->delete($this->currentLogo);
            \App\Models\Setting::where('key', 'logo_url')->delete();
            $this->currentLogo = null;
            Cache::forget('casalar_settings');
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.general-settings');
    }
}