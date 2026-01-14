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
        Schema::create('sync_queues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Identificação do dispositivo
            $table->string('device_id')->index();
            $table->string('device_name')->nullable();
            
            // Dados da operação
            $table->string('entity_type'); // 'order', 'payment', etc
            $table->uuid('entity_id')->nullable();
            $table->string('operation'); // 'create', 'update', 'delete'
            $table->json('payload');
            
            // UUID único para deduplicação
            $table->string('sync_uuid')->unique();
            
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed'
            ])->default('pending');
            
            // Controle de tentativas
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['device_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('sync_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queues');
    }
};
