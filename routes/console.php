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

Schedule::call(function () {
    // Gerar faturas de consumo mensais
    Artisan::call('casalar:generate-invoices');
})->monthlyOn(1, '00:00') // Todo dia 1 às 00:00
  ->name('generate-monthly-invoices')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Gerar faturas de assinatura
    Artisan::call('casalar:generate-subscription-invoices');
})->dailyAt('00:30') // Diariamente às 00:30
  ->name('generate-subscription-invoices')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Verificar faturas vencidas e bloquear filhos
    Artisan::call('casalar:check-overdue-invoices');
})->dailyAt('06:00') // Diariamente às 06:00
  ->name('check-overdue-invoices')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Enviar lembretes de faturas próximas do vencimento (3 dias antes)
    Artisan::call('casalar:send-invoice-reminders');
})->dailyAt('09:00') // Diariamente às 09:00
  ->name('send-invoice-reminders')
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
    // Backup automático do banco de dados
    Artisan::call('backup:run', ['--only-db' => true]);
})->dailyAt('02:00') // Diariamente às 02:00
  ->name('database-backup')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Backup completo (banco + arquivos)
    Artisan::call('backup:run');
})->weeklyOn(1, '03:00') // Toda segunda às 03:00
  ->name('full-backup')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Limpar backups antigos (manter últimos 30 dias)
    Artisan::call('backup:clean');
})->dailyAt('04:00') // Diariamente às 04:00
  ->name('cleanup-old-backups')
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

/*
// Processar fila de jobs
Schedule::call(function () {
    // Processar fila de jobs
    // Nota: Em produção, use supervisor para queue:work
    // Este é apenas um fallback
    Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--max-time' => 300, // 5 minutos
    ]);
})->everyMinute() // A cada minuto
  ->name('process-queue')
  ->withoutOverlapping()
  ->onOneServer()
  ->runInBackground();
  */

Schedule::call(function () {
    // Gerar relatórios automáticos mensais
    Artisan::call('casalar:generate-monthly-reports');
})->monthlyOn(1, '07:00') // Todo dia 1 às 07:00
  ->name('generate-monthly-reports')
  ->withoutOverlapping()
  ->onOneServer();

Schedule::call(function () {
    // Verificar e renovar tokens SAT que estão expirando
    Artisan::call('casalar:check-sat-tokens');
})->dailyAt('05:00') // Diariamente às 05:00
  ->name('check-sat-tokens')
  ->withoutOverlapping()
  ->onOneServer();

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