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
        Schema::table('payments', function (Blueprint $table) {
            // Identificadores Mercado Pago
            $table->string('mp_payment_id')->nullable()->after('gateway_transaction_id'); // ID do payment no MP
            $table->string('mp_payment_intent_id')->nullable()->after('mp_payment_id'); // ID do payment intent
            $table->string('mp_merchant_order_id')->nullable()->after('mp_payment_intent_id'); // ID da merchant order
            
            // Status detalhado do Mercado Pago
            $table->string('mp_status')->nullable()->after('status'); // approved, rejected, pending, etc
            $table->string('mp_status_detail')->nullable()->after('mp_status'); // Detalhe do status
            
            // Dados do pagamento
            $table->string('mp_payment_type_id')->nullable(); // credit_card, debit_card, pix, etc
            $table->string('mp_payment_method_id')->nullable(); // visa, master, pix, etc
            $table->string('mp_issuer_id')->nullable(); // Banco emissor
            
            // Informações do cartão (se aplicável)
            $table->string('mp_card_id')->nullable();
            $table->string('mp_card_first_six_digits', 6)->nullable();
            
            // PIX (se aplicável)
            $table->text('mp_pix_qr_code')->nullable(); // QR Code do PIX
            $table->text('mp_pix_qr_code_base64')->nullable(); // QR Code em base64
            $table->string('mp_pix_ticket_url')->nullable(); // URL do comprovante
            
            // Point TEF (se aplicável)
            $table->boolean('is_tef')->default(false); // Se foi TEF Point
            $table->string('tef_device_id')->nullable(); // ID da maquininha
            $table->string('tef_payment_intent_id')->nullable(); // ID do payment intent do Point
            
            // Dados da transação
            $table->integer('mp_installments')->nullable()->default(1); // Parcelas
            $table->decimal('mp_transaction_amount', 10, 2)->nullable(); // Valor da transação
            $table->decimal('mp_net_received_amount', 10, 2)->nullable(); // Valor líquido recebido
            $table->decimal('mp_total_paid_amount', 10, 2)->nullable(); // Total pago (com juros)
            
            // Taxas
            $table->decimal('mp_fee_amount', 10, 2)->nullable()->default(0); // Taxa do MP
            $table->decimal('mp_merchant_fee', 10, 2)->nullable()->default(0); // Taxa do merchant
            
            // Resposta completa do gateway (para auditoria)
            $table->json('mp_response')->nullable(); // Response completo do MP
            
            // Webhook
            $table->timestamp('mp_webhook_received_at')->nullable(); // Quando recebeu webhook
            $table->integer('mp_webhook_attempts')->default(0); // Tentativas de webhook
            
            // Índices
            $table->index('mp_payment_id');
            $table->index('mp_payment_intent_id');
            $table->index('mp_status');
            $table->index('is_tef');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'mp_payment_id',
                'mp_payment_intent_id',
                'mp_merchant_order_id',
                'mp_status',
                'mp_status_detail',
                'mp_payment_type_id',
                'mp_payment_method_id',
                'mp_issuer_id',
                'mp_card_id',
                'mp_card_first_six_digits',
                'mp_pix_qr_code',
                'mp_pix_qr_code_base64',
                'mp_pix_ticket_url',
                'is_tef',
                'tef_device_id',
                'tef_payment_intent_id',
                'mp_installments',
                'mp_transaction_amount',
                'mp_net_received_amount',
                'mp_total_paid_amount',
                'mp_fee_amount',
                'mp_merchant_fee',
                'mp_response',
                'mp_webhook_received_at',
                'mp_webhook_attempts',
            ]);
        });
    }
};