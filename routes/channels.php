<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\PaymentIntent;

/*
|--------------------------------------------------------------------------
| Broadcast Channels - Maos estendidas
|--------------------------------------------------------------------------
|
| Canais de broadcasting para notificações em tempo real
| Tecnologia: Laravel Echo + Pusher/Soketi
|
*/

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// =====================================================
// CANAL DO USUÁRIO (Privado)
// =====================================================

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// =====================================================
// CANAL DO FILHO (Privado)
// =====================================================

Broadcast::channel('filho.{filhoId}', function ($user, $filhoId) {
    return $user->filho && $user->filho->id === $filhoId;
});

// =====================================================
// CANAL DE PEDIDOS DO PDV (Privado)
// =====================================================

Broadcast::channel('pdv.{deviceId}', function ($user, $deviceId) {
    return $user->hasRole(['admin', 'operator']) && 
           $user->device_id === $deviceId;
});

// =====================================================
// CANAL DE PEDIDOS DO TOTEM (Presença)
// =====================================================

Broadcast::channel('totem.{totemId}', function ($user, $totemId) {
    if ($user->hasRole('filho')) {
        return [
            'id' => $user->id,
            'name' => $user->filho->name ?? $user->name,
        ];
    }
    
    return false;
});

// =====================================================
// CANAL DE NOTIFICAÇÕES ADMIN (Presença)
// =====================================================

Broadcast::channel('admin-dashboard', function ($user) {
    if ($user->hasRole(['admin', 'manager'])) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->roles->first()->name ?? 'user',
        ];
    }
    
    return false;
});

// =====================================================
// CANAL DE ALERTAS DE ESTOQUE (Privado)
// =====================================================

Broadcast::channel('stock-alerts', function ($user) {
    return $user->hasAnyRole(['admin', 'manager', 'stock']);
});

// =====================================================
// CANAL DE FATURAS (Privado)
// =====================================================

Broadcast::channel('invoices.{filhoId}', function ($user, $filhoId) {
    // Filho pode ver suas próprias faturas
    if ($user->filho && $user->filho->id === $filhoId) {
        return true;
    }
    
    // Admin pode ver todas
    return $user->hasRole(['admin', 'manager', 'financial']);
});


// =====================================================
// CANAL DE WEBHOOK PAGAMENTOS (Privado)
// =====================================================


Broadcast::channel('payment.{paymentIntentId}', function ($user, $paymentIntentId) {
    $intent = PaymentIntent::find($paymentIntentId);

    \Log::debug((array) $intent);
    
    if (!$intent) return false;

    \Log::info('user_id='.$user->id);


    return true;

    // Se for Order
    if ($intent->order_id && $intent->order->filho->user_id === $user->id) {
        return true;
    }
    
    // Se for Invoice
    if ($intent->invoice_id && $intent->invoice->filho->user_id === $user->id) {
        return true;
    }

     \Log::info('Não atendeu nenhum dos requisitos e retornou false no broadcast');

    return false; 
});

// =====================================================
// EVENTOS DISPONÍVEIS
// =====================================================

/*
 * Eventos que podem ser ouvidos nos canais:
 * 
 * Canal: user.{userId}
 * - NotificationReceived
 * - ProfileUpdated
 * 
 * Canal: filho.{filhoId}
 * - InvoiceGenerated
 * - InvoiceOverdue
 * - CreditAdjusted
 * - OrderStatusChanged
 * - SubscriptionRenewed
 * 
 * Canal: pdv.{deviceId}
 * - OrderCreated
 * - PaymentProcessed
 * - SyncRequired
 * 
 * Canal: kds
 * - NewOrderReceived
 * - OrderStatusChanged
 * - OrderCancelled
 * - OrderPriorityChanged
 * 
 * Canal: totem.{totemId}
 * - OrderConfirmed
 * - PaymentProcessed
 * 
 * Canal: admin-dashboard
 * - LowStockAlert
 * - InvoiceOverdueAlert
 * - SystemNotification
 * - UserActivity
 * 
 * Canal: stock-alerts
 * - ProductBelowMinimum
 * - ProductOutOfStock
 * - StockMovementCreated
 * 
 * Canal: invoices.{filhoId}
 * - InvoiceGenerated
 * - InvoiceStatusChanged
 * - PaymentReceived
 */