<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // 1. Remove qualquer restrição (CHECK constraint) que o Laravel possa ter criado
            // Isso limpa o erro de "syntax error near check" e "operator does not exist"
            DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check');

            // 2. Transforma a coluna definitivamente em VARCHAR(50)
            // O USING type::varchar garante que os dados atuais sejam convertidos sem perda
            DB::statement('ALTER TABLE stock_movements ALTER COLUMN type TYPE VARCHAR(50) USING type::varchar');

            // 3. Agora podemos apagar o tipo ENUM problemático do banco
            DB::statement('DROP TYPE IF EXISTS stock_movements_type_enum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down, apenas mantemos como string, pois é mais seguro em produção
    }
};