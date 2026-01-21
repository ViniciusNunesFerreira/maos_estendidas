<?php

namespace App\Livewire\Admin\Settings;

use App\Models\PointDevice;
use Livewire\Component;
use Livewire\WithPagination;

class PointDevices extends Component
{
    use WithPagination;

    public $devices;
    public bool $showModal = false;
    public ?PointDevice $editingDevice = null;
    
    // ✅ CORREÇÃO: Propriedades agora são NULLABLE (?)
    public ?string $device_id = null;
    public ?string $device_name = null;
    public ?string $location = null;
    public bool $auto_print = false;
    public bool $enabled_for_pdv = true;

    protected $rules = [
        'device_id' => 'required|string',
        'device_name' => 'required|string|max:255',
        'location' => 'nullable|string',
        'auto_print' => 'boolean',
        'enabled_for_pdv' => 'boolean',
    ];

    public function openModal(?PointDevice $device = null)
    {
        $this->editingDevice = $device;
        
        if ($device) {
            // Editando device existente
            $this->device_id = $device->device_id;
            $this->device_name = $device->device_name;
            $this->location = $device->location;
            $this->auto_print = $device->auto_print ?? false;
            $this->enabled_for_pdv = $device->enabled_for_pdv ?? true;
        } else {
            // ✅ Criando novo device - reseta campos para null
            $this->device_id = null;
            $this->device_name = null;
            $this->location = null;
            $this->auto_print = false;
            $this->enabled_for_pdv = true;
        }
        
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingDevice) {
            // Editar device existente (device_id não muda)
            $this->editingDevice->update([
                'device_name' => $this->device_name,
                'location' => $this->location,
                'auto_print' => $this->auto_print,
                'enabled_for_pdv' => $this->enabled_for_pdv,
            ]);
            $message = 'Maquininha atualizada!';
        } else {
            // Criar novo device
            PointDevice::create([
                'device_id' => $this->device_id,
                'device_name' => $this->device_name,
                'location' => $this->location,
                'auto_print' => $this->auto_print,
                'enabled_for_pdv' => $this->enabled_for_pdv,
                'status' => 'active',
            ]);
            $message = 'Maquininha cadastrada!';
        }

        $this->showModal = false;
        $this->dispatch('toast', message: $message, type: 'success');
        
        // ✅ Recarregar lista de devices
        $this->devices = PointDevice::orderBy('device_name')->get();
    }

    public function toggleStatus(PointDevice $device)
    {
        if ($device->status === 'active') {
            $device->deactivate();
        } else {
            $device->activate();
        }

        $this->dispatch('toast', message: 'Status atualizado!', type: 'success');
        
        // Recarregar lista
        $this->devices = PointDevice::orderBy('device_name')->get();
    }

    public function delete(PointDevice $device)
    {
        $device->delete();
        
        $this->dispatch('toast', message: 'Maquininha removida!', type: 'success');
        
        // Recarregar lista
        $this->devices = PointDevice::orderBy('device_name')->get();
    }

    public function render()
    {
        return view('livewire.admin.settings.point-devices');
    }
}