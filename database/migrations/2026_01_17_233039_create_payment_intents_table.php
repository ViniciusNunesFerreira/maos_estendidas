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
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relacionamentos
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            
            // Identificação Mercado Pago
            $table->string('mp_payment_id')->nullable()->unique(); // ID do payment no MP
            $table->string('mp_payment_intent_id')->nullable()->unique(); // ID do payment intent (Point)
            
            // Tipo de integração
            $table->enum('integration_type', ['checkout', 'point_tef', 'manual_pos']); // checkout - tef ou manual_pos
            $table->string('payment_method'); // pix, credit_card, debit_card
            
            // Valores
            $table->decimal('amount', 10, 2); // Valor solicitado
            $table->decimal('amount_paid', 10, 2)->nullable(); // Valor efetivamente pago
            
            // Status do Intent
            $table->enum('status', [
                'created',      // Intent criado
                'pending',      // Aguardando pagamento
                'processing',   // Processando (TEF)
                'approved',     // Aprovado
                'rejected',     // Rejeitado
                'cancelled',    // Cancelado
                'refunded',     // Estornado
                'error'         // Erro
            ])->default('created');
            
            $table->string('status_detail')->nullable(); // Detalhe do status (accredited, cc_rejected_bad_filled_card_number, etc)
            
            // Dados específicos PIX
            $table->text('pix_qr_code')->nullable();
            $table->text('pix_qr_code_base64')->nullable();
            $table->string('pix_ticket_url')->nullable();
            $table->timestamp('pix_expiration')->nullable();
            
            // Dados específicos TEF Point
            $table->string('tef_device_id')->nullable();
            $table->string('tef_external_reference')->nullable(); // Referência externa
            $table->boolean('tef_print_on_terminal')->default(false);
            
            // Dados específicos Cartão
            $table->string('card_token')->nullable(); // Token do cartão
            $table->string('card_last_digits', 4)->nullable();
            $table->string('card_brand')->nullable(); // visa, master, etc
            $table->integer('installments')->default(1);
            
            // Request/Response do MP
            $table->json('mp_request')->nullable(); // Request enviado ao MP
            $table->json('mp_response')->nullable(); // Response do MP
            
            // Controle de tentativas
            $table->integer('attempts')->default(0); // Tentativas de criação
            $table->text('last_error')->nullable(); // Último erro
            $table->timestamp('last_attempt_at')->nullable();
            
            // Timestamps importantes
            $table->timestamp('created_at_mp')->nullable(); // Quando foi criado no MP
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('mp_payment_id');
            $table->index('mp_payment_intent_id');
            $table->index(['order_id', 'status']);
            $table->index('integration_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};