<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            
            $table->uuid('id')->primary();
            
            // Identificação
            $table->string('device_id')->unique(); // UUID do dispositivo
            $table->string('name'); // Nome amigável
            $table->enum('type', ['pdv', 'totem', 'kds', 'mobile'])->default('pdv');
            
            // Dados do dispositivo
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->text('hardware_info')->nullable();
            
            // Localização (opcional)
            $table->string('location')->nullable(); // "Cozinha", "Recepção", etc
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            
            // Sincronização
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('pending_sync_count')->default(0);
            
            // Configurações específicas
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('device_id');
            $table->index(['type', 'is_active']);
            $table->index('last_seen_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
