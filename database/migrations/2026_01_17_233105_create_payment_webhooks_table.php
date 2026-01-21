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
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Identificação
            $table->string('gateway')->default('mercadopago'); // mercadopago, pagseguro, etc
            $table->string('event_type'); // payment.created, payment.updated, etc
            $table->string('action')->nullable(); // payment.created, merchant_order.updated, etc
            
            // IDs relacionados
            $table->string('mp_payment_id')->nullable();
            $table->string('mp_merchant_order_id')->nullable();
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('payment_intent_id')->nullable()
                  ->constrained('payment_intents')->nullOnDelete();
            
            // Payload recebido
            $table->json('payload'); // Payload completo do webhook
            $table->json('headers')->nullable(); // Headers HTTP recebidos
            
            // Validação
            $table->boolean('signature_valid')->default(false); // Se a signature foi validada
            $table->string('signature')->nullable(); // Signature recebida
            $table->string('ip_address')->nullable(); // IP de origem
            
            // Status do processamento
            $table->enum('status', [
                'received',     // Recebido
                'processing',   // Processando
                'processed',    // Processado com sucesso
                'failed',       // Falhou ao processar
                'ignored'       // Ignorado (duplicado, inválido, etc)
            ])->default('received');
            
            $table->text('processing_error')->nullable(); // Erro ao processar
            $table->timestamp('processed_at')->nullable(); // Quando foi processado
            
            // Retry
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('event_type');
            $table->index('mp_payment_id');
            $table->index('status');
            $table->index(['created_at', 'status']);
            $table->index('next_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};