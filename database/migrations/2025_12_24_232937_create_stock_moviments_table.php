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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Relacionamentos
            $table->foreignUuid('product_id')
                ->constrained()
                ->cascadeOnDelete();
            
            $table->foreignUuid('user_id')
                ->constrained()
                ->restrictOnDelete();
            
            $table->foreignUuid('order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            
            // Tipo de movimentação
            $table->enum('type', [
                'entry',           // Entrada de estoque
                'out',             // Saída manual
                'sale',            // Saída por venda
                'adjustment',      // Ajuste de inventário
                'return',          // Devolução
                'loss',            // Perda
                'transfer',        // Transferência
            ]);
            
            // Quantidades
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            
            // Custos (opcional - para entradas)
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            
            // Referências e metadados
            $table->string('reference')->nullable(); // Nota fiscal, etc
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('type');
            $table->index(['product_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};