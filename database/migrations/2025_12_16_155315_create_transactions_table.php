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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('filho_id')->constrained()->cascadeOnDelete();
            
            // Tipo de transação
            $table->enum('type', [
                'credit',              // Crédito (recarga)
                'debit',               // Débito (compra)
                'mensalidade_credit',  // Crédito de mensalidade
                'mensalidade_debit',   // Débito de mensalidade
                'refund',              // Estorno
                'adjustment',          // Ajuste manual
            ]);
            
            // Valores
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            
            // Relacionamentos opcionais
            $table->foreignUuid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('compra_externa_id')->nullable()
                  ->constrained('compras_externas')->nullOnDelete();
            
            // Metadados
            $table->string('description');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by_user_id')->constrained('users')
                  ->restrictOnDelete();
            
            $table->timestamps();
            
            // Índices
            $table->index(['filho_id', 'created_at']);
            $table->index(['filho_id', 'type']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
