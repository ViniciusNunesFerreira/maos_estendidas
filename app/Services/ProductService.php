<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;

class ProductService
{
    /**
     * Criar produto
     */
    public function create(array $data): Product
    {
        $data['slug'] = Str::slug($data['name']);
        $data['sku'] = $data['sku'] ?? $this->generateSku($data['category_id'] ?? null);

        if (isset($data['image']) && $data['image']) {
            $data['image_url'] = $data['image']->store('products', 'public');
            unset($data['image']);
        }

        return Product::create($data);
    }

    /**
     * Atualizar produto
     */
    public function update(Product $product, array $data): Product
    {
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (isset($data['image']) && $data['image']) {
            // Remover imagem antiga
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $data['image_url'] = $data['image']->store('products', 'public');
            unset($data['image']);
        }

        $product->update($data);

        return $product;
    }

    /**
     * Remover produto
     */
    public function delete(Product $product): bool
    {
        // Remover imagem
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        return $product->delete();
    }

    /**
     * Gerar SKU único
     */
    public function generateSku(?string $categoryId = null): string
    {
        $prefix = 'PRD';
        
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $prefix = strtoupper(substr($category->slug ?? $category->name, 0, 3));
            }
        }

        $sequence = Product::where('sku', 'like', "{$prefix}-%")->count() + 1;
        
        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    

    /**
     * Importar produtos de arquivo
     */
    public function import($file): array
    {
        $import = new ProductsImport();
        Excel::import($import, $file);

        return [
            'imported' => $import->getImportedCount(),
            'updated' => $import->getUpdatedCount(),
            'errors' => $import->getErrors(),
        ];
    }

    /**
     * Exportar produtos
     */
    public function export(string $format = 'xlsx', ?array $filters = null): string
    {
        $filename = 'produtos_' . now()->format('Y-m-d_His') . '.' . $format;
        
        Excel::store(new ProductsExport($filters), $filename, 'public');
        
        return $filename;
    }

    /**
     * Gerar etiquetas PDF
     */
    public function generateLabels(array $productIds, string $format = 'pdf'): string
    {
        $products = Product::whereIn('id', $productIds)->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.product-labels', [
            'products' => $products
        ]);

        $filename = 'etiquetas_' . now()->format('Y-m-d_His') . '.pdf';
        
        Storage::disk('public')->put($filename, $pdf->output());
        
        return $filename;
    }

    /**
     * Gerar etiquetas ZPL (Zebra)
     */
    public function generateZplLabels(array $productIds): string
    {
        $products = Product::whereIn('id', $productIds)->get();
        $zpl = '';

        foreach ($products as $product) {
            $zpl .= "^XA\n";
            $zpl .= "^FO50,50^A0N,30,30^FD{$product->name}^FS\n";
            $zpl .= "^FO50,100^A0N,25,25^FDSKU: {$product->sku}^FS\n";
            $zpl .= "^FO50,140^BY2^BCN,80,Y,N,N^FD{$product->barcode}^FS\n";
            $zpl .= "^FO50,250^A0N,35,35^FDR\$ " . number_format($product->price, 2, ',', '.') . "^FS\n";
            $zpl .= "^XZ\n";
        }

        $filename = 'etiquetas_' . now()->format('Y-m-d_His') . '.zpl';
        Storage::disk('public')->put($filename, $zpl);
        
        return $filename;
    }

    /**
     * Buscar produtos
     */
    public function search(string $query, ?array $filters = null): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'ilike', "%{$query}%")
                  ->orWhere('sku', 'ilike', "%{$query}%")
                  ->orWhere('barcode', $query);
            })
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->limit(50)
            ->get();
    }

    /**
     * Obter estatísticas de produtos
     */
    public function getStatistics(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'out_of_stock' => Product::where('is_active', true)->where('stock_quantity', 0)->count(),
            'low_stock' => Product::where('is_active', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
                ->where('stock_quantity', '>', 0)
                ->count(),
            'by_type' => [
                'loja' => Product::where('is_active', true)->where('type', 'loja')->count(),
                'cantina' => Product::where('is_active', true)->where('type', 'cantina')->count(),
                'ambos' => Product::where('is_active', true)->where('type', 'ambos')->count(),
            ],
        ];
    }

    /**
     * Produtos mais vendidos
     */
    public function getTopSelling(int $days = 30, int $limit = 10): \Illuminate\Support\Collection
    {
        return \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.id', 'products.name', 'products.sku', 'products.image_url')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.image_url')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();
    }
}