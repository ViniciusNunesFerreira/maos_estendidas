<?php

namespace App\Livewire\Admin\StudyMaterials;

use App\Models\StudyMaterial;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class StudyMaterialList extends Component
{
    use WithPagination;

    // Filtros
    public $search = '';
    public $type = '';
    public $category_id = '';
    public $status = '';

    // Resetar página ao filtrar
    public function updatingSearch() { $this->resetPage(); }

    public function delete($id)
    {
        $material = StudyMaterial::findOrFail($id);
        
        // Se tiver arquivo local ou thumbnail, deletar antes
        if ($material->thumbnail_path) Storage::disk('bunnycdn')->delete($material->thumbnail_path);
        if ($material->file_path) Storage::disk('bunnycdn')->delete($material->file_path);
        
        $material->delete();
        session()->flash('success', 'Material removido com sucesso.');
    }

    public function render()
    {
        $query = StudyMaterial::query()
            ->with(['category'])
            ->when($this->search, function($q) {
                $q->where('title', 'ilike', '%' . $this->search . '%');
            })
            ->when($this->type, fn($q) => $q->where('type', $this->type))
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->orderBy('created_at', 'desc');

        $materials = $query->paginate(10);

        $categories = Category::where('type', 'study_material')->get();

        // Verificamos se existe algum material em processamento para ativar o polling
        $hasProcessing = StudyMaterial::whereIn('processing_status', ['pending', 'processing'])->exists();

        return view('livewire.admin.study-materials.study-material-list', [
            'materials' => $materials,
            'categories' => $categories,
            'hasProcessing' => $hasProcessing
        ]);
    }
}