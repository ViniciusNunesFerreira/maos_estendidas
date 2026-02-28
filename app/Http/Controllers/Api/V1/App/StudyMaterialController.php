<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\StudyMaterial;
use App\Services\StudyMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * StudyMaterialController
 *
 * Alimenta o ambiente educacional do App dos Filhos.
 * Todos os endpoints exigem autenticação Sanctum (ability:filho:*).
 *
 * Rotas definidas em: routes/api.php (grupo prefix 'app')
 */
class StudyMaterialController extends Controller
{
    public function __construct(
        private StudyMaterialService $studyService
    ) {}

    // =========================================================
    // GET /api/v1/app/study
    // Home do ambiente de estudos
    // =========================================================

    /**
     * Retorna todos os dados da home de estudos:
     * - Árvore de categorias
     * - Material em destaque
     * - Populares por tipo
     * - Recentes
     * - "Continue estudando" (materiais vistos pelo usuário)
     */
    public function home(): JsonResponse
    {
        $data = $this->studyService->getHomeData();

        // Continue estudando é individual — não cacheado globalmente
        $data['continue_studying'] = $this->studyService->getContinueStudying(4);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // =========================================================
    // GET /api/v1/app/study/categories
    // Árvore completa de categorias
    // =========================================================

    public function categories(): JsonResponse
    {
        $categories = $this->studyService->getCategoriesTree();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    // =========================================================
    // GET /api/v1/app/study/categories/{slug}
    // Categoria com subcategorias e materiais paginados
    // =========================================================

    /**
     * @queryParam type  string  Filtrar por tipo: video|ebook|article
     * @queryParam sub   string  Slug de subcategoria ativa
     * @queryParam page  int     Página (padrão: 1)
     */
    public function category(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', 'in:video,ebook,article'],
            'sub'  => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $data = $this->studyService->getCategoryData(
                $slug,
                $request->query('type'),
                $request->query('sub')
            );

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada',
            ], 404);
        }
    }

    // =========================================================
    // GET /api/v1/app/study/materials/{slug}
    // Material individual com URL de mídia segura
    // =========================================================

    /**
     * Retorna os dados completos do material incluindo URL assinada para
     * visualização segura (vídeo ou PDF) com validade de 1 hora.
     */
    public function material(string $slug): JsonResponse
    {
        try {
            $data = $this->studyService->getMaterialData($slug);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Material não encontrado',
            ], 404);
        }
    }

    // =========================================================
    // POST /api/v1/app/study/materials/{slug}/view
    // Registra visualização (throttle por cache)
    // =========================================================

    public function trackView(string $slug): JsonResponse
    {
        try {
            $material = StudyMaterial::where('slug', $slug)->published()->firstOrFail();
            $this->studyService->trackView($material);

            return response()->json([
                'success' => true,
                'message' => 'Visualização registrada',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Material não encontrado',
            ], 404);
        }
    }

    // =========================================================
    // GET /api/v1/app/study/search?q=termo
    // Busca de materiais
    // =========================================================

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q'    => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['nullable', 'in:video,ebook,article'],
        ]);

        $results = $this->studyService->search(
            $request->query('q'),
            $request->query('type')
        );

        return response()->json([
            'success' => true,
            'data'    => $results,
            'term'    => $request->query('q'),
        ]);
    }
}