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
        Schema::create('filhos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Dados pessoais
            $table->string('cpf', 11)->unique();
            $table->date('birth_date');
            $table->string('mother_name'); // Nome da mãe

            // Contato
            $table->string('phone', 20)->nullable();
            
            // Endereço (opcional se diferente da casa)
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code', 8)->nullable();
            

            $table->decimal('credit_limit', 10, 2)->default(500); // Limite de crédito
            $table->decimal('credit_used', 10, 2)->default(0); // Quanto já usou
            $table->integer('billing_close_day')->default(28); // Dia fechamento fatura
            $table->integer('max_overdue_invoices')->default(3); // Máx faturas vencidas
            $table->boolean('is_blocked_by_debt')->default(false);
            // Motivo do bloqueio
            $table->string('block_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
 

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('inactive');
            $table->text('notes')->nullable();


            // Metadados
            $table->date('admission_date')->nullable();
            $table->date('departure_date')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('cpf');
            $table->index('status');
            $table->index('is_blocked_by_debt');
            $table->index('billing_close_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filhos');
    }
};
