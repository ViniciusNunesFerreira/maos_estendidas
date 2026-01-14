<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\Filho\FilhoService;
use Illuminate\Http\Request;
use App\Http\Requests\LoginByCpfRequest;
use App\Http\Requests\RequestPasswordResetRequest;
use App\Http\Requests\VerifyResetCodeRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\DTOs\CreateFilhoDTO;
use App\Http\Requests\Admin\StoreFilhoRequest;


class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private FilhoService $filhoService
    ) {}


    /**
     * Registro de Novo Cadastro (App/PWA)
     * POST /api/v1/auth/register
     */
    public function register(StoreFilhoRequest $request): JsonResponse
    {
        
        try {

            $dto = CreateFilhoDTO::fromRequest($request);

            $this->filhoService->create($dto);

            return response()->json([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso! Aguarde a aprovação do administrador para acessar.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao realizar cadastro.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Login via CPF + Senha
     */
    public function loginByCpf(LoginByCpfRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginByCpf(
                $request->cpf,
                $request->password,
                $request->device_info
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'data' => $result,
            ]);
        } catch (\App\Exceptions\InvalidCredentialsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        } catch (\App\Exceptions\AccountNotActiveException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode() ?: 'ACCOUNT_INACTIVE',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno no servidor.',
            ], 500);
        }
    }

    /**
     * Solicitar código de recuperação via SMS
     * 
     * POST /api/v1/auth/password/request-sms
     */
    public function requestPasswordResetSms(RequestPasswordResetRequest $request): JsonResponse
    {
        $this->authService->requestPasswordResetSms($request->cpf);
        
        return response()->json([
            'success' => true,
            'message' => 'Se o CPF estiver cadastrado, você receberá um SMS com o código de recuperação.',
        ]);
    }

    /**
     * Verificar código SMS
     * 
     * POST /api/v1/auth/password/verify-code
     */
    public function verifyResetCode(VerifyResetCodeRequest $request): JsonResponse
    {
        $valid = $this->authService->verifyResetCode(
            $request->cpf,
            $request->code
        );
        
        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido ou expirado',
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Código válido',
        ]);
    }

    /**
     * Resetar senha com código SMS
     * 
     * POST /api/v1/auth/password/reset-sms
     */
    public function resetPasswordWithSms(ResetPasswordRequest $request): JsonResponse
    {
        $success = $this->authService->resetPasswordWithSms(
            $request->cpf,
            $request->code,
            $request->password
        );
        
        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível resetar a senha. Código inválido ou expirado.',
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Senha alterada com sucesso. Faça login com a nova senha.',
        ]);
    }

    /**
     * Logout
     * 
     * POST /api/v1/auth/logout
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    private function getAbilitiesForRole(string $role): array
    {
        return match ($role) {
            'admin' => ['*'],
            'manager' => [
                'filhos:*',
                'products:*',
                'orders:*',
                'reports:view',
            ],
            'operator' => [
                'products:view',
                'orders:create',
                'orders:view',
                'payments:create',
                'filhos:view',
            ],
            'filho' => [
                'profile:view',
                'profile:update',
                'balance:view',
                'orders:create',
                'orders:view',
            ],
            default => [],
        };
    }
}