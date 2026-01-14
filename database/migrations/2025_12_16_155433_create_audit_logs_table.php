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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Usuário que realizou a ação
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Entidade afetada
            $table->uuidMorphs('auditable'); // auditable_type, auditable_id
            
            // Ação realizada
            $table->string('action'); // 'created', 'updated', 'deleted', 'login', etc
            
            // Dados alterados
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // Contexto da requisição
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, etc
            
            // Tags para facilitar busca
            $table->json('tags')->nullable();
            
            $table->timestamp('created_at');
            
            // Índices
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
