<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes - Casa Lar
|--------------------------------------------------------------------------
|
| Comandos Artisan e agendamentos do sistema
|
*/

// =====================================================
// COMANDOS CUSTOMIZADOS
// =====================================================

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();



// =====================================================
// AGENDAMENTOS (CRON JOBS)
// =====================================================

Schedule::command('orders:expire-reservations')->everyMinute();

Schedule::command('billing:process-renewals')
  ->dailyAt('01:00') // Diariamente às 1h da manhã
  ->name('generate-subscription-invoices')
  ->withoutOverlapping()
  ->appendOutputTo(storage_path('logs/billing_renewals.log'))
  ->onOneServer();



Schedule::command('billing:send-invoice-reminders');
  ->dailyAt('22:00') // Diariamente às 09:00
  ->name('send-invoice-reminders')
  ->withoutOverlapping()
  ->onOneServer();

/*
Schedule::call(function () {
    // Verificar faturas vencidas e bloquear filhos
    Artisan::call('casalar:check-overdue-invoices');
})->dailyAt('06:00') // Diariamente às 06:00
  ->name('check-overdue-invoices')
  ->withoutOverlapping()
  ->onOneServer();




Schedule::call(function () {
    // Enviar notificações de estoque baixo
    Artisan::call('casalar:check-stock-levels');
})->dailyAt('08:00') // Diariamente às 08:00
  ->name('check-stock-levels')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Limpar dados temporários antigos
    Artisan::call('casalar:cleanup-temp-data');
})->daily() // Diariamente
  ->name('cleanup-temp-data')
  ->withoutOverlapping()
  ->onOneServer();


Schedule::call(function () {
    // Limpar logs antigos (manter últimos 30 dias)
    Artisan::call('casalar:cleanup-logs');
})->weekly() // Semanalmente
  ->name('cleanup-old-logs')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Sincronizar dados offline pendentes (PDV/Totem)
    Artisan::call('casalar:sync-offline-data');
})->everyFiveMinutes() // A cada 5 minutos
  ->name('sync-offline-data')
  ->withoutOverlapping()
  ->onOneServer();

*/

// =====================================================
// COMANDOS DISPONÍVEIS
// =====================================================

/*
 * Comandos Artisan customizados do sistema:
 * 
 * php artisan casalar:generate-invoices
 * - Gera faturas de consumo mensais para todos os filhos
 * 
 * php artisan casalar:generate-subscription-invoices
 * - Gera faturas de assinatura para filhos elegíveis
 * 
 * php artisan casalar:check-overdue-invoices
 * - Verifica faturas vencidas e bloqueia filhos inadimplentes
 * 
 * php artisan casalar:send-invoice-reminders
 * - Envia lembretes de faturas próximas do vencimento
 * 
 * php artisan casalar:check-stock-levels
 * - Verifica níveis de estoque e envia alertas
 * 
 * php artisan casalar:cleanup-temp-data
 * - Limpa dados temporários antigos (tokens expirados, sessões antigas)
 * 
 * php artisan casalar:cleanup-logs
 * - Remove logs antigos (>30 dias)
 * 
 * php artisan casalar:sync-offline-data
 * - Sincroniza dados offline pendentes do PDV/Totem
 * 
 * php artisan casalar:generate-monthly-reports
 * - Gera relatórios automáticos mensais
 * 
 * php artisan casalar:check-sat-tokens
 * - Verifica e renova tokens SAT expirando
 * 
 * php artisan casalar:seed-demo-data
 * - Popula banco com dados de demonstração (apenas dev)
 * 
 * php artisan casalar:clear-all-cache
 * - Limpa todos os caches (config, routes, views, application)
 * 
 * php artisan casalar:install
 * - Assistente de instalação do sistema
 * 
 * php artisan casalar:update
 * - Executa migrations e otimizações após update
 */

// =====================================================
// MONITORAMENTO DE JOBS FALHADOS
// =====================================================

Schedule::call(function () {
    // Notificar admins sobre jobs falhados
    Artisan::call('casalar:notify-failed-jobs');
})->hourly() // A cada hora
  ->name('notify-failed-jobs')
  ->onOneServer();

// =====================================================
// HEALTH CHECKS
// =====================================================

Schedule::call(function () {
    // Verificar saúde do sistema
    Artisan::call('casalar:health-check');
})->everyFifteenMinutes() // A cada 15 minutos
  ->name('system-health-check')
  ->onOneServer();