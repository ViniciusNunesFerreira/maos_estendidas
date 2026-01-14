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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('filho_id')->constrained()->cascadeOnDelete();
            
            // Identificação
            $table->string('invoice_number')->unique(); // FAT-ASSIN-202412-001
            
            // Período
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date'); // Vencimento
            $table->date('issue_date');
            
            // =====================================================
            // VALORES
            // =====================================================
            
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);

            // =====================================================
            // STATUS
            // =====================================================
            
            $table->enum('status', [
                'pending',    // Aguardando pagamento
                'paid',       // Paga
                'overdue',    // Vencida
                'cancelled',  // Cancelada
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // =====================================================
            // NOTIFICAÇÕES
            // =====================================================
            
            $table->boolean('notification_sent')->default(false);
            $table->boolean('reminder_sent')->default(false);
            $table->boolean('overdue_notice_sent')->default(false);
            
            // =====================================================
            // PAGAMENTO
            // =====================================================
            
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            
            
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('due_date');
            $table->index(['filho_id', 'status']);
            $table->index(['subscription_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
