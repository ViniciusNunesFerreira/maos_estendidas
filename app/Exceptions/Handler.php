<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Lista de inputs que nunca devem ser incluídos em exceptions
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'card_token',
        'security_code',
        'cvv',
    ];

    /**
     * Registrar callbacks de tratamento de exceptions
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Tratar MercadoPagoException
        $this->renderable(function (MercadoPagoException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'mercado_pago_error',
                    'message' => $e->getMessage(),
                    'mp_error_code' => $e->getMpErrorCode(),
                    'http_status' => $e->getHttpStatus(),
                ], $e->getCode() ?: 500);
            }

            return response()->view('errors.mercado-pago', [
                'exception' => $e,
            ], $e->getCode() ?: 500);
        });

        // Tratar InsufficientBalanceException
        $this->renderable(function (InsufficientBalanceException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    ...$e->toArray(),
                ], 422);
            }

            return response()->view('errors.insufficient-balance', [
                'exception' => $e,
            ], 422);
        });

        // Tratar PaymentException
        $this->renderable(function (PaymentException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    ...$e->toArray(),
                ], $e->getCode() ?: 422);
            }

            return response()->view('errors.payment', [
                'exception' => $e,
            ], $e->getCode() ?: 422);
        });
    }

    /**
     * Renderizar exception para HTTP response
     */
    public function render($request, Throwable $e)
    {
        // Se for API request, sempre retornar JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderJsonException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Renderizar exception como JSON
     */
    protected function renderJsonException(Request $request, Throwable $e): JsonResponse
    {
        // Validação
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'error' => 'validation_error',
                'message' => 'Os dados fornecidos são inválidos',
                'errors' => $e->errors(),
            ], 422);
        }

        // Not Found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Recurso não encontrado',
            ], 404);
        }

        // MercadoPago Exception
        if ($e instanceof MercadoPagoException) {
            return response()->json([
                'success' => false,
                'error' => 'mercado_pago_error',
                'message' => $e->getMessage(),
                'mp_error_code' => $e->getMpErrorCode(),
            ], $e->getCode() ?: 500);
        }

        // Insufficient Balance
        if ($e instanceof InsufficientBalanceException) {
            return response()->json([
                'success' => false,
                ...$e->toArray(),
            ], 422);
        }

        // Payment Exception
        if ($e instanceof PaymentException) {
            return response()->json([
                'success' => false,
                ...$e->toArray(),
            ], $e->getCode() ?: 422);
        }

        // Erro genérico
        $statusCode = method_exists($e, 'getStatusCode') 
            ? $e->getStatusCode() 
            : 500;

        $message = app()->environment('production') 
            ? 'Erro interno do servidor' 
            : $e->getMessage();

        return response()->json([
            'success' => false,
            'error' => 'server_error',
            'message' => $message,
            ...(app()->environment('local') ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ] : []),
        ], $statusCode);
    }
}
