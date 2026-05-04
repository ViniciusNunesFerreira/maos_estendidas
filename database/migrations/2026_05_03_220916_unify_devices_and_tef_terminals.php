<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. LIMPEZA DE DÉBITO TÉCNICO: Removemos a tabela dependente (abandonada) 
        Schema::dropIfExists('getnet_transactions');

        // 2. Removemos a tabela inútil e redundante
        Schema::dropIfExists('point_devices');

        // 3. Recriamos a tabela 'devices' com a estrutura unificada
        Schema::dropIfExists('devices');

        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // --- Identificação do Hardware Físico ---
            $table->string('name', 100)->comment('Ex: Totem 01 - Pátio Principal');
            $table->string('internal_id', 50)->unique()->comment('ID enviado pelo app Flutter (Ex: TOTEM-001)');
            $table->enum('type', ['kiosk', 'pos', 'kds'])->default('kiosk')->comment('Finalidade do hardware');
            
            // --- Pareamento TEF (A Mágica da Unificação) ---
            $table->string('tef_provider', 50)->nullable()->comment('mercadopago, getnet, stone');
            $table->string('tef_device_id', 150)->nullable()->comment('ID da máquina na operadora (Ex: PAX_A910__1234)');
            
            // --- Dados de Rede e Monitoramento (Melhoria de Infraestrutura) ---
            $table->ipAddress('ip_address')->nullable()->comment('IP local para direcionamento de impressão');
            $table->macAddress('mac_address')->nullable();
            $table->timestamp('last_ping_at')->nullable()->comment('Data do último sinal de vida (Health Check)');
            
            // --- Estado e Controle ---
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Essencial em produção para não quebrar relatórios antigos se um totem for aposentado
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};