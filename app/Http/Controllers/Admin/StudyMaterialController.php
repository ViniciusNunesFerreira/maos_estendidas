<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudyMaterial;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StudyMaterialController extends Controller
{
    
    public function index(Request $request)
    {

        return view('admin.study-materials.index');
    }

    public function create()
    {
        return view('admin.study-materials.create');
    }

    public function store(Request $request)
    {
        $babalorixa = Auth::user();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:ebook,video,article',
            'category_id' => 'required|uuid|exists:categories,id',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:51200',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,bmp,webp|max:2048',
            'price' => 'required|nullable|numeric|min:0',
            'tags' => 'nullable|string|max:255',
        ]); 

       
        $data = $request->only([
            'title', 'description', 'content', 'type', 'category', 'price'
        ]);

        $data['created_by'] = $babalorixa->id;
        $data['is_free'] = $request->price == 0 ? true : false;

        try{
            // Tags
            if ($request->filled('tags')) {
                $data['tags'] = array_map('trim', explode(',', $request->tags));
            }

            // Upload do arquivo principal
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . uniqid() . '.' . $file->extension();

                //$data['file_path'] = $file->store('study-materials/files', 'public');

                // 'public' garante que o arquivo é acessível publicamente
                 $data['file_path'] =  Storage::disk('bunnycdn')->putFileAs('study-materials/files', $file, $fileName, 'public'); 

                $data['file_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
            }

            // Upload da thumbnail
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->extension();
                // $path = $thumbnail->store('study-materials/thumbnails', 'public');
                $path = Storage::disk('bunnycdn')->putFileAs('study-materials/thumbnails', $thumbnail, $thumbnailName, 'public');
                // Redimensionar thumbnail
               /* $manager = new ImageManager(new Driver());
                $image =  $manager->read(storage_path('app/public/' . $path));
                $image->resize(300, 200)->save();*/

                $resized = $this->resizeImageOnBunnyCDN($path);

                \Log::info($resized);
                
                $data['thumbnail_path'] = $path;
            }

            $material = StudyMaterial::create($data);

        }catch (\Exception $e) {
            \Log::error('Erro ao salvar material de estudo: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao salvar o material. Tente novamente.');
        }

        return redirect()
            ->route('admin.study-materials.index')
            ->with('success', 'Material de estudo criado com sucesso!');
    }

    public function show(StudyMaterial $studyMaterial)
    {
        $this->authorize('view', $studyMaterial);

        return view('admin.study-materials.create', compact('studyMaterial'));
    }

    public function edit(StudyMaterial $studyMaterial)
    {
       

        return view('admin.study-materials.edit', compact('studyMaterial'));
    }

    public function update(Request $request, StudyMaterial $studyMaterial)
    {
       

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:ebook,video,pdf,article',
            'category' => 'required|in:buzios,cartas,oraculo,espiritualidade,ifa,rituals,umbanda,orixas,ancestrais',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:51200',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,bmp,webp|max:2048',
            'price' => 'required|nullable|numeric|min:0',
            'tags' => 'nullable|string',
        ]);

        $data = $request->only([
            'title', 'description', 'content', 'type', 'category', 'is_free', 'price'
        ]);

       $data['is_free'] = $request->price == 0 ? true : false;

        // Tags
        if ($request->filled('tags')) {
            $data['tags'] = array_map('trim', explode(',', $request->tags));
        } else {
            $data['tags'] = null;
        }

        // Upload do arquivo principal
        if ($request->hasFile('file')) {
            // Remover arquivo antigo
            if ($studyMaterial->file_path) {
                Storage::disk('bunnycdn')->delete($studyMaterial->file_path);
            }

            $file = $request->file('file');
            $fileName = time() . '_' . uniqid() . '.' . $file->extension();

            $data['file_path'] =  Storage::disk('bunnycdn')->putFileAs('study-materials/files', $file, $fileName, 'public'); 

            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        // Upload da thumbnail
        if ($request->hasFile('thumbnail')) {
            // Remover thumbnail antiga
            if ($studyMaterial->thumbnail_path) {
                Storage::disk('bunnycdn')->delete($studyMaterial->thumbnail_path);
            }

            $thumbnail = $request->file('thumbnail');

            $thumbnailName = time() . '_' . uniqid() . '.' . $thumbnail->extension();
            $path = Storage::disk('bunnycdn')->putFileAs('study-materials/thumbnails', $thumbnail, $thumbnailName, 'public');

            $this->resizeImageOnBunnyCDN($path);

            $data['thumbnail_path'] = $path;
        }

        $studyMaterial->update($data);

        if($request->is_published){
            $studyMaterial->publish();
        }else{
            $studyMaterial->unpublish();
        }

        return redirect()
            ->route('admin.study-materials.show', $studyMaterial)
            ->with('success', 'Material de estudo atualizado com sucesso!');
    }

    public function publish(StudyMaterial $studyMaterial)
    {
        $this->authorize('update', $studyMaterial);

        $studyMaterial->publish();

        return back()->with('success', 'Material publicado com sucesso!');
    }

    public function unpublish(StudyMaterial $studyMaterial)
    {
        $this->authorize('update', $studyMaterial);

        $studyMaterial->unpublish();

        return back()->with('success', 'Material despublicado com sucesso!');
    }

    public function destroy(StudyMaterial $studyMaterial)
    {
        $this->authorize('delete', $studyMaterial);

        // Remover arquivos
        if ($studyMaterial->file_path) {
            Storage::disk('public')->delete($studyMaterial->file_path);
        }

        if ($studyMaterial->thumbnail_path) {
            Storage::disk('public')->delete($studyMaterial->thumbnail_path);
        }

        $studyMaterial->delete();

        return redirect()
            ->route('admin.study-materials.index')
            ->with('success', 'Material removido com sucesso!');
    }

    /**
     * Baixa o arquivo do material de estudo.
     */
    public function download(StudyMaterial $studyMaterial)
    {
        $diskName = 'bunnycdn';

        if (!Storage::disk($diskName)->exists($studyMaterial->file_path)) {
            Log::error("Download falhou: Arquivo não encontrado no path '{$studyMaterial->file_path}' no disco '{$diskName}'");
            return redirect()->back()->with('error', 'O arquivo solicitado não foi encontrado.');
        }

        $headers = array('Content-Type' => 'application/pdf');
        
        return Storage::disk($diskName)->download(
            $studyMaterial->file_path, 
            $studyMaterial->file_name, 
            $headers
        );
    }


    protected function resizeImageOnBunnyCDN(string $filePath)
    {
        $diskName = 'bunnycdn'; // Substitua pelo nome real do seu disco
        
        if (!Storage::disk($diskName)->exists($filePath)) {
            throw new \Exception("Arquivo não encontrado no BunnyCDN.");
        }
        
        
        $originalContent = Storage::disk($diskName)->get($filePath);

        // 2. PROCESSAR NA MEMÓRIA
        $manager = new ImageManager(new Driver());
        
        // Ler o conteúdo binário, não o caminho local
        $image = $manager->read($originalContent); 
        
        // Redimensionar e Codificar (o Intervention 3+ usa o encode para obter o conteúdo binário)
        $resizedImageContent = $image
            ->resize(300, 200)
            ->encode(); // Codifica a imagem redimensionada no formato original ou um novo

        // 3. SUBIR O NOVO ARQUIVO DE VOLTA
        // Você pode substituir o original ou salvar como um novo arquivo (ex: '300x200/' . $fileName)
        
        // Opção A: Sobrescrever o original
       // Storage::disk($diskName)->put($filePath, $resizedImageContent);
       
        return Storage::disk($diskName)->put($filePath, $resizedImageContent);
    }
    
}
