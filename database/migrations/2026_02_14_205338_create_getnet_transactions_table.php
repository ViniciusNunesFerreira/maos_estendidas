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
        Schema::create('getnet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relacionamentos
            $table->foreignUuid('payment_intent_id')->constrained('payment_intents')->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('point_device_id')->nullable()->constrained('point_devices')->nullOnDelete();
            
            // Identificadores Getnet
            $table->string('terminal_id')->comment('ID do terminal Getnet');
            $table->string('payment_id')->nullable()->unique()->comment('ID único do pagamento na Getnet');
            $table->string('seller_id')->nullable()->comment('ID do seller/loja');
            $table->string('terminal_serial_number')->nullable();
            
            // Dados da transação
            $table->enum('payment_method', ['pix', 'credit_card', 'debit_card'])->default('credit_card');
            $table->decimal('amount', 10, 2)->comment('Valor da transação em reais');
            $table->string('currency', 3)->default('BRL');
            $table->integer('installments')->default(1);
            
            // Status e rastreamento
            $table->enum('status', [
                'created',
                'pending',
                'waiting_terminal',
                'processing',
                'approved',
                'denied',
                'cancelled',
                'error',
                'timeout'
            ])->default('created');
            
            $table->string('status_detail')->nullable();
            $table->string('denial_reason')->nullable();
            
            // Dados de autorização
            $table->string('nsu')->nullable()->comment('Número Sequencial Único');
            $table->string('authorization_code')->nullable()->comment('Código de autorização da transação');
            $table->string('acquirer_transaction_id')->nullable();
            $table->string('terminal_nsu')->nullable();
            
            // Dados do cartão (quando aplicável)
            $table->string('card_brand')->nullable()->comment('Bandeira: Visa, Master, etc');
            $table->string('card_last_digits', 4)->nullable();
            $table->string('card_holder_name')->nullable();
            
            // Dados PIX (quando aplicável)
            $table->text('pix_qr_code')->nullable();
            $table->text('pix_qr_code_base64')->nullable();
            $table->string('pix_txid')->nullable()->comment('Transaction ID do PIX');
            $table->timestamp('pix_expiration')->nullable();
            
            // Request/Response da API
            $table->json('api_request')->nullable()->comment('Payload enviado para Getnet');
            $table->json('api_response')->nullable()->comment('Resposta da Getnet');
            $table->json('webhook_payload')->nullable()->comment('Payload recebido via webhook');
            
            // Controle de tentativas
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            
            // Timestamps de ciclo de vida
            $table->timestamp('sent_to_terminal_at')->nullable()->comment('Quando foi enviado para o terminal');
            $table->timestamp('terminal_received_at')->nullable()->comment('Quando o terminal recebeu');
            $table->timestamp('customer_interaction_at')->nullable()->comment('Quando cliente interagiu');
            $table->timestamp('processed_at')->nullable()->comment('Quando foi processado');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('denied_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Metadados
            $table->string('pdv_device_id')->nullable()->comment('ID do PDV que originou');
            $table->string('operator_name')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Auditoria
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('terminal_id');
            $table->index('payment_id');
            $table->index('status');
            $table->index('nsu');
            $table->index('authorization_code');
            $table->index(['order_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('getnet_transactions');
    }
};