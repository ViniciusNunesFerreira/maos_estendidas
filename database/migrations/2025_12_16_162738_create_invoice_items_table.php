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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Pai (Fatura)
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            
            // Rastreabilidade (Origem do item)
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            
            // Referência ao Produto (pode ser null se for uma taxa avulsa ou mensalidade genérica)
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            
            // =====================================================
            // DADOS DO ITEM (SNAPSHOT - Histórico Imutável)
            // =====================================================
            
            $table->date('purchase_date')->comment('Data que o consumo ocorreu');
            $table->string('description')->comment('Texto exibido na fatura (Ex: Coxinha de Frango)');
            $table->string('category')->nullable()->comment('Ex: Lanches, Bebidas, Mensalidade');
            $table->string('location')->nullable()->comment('loja, cantina, sistema');
            
            // Valores Financeiros
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2)->comment('Qtd * Unit Price');
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->comment('Subtotal - Desconto');
            
            $table->timestamps();
            
            // Índices
            $table->index(['invoice_id', 'purchase_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};