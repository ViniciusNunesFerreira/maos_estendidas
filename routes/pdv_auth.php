<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PDV\AuthController as PDVAuthController;

/*
|--------------------------------------------------------------------------
| PDV Authentication Routes
|--------------------------------------------------------------------------
|
| Rotas de autenticação específicas para o módulo PDV Desktop (Tauri + React)
| Apenas operadores e administrativos podem fazer login no PDV
| Roles permitidas: admin, manager, operator
|
*/

Route::prefix('v1/pdv/auth')->name('pdv.auth.')->group(function () {
    
    // =====================================================
    // ROTAS PÚBLICAS (Sem Autenticação)
    // =====================================================
    
    /**
     * Login no PDV
     * @method POST
     * @route /api/v1/pdv/auth/login
     * @body { email: string, password: string, device_name: string }
     * @response { user, token, expires_at, permissions }
     */
    Route::post('/login', [PDVAuthController::class, 'login'])
        ->middleware('throttle:5,1') // 5 tentativas por minuto
        ->name('login');
    
    // =====================================================
    // ROTAS AUTENTICADAS (Requer Token)
    // =====================================================
    
    Route::middleware('auth:sanctum')->group(function () {
        
        /**
         * Obter dados do usuário autenticado
         * @method GET
         * @route /api/v1/pdv/auth/me
         * @response { user, permissions, device_info }
         */
        Route::get('/me', [PDVAuthController::class, 'me'])
            ->name('me');
        
        /**
         * Logout do PDV
         * @method POST
         * @route /api/v1/pdv/auth/logout
         * @response { message }
         */
        Route::post('/logout', [PDVAuthController::class, 'logout'])
            ->name('logout');
        
        /**
         * Refresh token
         * @method POST
         * @route /api/v1/pdv/auth/refresh
         * @response { token, expires_at }
         */
        Route::post('/refresh', [PDVAuthController::class, 'refresh'])
            ->name('refresh');
        
        /**
         * Validar token atual
         * @method GET
         * @route /api/v1/pdv/auth/validate
         * @response { valid: boolean, expires_at, user }
         */
        Route::get('/validate', [PDVAuthController::class, 'validate'])
            ->name('validate');
    });
});