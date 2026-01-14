<?php

namespace App\Http\Controllers\Api\V1\Totem;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Listar produtos da cantina para o totem de autoatendimento
     * 
     * GET /api/v1/totem/menu
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('location', 'cantina') // Apenas produtos da cantina
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->with(['category']);

        // Filtro por categoria
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Busca por nome ou código de barras
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('barcode', $search);
            });
        }

        // Ordenação (destaque primeiro, depois por nome)
        $query->orderByDesc('is_featured')
              ->orderBy('name');

        // Paginação
        $perPage = min($request->input('per_page', 30), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Listar categorias da cantina
     * 
     * GET /api/v1/totem/menu/categories
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', function ($query) {
                $query->where('location', 'cantina')
                    ->where('is_active', true)
                    ->where('stock', '>', 0);
            })
            ->withCount([
                'products as available_products_count' => function ($query) {
                    $query->where('location', 'cantina')
                        ->where('is_active', true)
                        ->where('stock', '>', 0);
                }
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Detalhes de um produto
     * 
     * GET /api/v1/totem/menu/products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        // Verificar se o produto é da cantina
        if ($product->location !== 'cantina') {
            return response()->json([
                'success' => false,
                'message' => 'Produto não disponível no totem',
            ], 403);
        }

        // Verificar disponibilidade
        if (!$product->is_active || $product->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Produto indisponível no momento',
            ], 404);
        }

        $product->load(['category']);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }
}