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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained()->restrictOnDelete();
            
            // Dados básicos
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->string('barcode')->unique()->nullable();
            
            // Imagem
            $table->string('image_url')->nullable();
            
            // Preço
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            
            // Estoque
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_alert')->default(10);
            $table->boolean('track_stock')->default(true);
            
            // Cozinha
            $table->boolean('requires_preparation')->default(false);
            $table->integer('preparation_time_minutes')->nullable();
            $table->string('preparation_station')->nullable(); // 'grill', 'fryer', etc
            
            // Disponibilidade
            $table->boolean('is_active')->default(true);
            $table->boolean('available_pdv')->default(true);
            $table->boolean('available_totem')->default(true);
            $table->boolean('available_app')->default(true);
            
            // Fiscal
            $table->string('ncm', 8)->nullable(); // Código NCM
            $table->string('cest', 7)->nullable(); // Código CEST
            $table->decimal('icms_rate', 5, 2)->default(0);
            $table->decimal('pis_rate', 5, 2)->default(0);
            $table->decimal('cofins_rate', 5, 2)->default(0);
            
            // Metadados
            $table->json('tags')->nullable();
            $table->json('allergens')->nullable(); // ['gluten', 'lactose', 'nuts']
            $table->integer('calories')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('slug');
            $table->index('sku');
            $table->index('barcode');
            $table->index(['is_active', 'category_id']);
            $table->index(['available_pdv', 'is_active']);
            $table->fullText(['name', 'description']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
