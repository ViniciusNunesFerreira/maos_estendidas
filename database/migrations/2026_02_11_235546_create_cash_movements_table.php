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
        // Movimentações (Vendas, Sangrias, Suprimentos)
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cash_session_id')->constrained('cash_sessions');
            $table->foreignUuid('order_id')->nullable()->constrained('orders'); // Se for venda
            $table->foreignUuid('user_id')->constrained('users'); // Quem fez o movimento
            
            $table->string('type'); // sale, opening, supply (suprimento), bleed (sangria), closing
            $table->decimal('amount', 10, 2); // Valor (negativo para sangria)
            $table->string('payment_method'); // dinheiro, pix, credito, debito
            
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
