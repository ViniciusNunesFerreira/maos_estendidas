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
        Schema::create('password_reset_sms', function (Blueprint $table) {
$table->uuid('id')->primary();
            
            // Telefone que recebeu o código
            $table->string('phone', 20);
            
            // CPF do usuário
            $table->string('cpf', 11);
            
            // Código de 6 dígitos
            $table->string('code', 6);
            
            // Quando expira (15 minutos)
            $table->timestamp('expires_at');
            
            // Se já foi usado
            $table->boolean('used')->default(false);
            
            // Quando foi usado
            $table->timestamp('used_at')->nullable();
            
            // IP de quem solicitou
            $table->string('ip_address', 45)->nullable();
            
            // Tentativas de validação
            $table->integer('attempts')->default(0);
            
            // Máximo de tentativas
            $table->integer('max_attempts')->default(3);
            
            $table->timestamps();
            
            // Índices
            $table->index('phone');
            $table->index('cpf');
            $table->index(['phone', 'code']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_sms');
    }
};
