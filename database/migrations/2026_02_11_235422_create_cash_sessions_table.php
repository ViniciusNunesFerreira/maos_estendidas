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
        // Sessão do Caixa (Abertura/Fechamento)
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users'); // Quem abriu
            $table->string('device_id')->nullable(); // Identificação do terminal
            
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            
            // Valores
            $table->decimal('opening_balance', 10, 2); // Fundo de troco inicial
            $table->decimal('calculated_balance', 10, 2)->default(0); // O que o sistema diz que tem
            $table->decimal('counted_balance', 10, 2)->nullable(); // O que o operador contou (Fechamento Cego)
            $table->decimal('difference', 10, 2)->nullable(); // Quebra de caixa
            
            $table->string('status')->default('open'); // open, closed, audited
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
