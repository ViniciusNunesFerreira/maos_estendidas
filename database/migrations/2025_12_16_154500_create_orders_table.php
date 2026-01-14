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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique(); // Ex: PED-10020
            
            // Relacionamentos
            $table->foreignUuid('filho_id')->nullable()->constrained('filhos')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            
            // Vínculo com Fatura (Consumo Mensal)
            // Um pedido pertence a uma fatura, mas uma fatura tem vários pedidos.
            $table->foreignUuid('invoice_id')
                  ->nullable()
                  ->constrained('invoices')
                  ->nullOnDelete();

            // Origem do pedido
            $table->enum('origin', ['pdv', 'totem', 'app'])->default('pdv');
            $table->string('device_id')->nullable()->index();
            
            // Cliente (para vendas avulsas ou identificadas fora do cadastro de Filhos)
            $table->enum('customer_type', ['filho', 'guest'])->default('filho');
            $table->string('customer_name')->nullable();
            $table->string('customer_cpf', 11)->nullable();
            $table->string('customer_phone', 20)->nullable();
            
            // Dados Visitante
            $table->string('guest_name')->nullable();
            $table->string('guest_document')->nullable();

            // Valores
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Status do Pedido (Operacional)
            $table->enum('status', [
                'pending',       // Aguardando pagamento (balcão)
                'paid',          // Pago no ato (balcão)
                'preparing',     // Enviado para cozinha
                'ready',         // Pronto para retirada
                'delivered',     // Entregue ao aluno
                'cancelled',     // Cancelado
                'completed'      // Finalizado (processado financeiramente)
            ])->default('pending');
            
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Controle de Faturamento
            $table->boolean('is_invoiced')->default(false)->comment('Se true, já foi processado no fechamento mensal');
            $table->timestamp('invoiced_at')->nullable();
            
            // Timestamps do fluxo
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Observações
            $table->text('notes')->nullable();
            $table->text('kitchen_notes')->nullable();
            
            // Sincronização (Offline First)
            $table->boolean('is_synced')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_uuid')->nullable()->unique();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('order_number');
            $table->index(['status', 'origin']);
            $table->index(['filho_id', 'status']); // Busca pedidos em aberto do filho
            $table->index(['created_at', 'status']); // Relatórios do dia
            $table->index('is_invoiced'); // Crucial para o InvoiceService
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};