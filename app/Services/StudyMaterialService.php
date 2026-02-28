<?php

namespace App\Services;

use App\Models\Category;
use App\Models\StudyMaterial;
use App\Models\MaterialView;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class StudyMaterialService
{
    public function __construct(
        private BunnyStreamService $bunnyService
    ) {}

    // =========================================================
    // HOME — Dados da tela principal
    // =========================================================

    /**
     * Retorna todos os dados necessários para a home do ambiente de estudos.
     * Usa cache de 15 minutos para não sobrecarregar o banco.
     */
    public function getHomeData(): array
    {
        $cacheKey = 'study:home:v1';

        $cached = Cache::remember($cacheKey, 60 * 15, function () {
            return [
                'categories'         => $this->getCategoriesTree(),
                'featured'           => $this->getFeatured(),
                'popular_videos'     => $this->getMaterialsByType('video', 6),
                'popular_ebooks'     => $this->getMaterialsByType('ebook', 6),
                'recent'             => $this->getRecentMaterials(8),
            ];
        });

        return $cached;
    }

    // =========================================================
    // CATEGORIAS
    // =========================================================

    /**
     * Retorna a árvore de categorias de tipo study_material (pai → filhos).
     * Apenas categorias raiz (sem parent) com seus filhos diretos.
     */
    public function getCategoriesTree(): Collection
    {
        return Category::query()
            ->where('type', 'study_material')
            ->whereNull('parent_id')
            ->with([
                'children' => function ($q) {
                    $q->where('type', 'study_material')
                      ->withCount(['studyMaterials' => fn ($q) => $q->published()])
                      ->orderBy('name');
                },
            ])
            ->withCount(['studyMaterials' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();
    }

    /**
     * Retorna uma categoria pelo slug com subcategorias e materiais paginados.
     *
     * @param  string      $slug
     * @param  string|null $type       Filtro por tipo (video, ebook, article)
     * @param  string|null $subSlug    Slug de subcategoria selecionada
     * @return array{category: Category, subcategories: Collection, materials: LengthAwarePaginator}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getCategoryData(string $slug, ?string $type = null, ?string $subSlug = null): array
    {
        // Encontra a categoria raiz ou subcategoria
        $category = Category::query()
            ->where('slug', $slug)
            ->where('type', 'study_material')
            ->with([
                'children' => function ($q) {
                    $q->where('type', 'study_material')
                      ->withCount(['studyMaterials' => fn ($q) => $q->published()])
                      ->orderBy('name');
                },
                'parent',
            ])
            ->withCount(['studyMaterials' => fn ($q) => $q->published()])
            ->firstOrFail();

        // Se solicitou filtro por subcategoria
        $targetCategory = $category;
        if ($subSlug) {
            $sub = $category->children->firstWhere('slug', $subSlug);
            if ($sub) {
                $targetCategory = $sub;
            }
        }

        // Coleta IDs de todas as categorias relevantes (pai + filhos se não filtrou)
        $categoryIds = $targetCategory->id === $category->id && !$subSlug
            ? $category->children->pluck('id')->prepend($category->id)
            : collect([$targetCategory->id]);

        $query = StudyMaterial::query()
            ->published()
            ->whereIn('category_id', $categoryIds)
            ->with(['category'])
            ->when($type, fn ($q) => $q->byType($type))
            ->orderByDesc('published_at');

        return [
            'category'      => $category,
            'subcategories' => $category->children,
            'active_sub'    => $subSlug,
            'materials'     => $query->paginate(12),
            'type_filter'   => $type,
            'counts'        => [
                'total'   => $query->count(),
                'video'   => (clone $query)->byType('video')->count(),
                'ebook'   => (clone $query)->byType('ebook')->count(),
                'article' => (clone $query)->byType('article')->count(),
            ],
        ];
    }

    // =========================================================
    // MATERIAL INDIVIDUAL
    // =========================================================

    /**
     * Retorna o material pelo slug com URL de mídia segura gerada.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getMaterialData(string $slug): array
    {
        $material = StudyMaterial::query()
            ->where('slug', $slug)
            ->published()
            ->with(['category.parent', 'creator'])
            ->firstOrFail();

        $media = $this->buildMediaPayload($material);

        // Materiais relacionados
        $related = StudyMaterial::query()
            ->published()
            ->where('category_id', $material->category_id)
            ->where('id', '!=', $material->id)
            ->latest('published_at')
            ->limit(6)
            ->get();

        return [
            'material' => $material,
            'media'    => $media,
            'related'  => $related,
        ];
    }

    /**
     * Registra visualização do material pelo usuário autenticado.
     * Incrementa contador e registra no histórico para "continue estudando".
     */
    public function trackView(StudyMaterial $material): void
    {
        $userId = Auth::id();

        // Evita spam de views — 1 view por usuário por hora
        $cacheKey = "study:view:{$material->id}:{$userId}";
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 3600);

        $material->incrementViewCount();

        \Log::info('Id do material'.$material->id);

        // Salva histórico de visualização para "continue estudando"
        if ($userId) {
            MaterialView::updateOrCreate(
                [
                    'study_material_id' => $material->id,
                    'user_id'          => $userId,
                ],
                [
                    'viewed_at' => now(),
                ]
            );
        }
    }

    // =========================================================
    // BUSCAS AUXILIARES
    // =========================================================

    /**
     * Busca full-text por materiais publicados.
     */
    public function search(string $term, ?string $type = null): LengthAwarePaginator
    {
        return StudyMaterial::query()
            ->published()
            ->where(function ($q) use ($term) {
                $q->where('title', 'ilike', "%{$term}%")
                  ->orWhere('description', 'ilike', "%{$term}%")
                  ->orWhereJsonContains('tags', $term);
            })
            ->with(['category'])
            ->when($type, fn ($q) => $q->byType($type))
            ->orderByDesc('view_count')
            ->paginate(12);
    }

    /**
     * Materiais do histórico de visualização do usuário autenticado.
     */
    public function getContinueStudying(int $limit = 4): Collection
    {
        $userId = Auth::id();
        if (!$userId) return collect();

        return StudyMaterial::query()
            ->published()
            ->whereHas('views', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'category',
                'views' => fn ($q) => $q->where('user_id', $userId)->latest('viewed_at')->limit(1),
            ])
            ->orderByDesc(
                MaterialView::select('viewed_at')
                    ->whereColumn('study_material_id', 'study_materials.id')
                    ->where('user_id', $userId)
                    ->latest('viewed_at')
                    ->limit(1)
            )
            ->limit($limit)
            ->get();
    }

    // =========================================================
    // PRIVADOS
    // =========================================================

    private function getFeatured(): ?StudyMaterial
    {
        return StudyMaterial::query()
            ->published()
            ->whereNotNull('thumbnail_path')
            ->popular()
            ->with(['category'])
            ->first();
    }

    private function getMaterialsByType(string $type, int $limit): Collection
    {
        return StudyMaterial::query()
            ->published()
            ->byType($type)
            ->with(['category'])
            ->popular()
            ->limit($limit)
            ->get();
    }

    private function getRecentMaterials(int $limit): Collection
    {
        return StudyMaterial::query()
            ->published()
            ->with(['category'])
            ->recent()
            ->limit($limit)
            ->get();
    }

    /**
     * Monta o payload de mídia seguro conforme o tipo do material.
     */
    private function buildMediaPayload(StudyMaterial $material): array
    {
        $userIp = Request::ip();

        return match ($material->type) {
            'video' => $this->buildVideoPayload($material, $userIp),
            'ebook' => $this->buildEbookPayload($material),
            'article' => ['type' => 'article', 'content' => $material->content],
            default => ['type' => 'unknown'],
        };
    }

    private function buildVideoPayload(StudyMaterial $material, ?string $userIp): array
    {
        if (!$material->external_video_id) {
            return [
                'type'   => 'video',
                'status' => $material->processing_status,
                'error'  => 'Vídeo ainda em processamento',
            ];
        }

        $secure = $this->bunnyService->getSecureEmbedUrl(
            $material->external_video_id,
            $userIp
        );

        return [
            'type'   => 'video',
            'status' => $material->processing_status,
            'media'  => [
                'embed_url'  => $secure['embed_url'],
                'expires_at' => $secure['expires_at'],
            ],
        ];
    }

    private function buildEbookPayload(StudyMaterial $material): array
    {
        if (!$material->file_url) {
            return [
                'type'  => 'ebook',
                'error' => 'Arquivo não encontrado',
            ];
        }

        $secure = $this->bunnyService->getSecureFileViewUrl($material->file_url);

        return [
            'type'  => 'ebook',
            'media' => [
                'view_url'   => $secure['view_url'],
                'expires_at' => $secure['expires_at'],
                'file_name'  => $material->file_name,
                'file_size'  => $material->file_size_formatted,
            ],
        ];
    }
}