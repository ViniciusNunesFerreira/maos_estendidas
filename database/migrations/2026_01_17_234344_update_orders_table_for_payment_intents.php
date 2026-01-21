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
        Schema::table('orders', function (Blueprint $table) {
            // Relacionamento com payment intent
            $table->foreignUuid('payment_intent_id')
                  ->nullable()
                  ->after('invoice_id')
                  ->constrained('payment_intents')
                  ->nullOnDelete();
            
            // Flag para indicar se aguarda pagamento externo
            $table->boolean('awaiting_external_payment')
                  ->default(false)
                  ->after('is_synced');
            
            // Método de pagamento escolhido (para rastreamento)
            $table->string('payment_method_chosen')
                  ->nullable()
                  ->after('awaiting_external_payment');
            
            // Índices
            $table->index('payment_intent_id');
            $table->index('awaiting_external_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_intent_id']);
            $table->dropColumn([
                'payment_intent_id',
                'awaiting_external_payment',
                'payment_method_chosen'
            ]);
        });
    }
};