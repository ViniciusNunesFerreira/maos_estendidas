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
        Schema::create('point_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Identificação do dispositivo
            $table->string('device_id')->unique(); // PAX_A910__SMARTPOS1234567890
            $table->string('device_name')->nullable(); // Nome amigável
            $table->string('device_type')->nullable(); // PAX_A910, GERTEC_MP5, etc
            
            // Localização
            $table->string('store_id')->nullable();
            $table->string('store_name')->nullable();
            $table->string('pos_id')->nullable(); // ID do caixa
            $table->string('location')->nullable(); // Descrição da localização
            
            // Status
            $table->enum('status', [
                'active',       // Ativo e funcionando
                'inactive',     // Inativo
                'offline',      // Offline (sem comunicação)
                'maintenance'   // Em manutenção
            ])->default('active');
            
            // Configurações
            $table->boolean('auto_print')->default(false); // Imprimir automaticamente
            $table->boolean('enabled_for_pdv')->default(true); // Habilitado para PDV
            
            // Informações do dispositivo (retornadas pelo MP)
            $table->string('serial_number')->nullable();
            $table->string('firmware_version')->nullable();
            $table->json('capabilities')->nullable(); // ['contactless', 'chip', 'magnetic']
            
            // Últimas atividades
            $table->timestamp('last_communication_at')->nullable(); // Última comunicação
            $table->timestamp('last_payment_at')->nullable(); // Último pagamento
            $table->decimal('last_payment_amount', 10, 2)->nullable();
            
            // Estatísticas
            $table->integer('total_payments')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            
            // Metadados
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('device_id');
            $table->index('status');
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_devices');
    }
};