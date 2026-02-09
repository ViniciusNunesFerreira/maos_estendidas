<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Http\Controllers\Controller;
use App\Http\Resources\FilhoResource;
use App\Models\Filho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilhoController extends Controller
{
    /**
     * Buscar filho por CPF ou código
     * GET /api/v1/pdv/filhos/search
     */
    public function search(Request $request): JsonResponse
    {

        $query = $request->query('query');

        if (strlen($query) < 5) return response()->json([]);

        // Limpar CPF se for numérico
        $cleanQuery = preg_replace('/\D/', '', $query);

        $filhos = Filho::query()
                ->select(['id','user_id', 'cpf', 'credit_limit', 'credit_used', 'is_blocked_by_debt','block_reason', 'status'])
                ->with(['user' => function($q) {
                            $q->select(['id', 'name', 'email']); // Projeção no filho também!
                        }]) // Eager loading essencial
                ->where(function($mainQuery) use ($cleanQuery, $query) {
                    // Agrupamos os ORs para não quebrar a lógica do status = active
                    $mainQuery->where('cpf', 'ilike', "%{$cleanQuery}%")
                            ->orWhereHas('user', function($q) use ($query) {
                                $q->where('name', 'ilike', "%{$query}%")
                                    ->orWhere('email', 'ilike', "%{$query}%");
                            });
                })
                ->where('status', 'active')
                ->limit(5)
                ->get();

        if (!$filhos) {
            return response()->json([
                'success' => false,
                'message' => 'Filho não encontrado ou inativo',
            ], 404);
        }

        $dataMapped = $filhos->map(function($filho) {
                        return [
                            'id'                => $filho->id,
                            'full_name'         => $filho->full_name, // O Laravel resolve o Attribute aqui
                            'cpf_formatted'     => $filho->cpf_formatted,
                            'credit_limit'      => (float) $filho->credit_limit,
                            'credit_used'       => (float) $filho->credit_used,
                            'credit_available'  => (float) ($filho->credit_available),
                            'is_blocked_by_debt'=> (bool) $filho->is_blocked_by_debt,
                            'block_reason'      => $filho->block_reason,
                            'photo_url'         => $filho->photo_url
                        ];
                    });

        return response()->json([
            'success' => true,
            'data' => $dataMapped
        ]);
    }

    /**
     * Verificar crédito disponível
     * GET /api/v1/pdv/filhos/{filho}/credit
     */
    public function checkCredit(Filho $filho): JsonResponse
    {
        if ($filho->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Filho inativo',
                'data' => [
                    'can_purchase' => false,
                    'reason' => 'inactive',
                ],
            ]);
        }

        if ($filho->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Filho bloqueado por faturas em atraso',
                'data' => [
                    'can_purchase' => false,
                    'reason' => 'blocked',
                    'overdue_invoices' => $filho->invoices()->where('status', 'overdue')->count(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'can_purchase' => true,
                'credit_limit' => $filho->credit_limit,
                'credit_used' => $filho->credit_used,
                'credit_available' => $filho->credit_available,
                'current_month_spending' => $filho->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total'),
            ],
        ]);
    }

    /**
     * Buscar filho por QR Code
     * GET /api/v1/pdv/filhos/qrcode/{code}
     */
    public function findByQrCode(string $code): JsonResponse
    {
        $filho = Filho::where('qr_code', $code)
            ->where('status', 'active')
            ->first();

        if (!$filho) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code inválido ou filho inativo',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $filho->id,
                'name' => $filho->name,
                'cpf' => $filho->cpf_masked,
                'photo_url' => $filho->photo_url,
                'credit_available' => $filho->credit_available,
                'is_blocked' => $filho->is_blocked,
            ],
        ]);
    }

    /**
     * Listar filhos ativos (para autocomplete)
     * GET /api/v1/pdv/filhos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Filho::query()
            ->where('status', 'active')
            ->select('id', 'name', 'cpf', 'photo_url', 'credit_available', 'is_blocked');

        if ($request->filled('search')) {
            $search = $request->search;
            $cleanSearch = preg_replace('/\D/', '', $search);
            
            $query->where(function ($q) use ($search, $cleanSearch) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$cleanSearch}%");
            });
        }

        $filhos = $query->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($filho) {
                return [
                    'id' => $filho->id,
                    'name' => $filho->name,
                    'cpf' => $filho->cpf_masked,
                    'photo_url' => $filho->photo_url,
                    'credit_available' => $filho->credit_available,
                    'is_blocked' => $filho->is_blocked,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $filhos,
        ]);
    }

    /**
     * Histórico recente de compras do filho (para o PDV)
     * GET /api/v1/pdv/filhos/{filho}/orders
     */
    public function recentOrders(Filho $filho): JsonResponse
    {
        $orders = $filho->orders()
            ->with(['items.product:id,name,price'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                    'status' => $order->status,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Validar se filho pode comprar valor específico
     * POST /api/v1/pdv/filhos/{filho}/validate-purchase
     */
    public function validatePurchase(Request $request, Filho $filho): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = $request->amount;

        // Verificar status
        if ($filho->status !== 'active') {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Filho inativo',
                'reason' => 'inactive',
            ]);
        }

        // Verificar bloqueio
        if ($filho->is_blocked) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Filho bloqueado por faturas em atraso',
                'reason' => 'blocked',
            ]);
        }

        // Verificar crédito
        if ($filho->credit_available < $amount) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Crédito insuficiente',
                'reason' => 'insufficient_credit',
                'data' => [
                    'credit_available' => $filho->credit_available,
                    'amount_requested' => $amount,
                    'difference' => $amount - $filho->credit_available,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Compra autorizada',
            'data' => [
                'credit_available' => $filho->credit_available,
                'credit_after_purchase' => $filho->credit_available - $amount,
            ],
        ]);
    }
}