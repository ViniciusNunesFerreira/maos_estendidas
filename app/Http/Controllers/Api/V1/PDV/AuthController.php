<?php

namespace App\Http\Controllers\Api\V1\PDV;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuthController extends Controller
{
    /**
     * Login no PDV
     * Apenas admin, manager e operator podem fazer login
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Verificar credenciais
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Verificar se usuário está ativo
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Sua conta está inativa. Contate o administrador.'],
            ]);
        }

        // ✅ CORREÇÃO: Usar Spatie hasAnyRole() em vez de in_array()
        if (!$user->hasAnyRole(['admin', 'manager', 'operator'])) {
            throw ValidationException::withMessages([
                'email' => ['Você não tem permissão para acessar o PDV.'],
            ]);
        }

        // Revogar tokens antigos do mesmo dispositivo
        $user->tokens()
            ->where('name', $request->device_name)
            ->delete();

        // ✅ CORREÇÃO: Obter abilities baseado nas permissions Spatie
        $abilities = $this->getUserAbilities($user);

        // Criar novo token com expiração de 24 horas
        $token = $user->createToken(
            name: $request->device_name,
            abilities: $abilities,
            expiresAt: now()->addHours(24)
        );

        // Log de atividade

        if (method_exists($user, 'auditLogs')) {
            
            AuditLog::create([
                'user_id' => $user->id,
                'auditable_type' => get_class($user),
                'auditable_id' => $user->id,
                'action' => 'Login PDV',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

        }

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(), // ✅ CORREÇÃO: Array de roles do Spatie
                    'avatar_url' => $user->avatar_url,
                ],
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
                'permissions' => $this->getUserPermissions($user), // ✅ CORREÇÃO: Permissions dinâmicas
            ],
        ], 200);
    }

    /**
     * Obter dados do usuário autenticado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(), // ✅ Spatie
                    'avatar_url' => $user->avatar_url,
                ],
                'permissions' => $this->getUserPermissions($user),
                'device_info' => [
                    'current_device' => $request->user()->currentAccessToken()->name,
                    'token_expires_at' => $request->user()->currentAccessToken()->expires_at?->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Logout do PDV
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revogar token atual
        $request->user()->currentAccessToken()->delete();

        // Log de atividade
        activity()
            ->causedBy($request->user())
            ->log('Logout PDV realizado');

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ], 200);
    }

    /**
     * ✅ NOVO: Obter abilities do usuário baseado em suas permissions Spatie
     *
     * @param User $user
     * @return array
     */
    private function getUserAbilities(User $user): array
    {
        // Se for admin, retorna todas
        if ($user->hasRole('admin')) {
            return ['*'];
        }

        // Retornar permissions do Spatie como abilities
        return $user->getAllPermissions()
            ->pluck('name')
            ->toArray();
    }

    /**
     * ✅ NOVO: Obter permissions detalhadas usando Spatie $user->can()
     *
     * @param User $user
     * @return array
     */
    private function getUserPermissions(User $user): array
    {
        return [
            'can_create_orders' => $user->can('orders.create'),
            'can_cancel_orders' => $user->can('orders.cancel'),
            'can_give_discounts' => $user->can('pdv.give-discount'),
            'can_override_limits' => $user->can('pdv.override-limit'),
            'can_view_products' => $user->can('products.view'),
            'can_manage_products' => $user->can('products.edit') || $user->can('products.create'),
            'can_view_filhos' => $user->can('filhos.view'),
            'can_manage_filhos' => $user->can('filhos.edit') || $user->can('filhos.create'),
            'max_discount_percent' => $this->getMaxDiscountPercent($user),
            'roles' => $user->getRoleNames()->toArray(),
            'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }

    /**
     * ✅ NOVO: Obter porcentagem máxima de desconto por role
     *
     * @param User $user
     * @return int
     */
    private function getMaxDiscountPercent(User $user): int
    {
        return match (true) {
            $user->hasRole('admin') => 100,
            $user->hasRole('manager') => 50,
            $user->hasRole('operator') => 10,
            default => 0,
        };
    }

    /**
     * Refresh do token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        $deviceName = $currentToken->name;

        // Revogar token atual
        $currentToken->delete();

        // Criar novo token
        $newToken = $user->createToken(
            name: $deviceName,
            abilities: $this->getUserAbilities($user),
            expiresAt: now()->addHours(24)
        );

        return response()->json([
            'success' => true,
            'message' => 'Token renovado com sucesso',
            'data' => [
                'token' => $newToken->plainTextToken,
                'expires_at' => $newToken->accessToken->expires_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Validar token atual
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'expires_at' => $token->expires_at?->toIso8601String(),
                'device_name' => $token->name,
                'user' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                ],
            ],
        ], 200);
    }
}