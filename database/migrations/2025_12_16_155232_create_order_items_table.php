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
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->restrictOnDelete();
            
            // Snapshot do produto no momento da venda
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->decimal('unit_price', 10, 2);
            
            // Quantidade e valores
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Customizações
            $table->text('notes')->nullable();
            $table->json('modifiers')->nullable(); // Adicionais, remoções
            
            // Status individual (para cozinha)
            $table->enum('preparation_status', [
                'pending',
                'preparing',
                'ready',
                'delivered'
            ])->default('pending');
            
            $table->timestamps();
            
            // Índices
            $table->index(['order_id', 'product_id']);
            $table->index('preparation_status');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
