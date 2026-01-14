<?php

namespace App\Http\Controllers\Api\V1\KDS;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Events\OrderStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Listar pedidos pendentes (aguardando preparo)
     * 
     * GET /api/v1/kds/orders/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('status', 'confirmed')
            ->whereHas('items', function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('location', 'cantina'); // Apenas produtos da cantina
                });
            })
            ->with([
                'items.product',
                'filho',
                'createdBy',
            ])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'count' => $orders->count(),
        ]);
    }

    /**
     * Listar pedidos em preparo
     * 
     * GET /api/v1/kds/orders/preparing
     */
    public function preparing(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('status', 'preparing')
            ->whereHas('items', function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('location', 'cantina');
                });
            })
            ->with([
                'items.product',
                'filho',
                'createdBy',
            ])
            ->orderBy('preparation_started_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'count' => $orders->count(),
        ]);
    }

    /**
     * Listar pedidos prontos (aguardando retirada)
     * 
     * GET /api/v1/kds/orders/ready
     */
    public function ready(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('status', 'ready')
            ->whereHas('items', function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('location', 'cantina');
                });
            })
            ->with([
                'items.product',
                'filho',
                'createdBy',
            ])
            ->orderBy('preparation_completed_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'count' => $orders->count(),
        ]);
    }

    /**
     * Iniciar preparo de um pedido
     * 
     * POST /api/v1/kds/orders/{order}/start
     */
    public function startPreparing(Order $order, Request $request): JsonResponse
    {
        try {
            // Verificar se o pedido pode ser preparado
            if ($order->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido não pode ser iniciado. Status atual: ' . $order->status,
                ], 422);
            }

            DB::beginTransaction();

            // Atualizar status
            $order->update([
                'status' => 'preparing',
                'preparation_started_at' => now(),
                'prepared_by_user_id' => $request->user()->id,
            ]);

            // Registrar no audit log
            activity()
                ->performedOn($order)
                ->causedBy($request->user())
                ->withProperties(['status' => 'preparing'])
                ->log('Pedido iniciado no KDS');

            // Disparar evento
            event(new OrderStatusChanged($order, 'confirmed', 'preparing'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preparo iniciado com sucesso',
                'data' => new OrderResource($order->fresh([
                    'items.product',
                    'filho',
                    'createdBy',
                    'preparedBy',
                ])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar preparo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar pedido como pronto
     * 
     * POST /api/v1/kds/orders/{order}/complete
     */
    public function markReady(Order $order, Request $request): JsonResponse
    {
        try {
            // Verificar se o pedido está sendo preparado
            if ($order->status !== 'preparing') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido não está sendo preparado. Status atual: ' . $order->status,
                ], 422);
            }

            DB::beginTransaction();

            // Atualizar status
            $order->update([
                'status' => 'ready',
                'preparation_completed_at' => now(),
            ]);

            // Calcular tempo de preparo
            $preparationTime = $order->preparation_started_at
                ? now()->diffInMinutes($order->preparation_started_at)
                : null;

            $order->update(['preparation_time_minutes' => $preparationTime]);

            // Registrar no audit log
            activity()
                ->performedOn($order)
                ->causedBy($request->user())
                ->withProperties([
                    'status' => 'ready',
                    'preparation_time' => $preparationTime,
                ])
                ->log('Pedido marcado como pronto no KDS');

            // Disparar evento
            event(new OrderStatusChanged($order, 'preparing', 'ready'));

            // Notificar o cliente (se for pedido do app/totem)
            if ($order->filho_id) {
                // Enviar notificação push
                // TODO: Implementar notificação push
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como pronto',
                'data' => new OrderResource($order->fresh([
                    'items.product',
                    'filho',
                    'createdBy',
                    'preparedBy',
                ])),
                'preparation_time_minutes' => $preparationTime,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar pedido como pronto: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reportar atraso em um pedido
     * 
     * POST /api/v1/kds/orders/{order}/delay
     */
    public function reportDelay(Order $order, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'estimated_delay_minutes' => 'required|integer|min:1|max:180',
        ]);

        try {
            DB::beginTransaction();

            // Atualizar pedido
            $order->update([
                'delay_reason' => $request->reason,
                'estimated_delay_minutes' => $request->estimated_delay_minutes,
                'delayed_at' => now(),
            ]);

            // Registrar no audit log
            activity()
                ->performedOn($order)
                ->causedBy($request->user())
                ->withProperties([
                    'delay_reason' => $request->reason,
                    'estimated_delay' => $request->estimated_delay_minutes,
                ])
                ->log('Atraso reportado no KDS');

            // Notificar o cliente sobre o atraso
            if ($order->filho_id) {
                // TODO: Enviar notificação sobre atraso
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Atraso reportado com sucesso',
                'data' => new OrderResource($order->fresh([
                    'items.product',
                    'filho',
                ])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao reportar atraso: ' . $e->getMessage(),
            ], 500);
        }
    }
}