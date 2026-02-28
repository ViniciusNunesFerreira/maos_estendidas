<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Listar produtos disponíveis no PDV
     * GET /api/v1/pdv/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('available_pdv', true)
            ->whereIn('type', ['loja', 'cantina'])
            ->with(['category:id,name,slug,icon,color']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtro por categoria
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Busca por nome, SKU ou código de barras
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%")
                  ->orWhere('barcode', $search);
            });
        }

        // Apenas com estoque
        if ($request->boolean('in_stock', true)) {
            $query->where('stock_quantity', '>', 0);
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        if (in_array($sortBy, ['name', 'price', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $products = $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'track_stock' => $product->track_stock,
                'stock_quantity' => $product->stock_quantity,
                'min_stock_alert' => $product->min_stock_alert,
                'is_low_stock' => $product->stock_quantity <= $product->min_stock_alert,
                'image_url' => $product->image_url,
                'category_id' => $product->category_id,
                'is_active' => $product->is_active,
                'available_pdv' => $product->available_pdv,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'icon' => $product->category->icon,
                    'color' => $product->category->color,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
            'meta' => [
                'total' => $products->count(),
            ],
        ]);
    }

    /**
     * Buscar produto por código de barras
     * GET /api/v1/pdv/products/barcode/{barcode}
     */
    public function findByBarcode(string $barcode): JsonResponse
    {
        $product = Product::where('barcode', $barcode)
            ->where('is_active', true)
            ->whereIn('location', ['loja', 'ambos'])
            ->with(['category:id,name,icon,color'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado',
            ], 404);
        }

        if ($product->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Produto sem estoque',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'stock' => $product->stock,
                'image_url' => $product->image_url,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Buscar produto por SKU
     * GET /api/v1/pdv/products/sku/{sku}
     */
    public function findBySku(string $sku): JsonResponse
    {
        $product = Product::where('sku', $sku)
            ->where('is_active', true)
            ->whereIn('location', ['loja', 'ambos'])
            ->with(['category:id,name,icon,color'])
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'stock' => $product->stock,
                'image_url' => $product->image_url,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Listar categorias para o PDV
     * GET /api/v1/pdv/categories
     */
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->where('type', 'product')
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true)
                  ->whereIn('type', ['loja', 'cantina'])
                  ->where('stock_quantity', '>', 0);
            }])
            ->orderBy('order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'products_count' => $category->products_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Verificar estoque de múltiplos produtos
     * POST /api/v1/pdv/products/check-stock
     */
    public function checkStock(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $results = [];
        $allAvailable = true;

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            
            $available = $product && $product->is_active && $product->stock >= $item['quantity'];
            
            if (!$available) {
                $allAvailable = false;
            }

            $results[] = [
                'product_id' => $item['product_id'],
                'product_name' => $product?->name,
                'requested' => $item['quantity'],
                'available' => $product?->stock ?? 0,
                'is_available' => $available,
            ];
        }

        return response()->json([
            'success' => true,
            'all_available' => $allAvailable,
            'data' => $results,
        ]);
    }

    /**
     * Produtos mais vendidos (para sugestões)
     * GET /api/v1/pdv/products/popular
     */
    public function popular(): JsonResponse
    {
        $products = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->where('orders.status', '!=', 'cancelled')
            ->where('products.is_active', true)
            ->whereIn('products.location', ['loja', 'ambos'])
            ->where('products.stock', '>', 0)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                'products.stock',
                'products.image_url'
            )
            ->selectRaw('sum(order_items.quantity) as total_sold')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price', 'products.stock', 'products.image_url')
            ->orderByDesc('total_sold')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Produtos com estoque baixo (alerta para operador)
     * GET /api/v1/pdv/products/low-stock
     */
    public function lowStock(): JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('location', ['loja', 'ambos'])
            ->whereColumn('stock', '<=', 'stock_min')
            ->select('id', 'name', 'sku', 'stock', 'stock_min')
            ->orderBy('stock')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'meta' => [
                'total' => $products->count(),
            ],
        ]);
    }
}