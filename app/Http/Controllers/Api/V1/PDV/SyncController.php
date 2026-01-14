<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Filho;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncController extends Controller
{
    public function __construct(
        private readonly SyncService $syncService,
        private readonly OrderService $orderService
    ) {}

    /**
     * Download completo de dados para cache local (PDV offline)
     * GET /api/v1/pdv/sync/download
     */
    public function download(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');
        $lastSync = $request->query('last_sync');

        // Produtos
        $productsQuery = Product::query()
            ->where('is_active', true)
            ->whereIn('location', ['loja', 'ambos'])
            ->with(['category:id,name,slug,icon,color']);

        if ($lastSync) {
            $productsQuery->where('updated_at', '>', Carbon::parse($lastSync));
        }

        $products = $productsQuery->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'price' => $p->price,
                'cost_price' => $p->cost_price,
                'stock' => $p->stock,
                'stock_min' => $p->stock_min,
                'image_url' => $p->image_url,
                'category_id' => $p->category_id,
                'category' => $p->category ? [
                    'id' => $p->category->id,
                    'name' => $p->category->name,
                    'icon' => $p->category->icon,
                    'color' => $p->category->color,
                ] : null,
                'updated_at' => $p->updated_at->toIso8601String(),
            ];
        });

        // Categorias
        $categoriesQuery = Category::query()->where('is_active', true);

        if ($lastSync) {
            $categoriesQuery->where('updated_at', '>', Carbon::parse($lastSync));
        }

        $categories = $categoriesQuery->orderBy('sort_order')->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => $c->icon,
                'color' => $c->color,
                'sort_order' => $c->sort_order,
                'updated_at' => $c->updated_at->toIso8601String(),
            ];
        });

        // Filhos ativos (para validação offline básica)
        $filhosQuery = Filho::query()
            ->where('status', 'active')
            ->select('id', 'name', 'cpf', 'qr_code', 'photo_url', 'credit_limit', 'credit_used', 'is_blocked', 'updated_at');

        if ($lastSync) {
            $filhosQuery->where('updated_at', '>', Carbon::parse($lastSync));
        }

        $filhos = $filhosQuery->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'cpf_masked' => $f->cpf_masked,
                'qr_code' => $f->qr_code,
                'photo_url' => $f->photo_url,
                'credit_limit' => $f->credit_limit,
                'credit_used' => $f->credit_used,
                'credit_available' => $f->credit_available,
                'is_blocked' => $f->is_blocked,
                'updated_at' => $f->updated_at->toIso8601String(),
            ];
        });

        // Registrar sync
        $this->syncService->registerDownload($deviceId, now());

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'categories' => $categories,
                'filhos' => $filhos,
            ],
            'meta' => [
                'synced_at' => now()->toIso8601String(),
                'products_count' => $products->count(),
                'categories_count' => $categories->count(),
                'filhos_count' => $filhos->count(),
                'is_full_sync' => !$lastSync,
            ],
        ]);
    }

    /**
     * Upload de pedidos criados offline
     * POST /api/v1/pdv/sync/upload
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.local_id' => 'required|string',
            'orders.*.filho_id' => 'nullable|uuid',
            'orders.*.guest_name' => 'nullable|string|max:255',
            'orders.*.guest_document' => 'nullable|string|max:20',
            'orders.*.items' => 'required|array|min:1',
            'orders.*.items.*.product_id' => 'required|uuid',
            'orders.*.items.*.quantity' => 'required|integer|min:1',
            'orders.*.items.*.unit_price' => 'required|numeric|min:0',
            'orders.*.total' => 'required|numeric|min:0',
            'orders.*.payment_method' => 'nullable|string',
            'orders.*.created_at_local' => 'required|date',
        ]);

        $deviceId = $request->header('X-Device-ID');
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        DB::beginTransaction();

        try {
            foreach ($request->orders as $orderData) {
                try {
                    // Verificar se já foi sincronizado (evitar duplicatas)
                    $existingOrder = Order::where('local_id', $orderData['local_id'])
                        ->where('device_id', $deviceId)
                        ->first();

                    if ($existingOrder) {
                        $results[] = [
                            'local_id' => $orderData['local_id'],
                            'status' => 'skipped',
                            'message' => 'Pedido já sincronizado',
                            'order_id' => $existingOrder->id,
                            'order_number' => $existingOrder->order_number,
                        ];
                        continue;
                    }

                    // Criar pedido
                    $order = $this->orderService->createFromSync([
                        'local_id' => $orderData['local_id'],
                        'device_id' => $deviceId,
                        'filho_id' => $orderData['filho_id'] ?? null,
                        'guest_name' => $orderData['guest_name'] ?? null,
                        'guest_document' => $orderData['guest_document'] ?? null,
                        'items' => $orderData['items'],
                        'total' => $orderData['total'],
                        'payment_method' => $orderData['payment_method'] ?? null,
                        'origin' => 'pdv',
                        'created_at_local' => $orderData['created_at_local'],
                        'operator_id' => auth()->id(),
                    ]);

                    $results[] = [
                        'local_id' => $orderData['local_id'],
                        'status' => 'success',
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ];
                    $successCount++;

                } catch (\Exception $e) {
                    $results[] = [
                        'local_id' => $orderData['local_id'],
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                    $errorCount++;
                }
            }

            DB::commit();

            // Registrar sync
            $this->syncService->registerUpload($deviceId, now(), $successCount, $errorCount);

            return response()->json([
                'success' => true,
                'data' => [
                    'results' => $results,
                    'summary' => [
                        'total' => count($request->orders),
                        'success' => $successCount,
                        'errors' => $errorCount,
                        'skipped' => count($request->orders) - $successCount - $errorCount,
                    ],
                ],
                'synced_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erro na sincronização',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar atualizações disponíveis (polling)
     * GET /api/v1/pdv/sync/check
     */
    public function check(Request $request): JsonResponse
    {
        $lastSync = $request->query('last_sync');

        if (!$lastSync) {
            return response()->json([
                'success' => true,
                'has_updates' => true,
                'message' => 'Sincronização inicial necessária',
            ]);
        }

        $lastSyncTime = Carbon::parse($lastSync);

        $hasProductUpdates = Product::where('updated_at', '>', $lastSyncTime)->exists();
        $hasCategoryUpdates = Category::where('updated_at', '>', $lastSyncTime)->exists();
        $hasFilhoUpdates = Filho::where('updated_at', '>', $lastSyncTime)->exists();

        $hasUpdates = $hasProductUpdates || $hasCategoryUpdates || $hasFilhoUpdates;

        return response()->json([
            'success' => true,
            'has_updates' => $hasUpdates,
            'updates' => [
                'products' => $hasProductUpdates,
                'categories' => $hasCategoryUpdates,
                'filhos' => $hasFilhoUpdates,
            ],
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Status de sincronização do device
     * GET /api/v1/pdv/sync/status
     */
    public function status(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');

        $syncHistory = $this->syncService->getDeviceHistory($deviceId, 10);
        $lastDownload = $this->syncService->getLastDownload($deviceId);
        $lastUpload = $this->syncService->getLastUpload($deviceId);
        $pendingOrders = Order::where('device_id', $deviceId)
            ->where('sync_status', 'pending')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $deviceId,
                'last_download' => $lastDownload?->toIso8601String(),
                'last_upload' => $lastUpload?->toIso8601String(),
                'pending_orders' => $pendingOrders,
                'history' => $syncHistory,
            ],
        ]);
    }

    /**
     * Atualização incremental de estoque
     * GET /api/v1/pdv/sync/stock
     */
    public function stockUpdates(Request $request): JsonResponse
    {
        $productIds = $request->query('product_ids');
        
        if ($productIds) {
            $ids = explode(',', $productIds);
            $products = Product::whereIn('id', $ids)
                ->select('id', 'stock', 'updated_at')
                ->get();
        } else {
            // Retorna todos os estoques atualizados nas últimas 5 minutos
            $products = Product::where('updated_at', '>=', now()->subMinutes(5))
                ->whereIn('location', ['loja', 'ambos'])
                ->select('id', 'stock', 'updated_at')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($p) {
                return [
                    'id' => $p->id,
                    'stock' => $p->stock,
                    'updated_at' => $p->updated_at->toIso8601String(),
                ];
            }),
            'fetched_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Forçar ressincronização completa
     * POST /api/v1/pdv/sync/reset
     */
    public function reset(Request $request): JsonResponse
    {
        $deviceId = $request->header('X-Device-ID');

        $this->syncService->resetDevice($deviceId);

        return response()->json([
            'success' => true,
            'message' => 'Sincronização resetada. Execute download completo.',
        ]);
    }
}