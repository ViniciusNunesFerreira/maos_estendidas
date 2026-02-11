<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Criamos/Verificamos as colunas primeiro
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'is_invoiced')) {
                $table->boolean('is_invoiced')->default(false);
            }
            if (!Schema::hasColumn('orders', 'invoiced_at')) {
                $table->timestamp('invoiced_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'type')) {
                $table->string('type')->default('consumption');
            }
        });

        // 2. Manipulação de Índices (Lógica separada para maior segurança)
        Schema::table('orders', function (Blueprint $table) {
            $indexes = Schema::getIndexes('orders');
            $indexNames = array_column($indexes, 'name');

            // Removemos o índice anterior que tentamos criar com 'type' (se ele chegou a ser criado)
            if (in_array('idx_orders_billing_lookup', $indexNames)) {
                $table->dropIndex('idx_orders_billing_lookup');
            }

            // Criamos o índice perfeito para sua consulta atual
            // Ordem: Filho -> Status de Faturamento -> Tipo de Cliente -> Status do Pedido
            $table->index(
                ['filho_id', 'is_invoiced', 'customer_type', 'status'], 
                'idx_orders_app_optimized'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $indexes = Schema::getIndexes('orders');
            $indexNames = array_column($indexes, 'name');

            if (in_array('idx_orders_billing_lookup', $indexNames)) {
                $table->dropIndex('idx_orders_billing_lookup');
            }
            
            // Recria o básico apenas se não houver
            if (!in_array('orders_is_invoiced_index', $indexNames)) {
                $table->index('is_invoiced');
            }
        });
    }
};