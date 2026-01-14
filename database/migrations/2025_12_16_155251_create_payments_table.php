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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            
            // Método de pagamento
            $table->enum('method', [
                'saldo_filho',   // Saldo interno do filho
                'dinheiro',      // Dinheiro
                'pix',           // PIX
                'credito',       // Cartão de crédito
                'debito',        // Cartão de débito
                'voucher',       // Vale/Voucher
            ]);
            
            // Valores
            $table->decimal('amount', 10, 2);
            $table->decimal('change_amount', 10, 2)->default(0); // Troco (dinheiro)
            
            // Status
            $table->enum('status', [
                'pending',
                'authorized',
                'confirmed',
                'failed',
                'refunded'
            ])->default('pending');
            
            // Dados do gateway (cartão/PIX)
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_name')->nullable();
            $table->json('gateway_response')->nullable();
            
            // Dados de cartão (quando aplicável)
            $table->string('card_last_digits', 4)->nullable();
            $table->string('card_brand')->nullable(); // Visa, Master, etc
            $table->integer('installments')->default(1);
            
            // PIX
            $table->string('pix_key')->nullable();
            $table->string('pix_qrcode')->nullable();
            
            // Timestamps
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['order_id', 'status']);
            $table->index('gateway_transaction_id');
            $table->index(['method', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
