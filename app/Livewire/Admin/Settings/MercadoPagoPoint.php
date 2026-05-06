<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Device;
use App\Models\PaymentSetting;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

class MercadoPagoPoint extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $showModal = false;
    public $isEditMode = false;
    public $deleteId = null;

    public $deviceId;
    public $name;
    public $internal_id;
    public $type = 'kiosk';
    public $tef_provider = 'mercadopago';
    public $tef_device_id;
    public $ip_address;
    public $printer_client_ip;
    public $printer_kitchen_ip;
    public $notes;
    public $is_active = true;

    public $search = '';

    // Variáveis para integração com API do Mercado Pago
    public $mpDevices = [];
    public $fetchingDevices = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'internal_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('devices', 'internal_id')->ignore($this->deviceId),
            ],
            'type' => 'required|in:kiosk,pos,kds',
            'tef_provider' => 'nullable|string|in:mercadopago,getnet,stone',
            'tef_device_id' => 'nullable|string|max:150',
            'ip_address' => 'nullable|ip',
            'printer_client_ip' => 'nullable|ip',
            'printer_kitchen_ip' => 'nullable|ip',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->resetValidation();
        $this->deviceId = null;
        $this->name = '';
        $this->internal_id = '';
        $this->type = 'kiosk';
        $this->tef_provider = 'mercadopago';
        $this->tef_device_id = '';
        $this->ip_address = '';
        $this->printer_client_ip = '';
        $this->printer_kitchen_ip = '';
        $this->notes = '';
        $this->is_active = true;
        $this->mpDevices = [];
    }

    public function edit($id)
    {
        $this->resetValidation();
        
        $device = Device::findOrFail($id);
        
        $this->deviceId = $device->id;
        $this->name = $device->name;
        $this->internal_id = $device->internal_id;
        $this->type = $device->type;
        $this->tef_provider = $device->tef_provider;
        $this->tef_device_id = $device->tef_device_id;
        $this->ip_address = $device->ip_address;
        $this->printer_client_ip = $device->printer_client_ip;
        $this->printer_kitchen_ip = $device->printer_kitchen_ip;
        $this->notes = $device->notes;
        $this->is_active = $device->is_active;

        // Se já for MP, incluímos no array visual para o Select não quebrar antes da busca
        if ($this->tef_provider === 'mercadopago' && $this->tef_device_id) {
            $this->mpDevices = [
                ['id' => $this->tef_device_id, 'operating_mode' => 'Desconhecido (Busque para atualizar)']
            ];
        } else {
            $this->mpDevices = [];
        }

        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->type === 'kiosk' && $this->tef_provider && empty($this->tef_device_id)) {
            $this->addError('tef_device_id', 'O ID da Maquininha é obrigatório quando um provedor TEF é selecionado.');
            return;
        }

        Device::updateOrCreate(
            ['id' => $this->deviceId],
            [
                'name' => $this->name,
                'internal_id' => $this->internal_id,
                'type' => $this->type,
                'tef_provider' => empty($this->tef_provider) ? null : $this->tef_provider,
                'tef_device_id' => empty($this->tef_device_id) ? null : $this->tef_device_id,
                'ip_address' => empty($this->ip_address) ? null : $this->ip_address,
                'printer_client_ip' => empty($this->printer_client_ip) ? null : $this->printer_client_ip,
                'printer_kitchen_ip' => empty($this->printer_kitchen_ip) ? null : $this->printer_kitchen_ip,
                'notes' => empty($this->notes) ? null : $this->notes,
                'is_active' => $this->is_active,
            ]
        );

        $this->closeModal();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Terminal salvo com sucesso!']);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('confirm-deletion', ['id' => $id]);
    }

    #[\Livewire\Attributes\On('delete-device')]
    public function delete()
    {
        if ($this->deleteId) {
            Device::findOrFail($this->deleteId)->delete();
            $this->deleteId = null;
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Terminal removido com sucesso!']);
        }
    }

    public function toggleStatus($id)
    {
        $device = Device::findOrFail($id);
        $device->is_active = !$device->is_active;
        $device->save();
        
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Status do terminal atualizado!']);
    }

    /**
     * Busca maquininhas vinculadas na API do Mercado Pago
     */
    public function fetchMpDevices()
    {
        $this->fetchingDevices = true;
        try {
            $config = PaymentSetting::getMercadoPagoConfig();
            $token = $config ? $config->access_token : config('services.mercadopago.access_token');
            
            if (empty($token)) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Token do Mercado Pago não configurado.']);
                $this->fetchingDevices = false;
                return;
            }

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("https://api.mercadopago.com/point/integration-api/devices");

            if ($response->successful()) {
                $this->mpDevices = $response->json('devices') ?? [];
                
                if (empty($this->mpDevices)) {
                    $this->dispatch('notify', ['type' => 'warning', 'message' => 'Nenhuma maquininha vinculada a esta conta foi encontrada.']);
                } else {
                    $this->dispatch('notify', ['type' => 'success', 'message' => count($this->mpDevices) . ' maquininha(s) carregada(s) com sucesso.']);
                }
            } else {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Erro ao consultar API da operadora.']);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar devices MP', ['error' => $e->getMessage()]);
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Falha de rede ao contatar o Mercado Pago.']);
        }
        $this->fetchingDevices = false;
    }

    /**
     * Testa a comunicação com a API Point Integration do Mercado Pago.
     */
    public function testConnection($id)
    {
        try {
            $device = Device::findOrFail($id);

            if ($device->tef_provider !== 'mercadopago' || empty($device->tef_device_id)) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Terminal não está configurado para o Mercado Pago ou sem ID TEF.']);
                return;
            }

            $config = PaymentSetting::getMercadoPagoConfig();
            $token = $config ? $config->access_token : config('services.mercadopago.access_token');

            if (empty($token)) {
                $this->dispatch('notify', ['type' => 'error', 'message' => 'Token do Mercado Pago não configurado no sistema.']);
                return;
            }

            // Faz o GET na lista geral de devices
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("https://api.mercadopago.com/point/integration-api/devices");

            if ($response->successful()) {
                $devicesList = $response->json('devices') ?? [];

                \Log::debug((array) $devicesList);
                
                // Filtra a nossa maquininha usando as Collections do Laravel
                $mpDevice = collect($devicesList)->firstWhere('id', $device->tef_device_id);

                if ($mpDevice) {
                     \Log::info('encontrou a maquininha');
                    $mode = $mpDevice['operating_mode'] ?? 'Desconhecido';
                    $this->dispatch('flash', ['type' => 'success', 'message' => "Conexão OK! Maquininha respondendo (Modo: {$mode})."]);
                } else {
                    \Log::info('não encontrou a maquininha');
                    $this->dispatch('flash', ['type' => 'error', 'message' => 'A maquininha não foi encontrada na sua conta do Mercado Pago.']);
                }
            } else {
                $this->dispatch('flash', ['type' => 'error', 'message' => 'A API do Mercado Pago não respondeu corretamente.']);
            }

        } catch (\Exception $e) {
            \Log::error('Erro ao testar conexão física do terminal', ['error' => $e->getMessage(), 'device' => $id]);
            $this->dispatch('flash', ['type' => 'error', 'message' => 'Ocorreu um erro interno de rede ao tentar conectar.']);
        }
    }

    /**
     * Altera o modo de operação na maquininha física (PDV <-> STANDALONE)
     */
    public function toggleOperatingMode($id)
    {
        try {
            $device = Device::findOrFail($id);

            if ($device->tef_provider !== 'mercadopago' || empty($device->tef_device_id)) {
                $this->dispatch('flash', ['type' => 'error', 'message' => 'Comando exclusivo para terminais do Mercado Pago.']);
                return;
            }

            $config = PaymentSetting::getMercadoPagoConfig();
            $token = $config ? $config->access_token : config('services.mercadopago.access_token');

            if (empty($token)) {
                $this->dispatch('flash', ['type' => 'error', 'message' => 'Token do Mercado Pago não configurado.']);
                return;
            }

            // Descobre o modo atual puxando da lista geral
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("https://api.mercadopago.com/point/integration-api/devices");

            if ($response->successful()) {
                $devicesList = $response->json('devices') ?? [];
                $mpDevice = collect($devicesList)->firstWhere('id', $device->tef_device_id);

                if (!$mpDevice) {
                    $this->dispatch('flash', ['type' => 'error', 'message' => 'Maquininha não encontrada na conta para alternar o modo.']);
                    return;
                }

                $currentMode = $mpDevice['operating_mode'] ?? 'STANDALONE';
                $newMode = ($currentMode === 'PDV') ? 'STANDALONE' : 'PDV';

                // Aplica a alteração remotamente (A rota PATCH felizmente existe de forma individual na documentação do MP)
                $patchResponse = Http::withToken($token)
                    ->timeout(8)
                    ->patch("https://api.mercadopago.com/point/integration-api/devices/{$device->tef_device_id}", [
                        'operating_mode' => $newMode
                    ]);

                if ($patchResponse->successful()) {
                    $this->dispatch('flash', ['type' => 'success', 'message' => "Modo da maquininha alterado para {$newMode} com sucesso!"]);
                } else {
                    $this->dispatch('flash', ['type' => 'error', 'message' => 'A API do Mercado Pago recusou a alteração. Tente novamente.']);
                }
            } else {
                $this->dispatch('flash', ['type' => 'error', 'message' => 'Falha ao consultar o Mercado Pago para ver o status atual.']);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao alternar modo da maquininha', ['error' => $e->getMessage()]);
            $this->dispatch('flash', ['type' => 'error', 'message' => 'Erro de comunicação ao enviar comando para a máquina.']);
        }
    }

    public function render()
    {
        $devices = Device::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'ilike', '%' . $this->search . '%')
                      ->orWhere('internal_id', 'ilike', '%' . $this->search . '%')
                      ->orWhere('tef_device_id', 'ilike', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.settings.mercado-pago-point', [
            'devices' => $devices
        ]);
    }
}