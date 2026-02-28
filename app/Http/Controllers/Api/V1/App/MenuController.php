<?php
// app/Http/Controllers/Api/V1/App/MenuController.php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:loja,cantina',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|in:name,price',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = Product::query()
            ->where('is_active', true)
            ->where('available_app', true) // Disponível no app
            ->where('stock_quantity', '>', 0)
            ->with(['category']);

        // NOVO: Filtro por tipo (loja ou cantina)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtro por categoria
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Busca por nome ou descrição
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('sku', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = min($request->input('per_page', 20), 50);
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
     * Listar categorias com produtos disponíveis
     * 
     * GET /api/v1/app/menu/categories
     * 
     * Query params:
     * - type: 'loja' ou 'cantina' (NOVO)
     */
    public function categories(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:loja,cantina',
        ]);

        $query = Category::query()
            ->where('is_active', true)
            ->where('type', 'product')
            ->whereHas('products', function ($q) use ($request) {
                $q->where('is_active', true)
                  ->where('available_app', true)
                  ->where('stock_quantity', '>', 0);
                
                // Filtrar produtos por tipo
                if ($request->filled('type')) {
                    $q->where('type', $request->type);
                }
            })
            ->withCount([
                'products' => function ($q) use ($request) {
                    $q->where('is_active', true)
                      ->where('available_app', true)
                      ->where('stock_quantity', '>', 0);
                    
                    // Filtrar produtos por tipo
                    if ($request->filled('type')) {
                        $q->where('type', $request->type);
                    }
                }
            ])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($query),
        ]);
    }

    /**
     * Detalhes de um produto
     * 
     * GET /api/v1/app/menu/products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        // Verificar se o produto está disponível no app
        if (!$product->available_app) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não disponível no app',
            ], 403);
        }

        // Verificar se o produto está ativo e tem estoque
        if (!$product->is_active || $product->stock_quantity <= 0) {
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