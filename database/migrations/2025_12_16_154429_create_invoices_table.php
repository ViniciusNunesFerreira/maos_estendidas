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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relacionamento com Filho (Cliente/Aluno)
            $table->foreignUuid('filho_id')->constrained('filhos')->cascadeOnDelete();
            
            // Número único da fatura: FAT-LOJA-202412-001
            $table->string('invoice_number', 30)->unique();

            // Tipo da fatura
            $table->enum('type', ['consumption', 'subscription'])
                  ->default('consumption')
                  ->comment('consumption = fechamento consumo loja, subscription = mensalidade recorrente');

            // =====================================================
            // PERÍODO DE REFERÊNCIA
            // =====================================================
            
            $table->date('period_start')->comment('Início do ciclo de faturamento');
            $table->date('period_end')->comment('Fim do ciclo de faturamento');
            $table->date('issue_date')->comment('Data de emissão da fatura');
            $table->date('due_date')->comment('Data de vencimento');

            // Dados SAT (Fiscal)
            $table->string('sat_number')->nullable()->index(); // Número do CF-e-SAT (se aplicável a fatura fechada)
            $table->string('sat_key', 44)->nullable()->index(); // Chave de acesso
            $table->string('sat_qrcode')->nullable();
            $table->text('sat_xml')->nullable();
            
            // Status do Fluxo Financeiro
            $table->enum('status', [
                'draft',       // Em rascunho/geração
                'pending',     // Emitida, aguardando pagamento
                'processing',  // Processando pagamento/SAT
                'paid',        // Paga integralmente
                'partial',     // Parcialmente paga
                'overdue',     // Vencida
                'cancelled',   // Cancelada
                'failed',      // Falha na emissão fiscal
            ])->default('draft');
            
            // =====================================================
            // VALORES MONETÁRIOS
            // =====================================================

            // Subtotal (soma bruta dos itens)
            $table->decimal('subtotal', 10, 2)->default(0);
            
            // Descontos
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
            
            // Multas e Juros (Adicionados conforme necessidade do Service)
            $table->decimal('late_fee', 10, 2)->default(0)->comment('Valor da multa por atraso');
            $table->decimal('interest', 10, 2)->default(0)->comment('Valor dos juros acumulados');
            $table->boolean('late_fee_applied')->default(false);

            // Total Final (Subtotal - Descontos + Multas + Juros)
            $table->decimal('total_amount', 10, 2)->default(0);
            
            // Controle de Pagamento
            $table->decimal('paid_amount', 10, 2)->default(0);
            
            // Impostos (informativo/fiscal)
            $table->decimal('tax_amount', 10, 2)->default(0);

            // =====================================================
            // NOTIFICAÇÕES E CONTROLE
            // =====================================================
            
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            
            $table->boolean('overdue_notice_sent')->default(false);
            $table->timestamp('overdue_notice_sent_at')->nullable();

            // Observações
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable(); // Visível apenas para adm

            // Tratamento de Erros
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            
            // Timestamps de Eventos
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices Otimizados
            $table->index(['filho_id', 'status']); // Busca comum: Faturas pendentes de um filho
            $table->index(['status', 'due_date']); // Busca comum: Rotina de cobrança
            $table->index(['invoice_number']);
            $table->index(['type', 'period_start']); // Relatórios mensais
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};