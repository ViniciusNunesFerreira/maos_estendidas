<?php

namespace App\Jobs;

use App\Models\StudyMaterial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class ProcessStudyMaterialMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Tentativas em caso de falha na API
    public $timeout = 3600; // 1 hora para vídeos grandes

    public function __construct(protected StudyMaterial $material)
    {}

    public function handle(): void
    {
        try {
            $this->material->update(['processing_status' => 'processing']);

            // 1. Processar Thumbnail (Sempre existe)
            $this->processThumbnail();

            // 2. Processar Arquivo Principal (Se houver)
            if ($this->material->type === 'video') {
                $this->processVideo();
            } elseif ($this->material->type === 'ebook') {
                $this->processEbook();
            }

            $this->material->refresh();

            // 3. Finalizar
            $this->material->update([
                'processing_status' => 'completed',
                'is_published' => true, // Opcional: publicar automaticamente após processar
                'published_at' => now(),
            ]);

            Log::info("Material ID {$this->material->id} processado com sucesso.");

        } catch (\Exception $e) {
            $this->material->update(['processing_status' => 'failed']);
            Log::error("Erro no processamento do Material {$this->material->id}: " . $e->getMessage());
            throw $e;
        }
    }


    protected function processThumbnail()
    {
        if (!$this->material->thumbnail_path) return;

        
        if (!Storage::disk('local')->exists($this->material->thumbnail_path)) {
            Log::error("Arquivo não encontrado no disco local", ['path' => $this->material->thumbnail_path]);
            throw new \Exception("Arquivo local não encontrado para processamento: " . $this->material->thumbnail_path);
        }

        try {
          
            $fileContent = Storage::disk('local')->get($this->material->thumbnail_path);
            // 4. Processamento com Intervention Image
            $manager = new ImageManager(new Driver());
            // Passa o binário ($fileContent) 
            $image = $manager->read($fileContent);
            // Redimensionar e converter para WebP (Premium Performance)
            $encoded = $image->cover(800, 450)->toWebp(80)->toString();

            $fileName = 'thumbnails/' . Str::uuid() . '.webp';
            
            // 5. Upload para o BunnyCDN
            Storage::disk('bunnycdn')->put($fileName, $encoded);

            // 6. Atualiza e Limpa o temporário
            $oldPath = $this->material->thumbnail_path;
            $this->material->update(['thumbnail_path' => $fileName]);
            
            Storage::disk('local')->delete($oldPath);

        } catch (\Exception $e) {
            Log::error("Falha ao decodificar/processar imagem: " . $e->getMessage());
            throw $e;
        }
    }

    protected function processEbook()
    {
        // 1. Validar se o arquivo existe antes de qualquer coisa
        if (!$this->material->file_path || !Storage::disk('local')->exists($this->material->file_path)) {
            Log::error("Arquivo Ebook não encontrado", ['path' => $this->material->file_path]);
            throw new \Exception("Arquivo local do Ebook não encontrado: " . $this->material->file_path);
        }

        // 2. Definir nome final no Bunny
        $fileName = 'secure_materials/ebooks/' . Str::uuid() . '.pdf';
        
        // 3. Upload usando Stream (Nível Sênior: não carrega o arquivo todo na memória RAM)
        $stream = Storage::disk('local')->readStream($this->material->file_path);
        
        // Enviamos o stream diretamente para o BunnyCDN
        Storage::disk('bunnycdn')->put($fileName, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        // 4. Atualizar banco e limpar local
        $oldPath = $this->material->file_path;
        $this->material->update(['file_path' => $fileName]);
        Storage::disk('local')->delete($oldPath);
    }


    protected function processVideo()
    {
        $libraryId = env('BUNNY_STREAM_LIBRARY_ID');
        $apiKey = env('BUNNY_STREAM_API_KEY');

        Log::info("Iniciando criação de vídeo no Bunny Stream", ['library_id' => $libraryId]);

        // A. Criar o objeto de vídeo no Bunny Stream
        $createResponse = Http::withHeaders([
            'AccessKey' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->post("https://video.bunnycdn.com/library/{$libraryId}/videos", [
            'title' => $this->material->title,
        ]);

        // B. Verificação Robusta da Resposta
        if ($createResponse->failed()) {
            Log::error("Falha ao criar vídeo no Bunny Stream", [
                'status' => $createResponse->status(),
                'response' => $createResponse->body(),
                'material_id' => $this->material->id
            ]);
            throw new \Exception("Erro Bunny API ({$createResponse->status()}): " . $createResponse->body());
        }

        $responseData = $createResponse->json();

        // Verificação de segurança para evitar o erro de offset on null
        if (!isset($responseData['guid'])) {
            Log::error("Resposta do Bunny não contém GUID", ['response' => $responseData]);
            throw new \Exception("GUID não encontrado na resposta do Bunny Stream.");
        }

        $videoId = $responseData['guid'];

       
        // 1. Verificação de existência usando o Facade Storage (Mais seguro em Docker)
        if (!Storage::disk('local')->exists($this->material->file_path)) {
            Log::error("Arquivo não encontrado no disco local pelo Worker", [
                'path' => $this->material->file_path,
                'full_path' => storage_path('app/' . $this->material->file_path)
            ]);
            throw new \Exception("O Worker não encontrou o arquivo: " . $this->material->file_path);
        }

        $tempPath = $this->material->file_path;
        
        // 2. Abrir o Stream via Laravel Storage
        $stream = Storage::disk('local')->readStream($this->material->file_path);

        // Usando stream para não estourar a memória com vídeos grandes
        $uploadResponse = Http::withHeaders([
            'AccessKey' => $apiKey,
        ])->withBody($stream, 'application/octet-stream')
        ->put("https://video.bunnycdn.com/library/{$libraryId}/videos/{$videoId}");

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($uploadResponse->failed()) {
            Log::error("Falha no upload do arquivo para Bunny Stream", [
                'status' => $uploadResponse->status(),
                'response' => $uploadResponse->body()
            ]);
            throw new \Exception("Falha no upload do vídeo.");
        }

        if ($uploadResponse->successful()) {

            Log::info("Upload concluído com sucesso para o Bunny", ['video_id' => $videoId]);
            // D. Sucesso: Atualizar banco e remover local
            $this->material->update([
                'external_video_id' => $videoId,
                'file_path' => null, 
            ]);

            // 3. Deletamos o arquivo usando a variável temporária
            if ($tempPath && Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
                Log::info("Arquivo temporário removido com sucesso: " . $tempPath);
            }

        }

       
    }
    
}