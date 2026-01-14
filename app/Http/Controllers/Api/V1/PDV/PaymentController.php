<?php
// app/Http/Controllers/Api/V1/PDV/PaymentController.php

namespace App\Http\Controllers\Api\V1\PDV;

use App\DTOs\Payment\PaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        if ($order->isPaid()) {
            return response()->json([
                'message' => 'Pedido já foi pago',
            ], 422);
        }

        try {
            $dto = PaymentDTO::fromRequest($request);
            $payment = $this->paymentService->process($order, $dto);

            return response()->json(new PaymentResource($payment));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}