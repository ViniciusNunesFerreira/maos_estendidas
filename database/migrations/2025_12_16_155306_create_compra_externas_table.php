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
        Schema::create('compras_externas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('filho_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('requested_by_user_id')->constrained('users')
                  ->restrictOnDelete();
            
            // Dados da compra
            $table->string('store_name');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            
            // Comprovante
            $table->string('receipt_url')->nullable();
            $table->date('purchase_date');
            
            // Status e aprovação
            $table->enum('status', [
                'pending',    // Aguardando aprovação
                'approved',   // Aprovado
                'rejected',   // Rejeitado
            ])->default('pending');
            
            $table->foreignUuid('approved_by_user_id')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['filho_id', 'status']);
            $table->index(['status', 'created_at']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compra_externas');
    }
};
