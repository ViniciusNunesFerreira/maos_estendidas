<?php

namespace App\Livewire\Admin\StudyMaterials;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\Models\Category;
use App\Models\StudyMaterial;
use Illuminate\Support\Str;
use App\Jobs\ProcessStudyMaterialMedia;

class CreateStudyMaterial extends Component
{
    use WithFileUploads;

    public ?StudyMaterial $studyMaterial = null;

    public $title;
    public $description;
    public $content;
    public $type = 'article'; // Default
    public $category_id;
    public $price = 0.00;
    public $tags;
    
    // Arquivos
    public $file;
    public $thumbnail;

    // Coleção para o select
    public $categories;

    public function mount(StudyMaterial $studyMaterial = null)
    {
        if ($studyMaterial && $studyMaterial->exists) {
            $this->studyMaterial = $studyMaterial;
            $this->title = $studyMaterial->title;
            $this->description = $studyMaterial->description;
            $this->type = $studyMaterial->type;
            $this->category_id = $studyMaterial->category_id;
            $this->price = $studyMaterial->price;
            $this->tags = $studyMaterial->tags ? implode(', ', $studyMaterial->tags) : '';
        }
        
        // Carrega as categorias ativas do tipo 'study_material'
        $this->categories = Category::where('is_active', true)
            ->where('type', 'study_material')
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    // Validação Dinâmica baseada no 'type'
    protected function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:ebook,video,article',
            'category_id' => 'required|uuid|exists:categories,id',
            'thumbnail' => 'required|image|max:2048', // Max 2MB
            'price' => 'required|numeric|min:0',
            'tags' => 'nullable|string',
        ];

        if ($this->type === 'ebook') {
            $rules['file'] = 'required|file|mimes:pdf|max:102400'; // 100MB max
        } elseif ($this->type === 'video') {
            $rules['file'] = 'required|file|mimes:mp4,mov,avi|max:2024000'; // 2GB max para upload via Livewire em chunks
        } else {
            // Se for apenas artigo, o arquivo pode ser nulo
            $rules['file'] = 'nullable|file|mimes:pdf,doc,docx|max:10240';
        }

        return $rules;
    }

    public function save()
    {
        $this->validate();

        // 1. Prepara os dados básicos
        $data = [
            'created_by' => auth()->id(),
            'title' => $this->title,
            'slug' => Str::slug($this->title) . '-' . uniqid(),
            'description' => $this->description,
            'content' => $this->content,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'price' => $this->price,
            'is_free' => $this->price == 0,
            'tags' => $this->tags ? array_map('trim', explode(',', $this->tags)) : null,
            'is_published' => false,
            // A novidade da nossa arquitetura:
            'processing_status' => 'pending', 
        ];

        try {
            // 2. Upload Temporário (Livewire já fez a parte difícil)
            if ($this->thumbnail) {
                // Aqui podemos até mesmo disparar um Job de redimensionamento de imagem para não atrasar a tela, mas, se for leve, podemos fazer síncrono.
                $thumbnailName = time() . '_' . uniqid() . '.' . $this->thumbnail->getClientOriginalExtension();
                $data['thumbnail_path'] = $this->thumbnail->storeAs('study-materials/thumbnails/temp', $thumbnailName, 'local');
            }

            if ($this->file) {
                $fileName = time() . '_' . uniqid() . '.' . $this->file->getClientOriginalExtension();
                $data['file_path'] = $this->file->storeAs('study-materials/files/temp', $fileName, 'local');
                $data['file_name'] = $this->file->getClientOriginalName();
                $data['file_size'] = $this->file->getSize();
            }

            // 3. Salva no Banco de Dados
            //$material = StudyMaterial::create($data);

            $material = $this->studyMaterial ?? new StudyMaterial();
            $material->fill($data); // O array de dados que já montamos
            $material->save();

            ProcessStudyMaterialMedia::dispatch($material);

            session()->flash('success', 'Material salvo e enviado para processamento. Em breve estará disponível.');
            return redirect()->route('admin.materials.index');

        } catch (\Exception $e) {
            \Log::error('Erro ao salvar material: ' . $e->getMessage());
            session()->flash('error', 'Ocorreu um erro ao salvar o material.');
        }
    }

    public function render()
    {
        return view('livewire.admin.study-materials.create-study-material');
    }
}