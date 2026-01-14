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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('filho_id')->constrained()->cascadeOnDelete();
            // Quem aprovou/criou a assinatura
            $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Dados do plano
            $table->string('plan_name')->default('Mensalidade Casa Lar');
            // Descrição
            $table->text('plan_description')->nullable();
            $table->decimal('amount', 10, 2); // Valor mensal
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            // Dia do vencimento (1-28)
            $table->integer('billing_day')->default(10);
            
                        // =====================================================
            // DATAS IMPORTANTES
            // =====================================================
            
            // Data de início da assinatura
            $table->date('started_at');
            
            // Primeira cobrança (30 dias após aprovação)
            $table->date('first_billing_date');
            
            // Próxima cobrança
            $table->date('next_billing_date');
            
            // Data de cancelamento
            $table->timestamp('cancelled_at')->nullable();
            
            // Data de pausa
            $table->timestamp('paused_at')->nullable();
            
            // Data de fim (se aplicável)
            $table->date('ends_at')->nullable();

            // =====================================================
            // STATUS
            // =====================================================
            
            $table->enum('status', [
                'pending',    // Aguardando ativação
                'trial',      // Em período de teste
                'active',     // Ativa
                'paused',     // Pausada
                'suspended',  // Suspensa (inadimplência)
                'cancelled',  // Cancelada
                'expired',    // Expirada
            ])->default('pending');
            
            // Motivo do status atual
            $table->string('status_reason')->nullable();

            // =====================================================
            // HISTÓRICO
            // =====================================================
            
            // Total de faturas geradas
            $table->integer('invoices_count')->default(0);
            
            // Total de faturas pagas
            $table->integer('paid_invoices_count')->default(0);
            
            // Total já pago
            $table->decimal('total_paid', 10, 2)->default(0);

            
            $table->timestamps();
            $table->softDeletes();


            $table->index('status');
            $table->index('next_billing_date');
            $table->index(['filho_id', 'status']);
            $table->index(['status', 'next_billing_date']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
