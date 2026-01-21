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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Identificação
            $table->string('gateway')->default('mercadopago'); // mercadopago, pagseguro, etc
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            
            // Credenciais Mercado Pago
            $table->text('access_token')->nullable(); // Token privado (criptografado)
            $table->text('public_key')->nullable();   // Chave pública
            $table->string('client_id')->nullable();  // Para OAuth
            $table->text('client_secret')->nullable(); // Para OAuth (criptografado)
            
            // Configurações Point (TEF)
            $table->string('device_id')->nullable();  // ID da maquininha Point
            $table->string('store_id')->nullable();   // ID da loja
            $table->string('pos_id')->nullable();     // ID do PDV
            $table->boolean('auto_print_receipt')->default(false); // Imprimir automático
            
            // Métodos ativos (bitmask ou JSON)
            $table->json('active_methods')->nullable(); // ['pix', 'credit_card', 'debit_card', 'tef']
            
            // Webhook
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable(); // Para validar signature
            
            // Status e controle
            $table->boolean('is_active')->default(false);
            $table->timestamp('tested_at')->nullable(); // Última vez que testou conexão
            $table->text('last_test_result')->nullable(); // Resultado do último teste
            
            // Metadados
            $table->json('metadata')->nullable(); // Configurações extras
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('gateway');
            $table->index('environment');
            $table->index('is_active');
        });
        
        // Inserir configuração padrão
        DB::table('payment_settings')->insert([
            'id' => Str::uuid(),
            'gateway' => 'mercadopago',
            'environment' => 'sandbox',
            'is_active' => false,
            'active_methods' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};