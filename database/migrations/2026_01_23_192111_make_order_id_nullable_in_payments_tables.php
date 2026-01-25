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
        // 1. Alteração na tabela PAYMENTS
        Schema::table('payments', function (Blueprint $table) {
            
            $table->dropForeign(['order_id']);

          
            
            $table->uuid('order_id')->nullable()->change();

            // Recriamos a FK.
            // Opcional: Adicionar onDelete('set null') ou 'cascade' dependendo da sua regra de negócio
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')->cascadeOnDelete();
        });

        // 2. Alteração na tabela PAYMENT_INTENTS
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropForeign(['order_id']);

            $table->uuid('order_id')->nullable()->change();

            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments_tables', function (Blueprint $table) {
            //
        });
    }
};
