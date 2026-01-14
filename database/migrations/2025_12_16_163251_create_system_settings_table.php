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
        Schema::create('system_settings', function (Blueprint $table) {
            
            $table->uuid('id')->primary();
            
            // Grupo da configuração
            $table->string('group')->default('general');
            
            // Chave única
            $table->string('key')->unique();
            
            // Valor (JSON para flexibilidade)
            $table->json('value')->nullable();
            
            // Tipo do valor
            $table->enum('type', [
                'string',
                'integer',
                'decimal',
                'boolean',
                'array',
                'json',
            ])->default('string');
            
            // Descrição da configuração
            $table->string('description')->nullable();
            
            // Se pode ser editado pelo admin
            $table->boolean('is_editable')->default(true);
            
            $table->timestamps();
            
            $table->index('group');
            $table->index(['group', 'key']);

        });

        // Inserir configurações padrão
        $this->insertDefaultSettings();
    }


     private function insertDefaultSettings(): void
    {
        $settings = [
            // Assinatura
            [
                'group' => 'subscription',
                'key' => 'default_plan_amount',
                'value' => json_encode(120.00),
                'type' => 'decimal',
                'description' => 'Valor padrão da mensalidade',
            ],
            [
                'group' => 'subscription',
                'key' => 'first_billing_days',
                'value' => json_encode(30),
                'type' => 'integer',
                'description' => 'Dias após aprovação para primeira cobrança',
            ],
            
            // Crédito
            [
                'group' => 'credit',
                'key' => 'default_credit_limit',
                'value' => json_encode(5000.00),
                'type' => 'decimal',
                'description' => 'Limite de crédito padrão para novos filhos',
            ],
            [
                'group' => 'credit',
                'key' => 'max_overdue_invoices',
                'value' => json_encode(3),
                'type' => 'integer',
                'description' => 'Máximo de faturas vencidas antes de bloquear',
            ],
            
            // Faturamento
            [
                'group' => 'billing',
                'key' => 'default_billing_close_day',
                'value' => json_encode(30),
                'type' => 'integer',
                'description' => 'Dia padrão de fechamento de faturas',
            ],
            [
                'group' => 'billing',
                'key' => 'invoice_due_days',
                'value' => json_encode(5),
                'type' => 'integer',
                'description' => 'Dias após fechamento para vencimento',
            ],
            
            // SMS
            [
                'group' => 'sms',
                'key' => 'reset_code_expiry_minutes',
                'value' => json_encode(15),
                'type' => 'integer',
                'description' => 'Minutos para expirar código SMS',
            ],
        ];
        
        foreach ($settings as $setting) {
            DB::table('system_settings')->insert(array_merge($setting, [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
