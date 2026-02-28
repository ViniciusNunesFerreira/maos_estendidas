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
        Schema::create('study_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->enum('type', ['ebook', 'video', 'article'])->default('article');
            
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_free')->default(true);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->json('tags')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_published', 'category_id']);
            $table->index(['type', 'is_free']);
            $table->index('download_count');
            $table->index('view_count');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->enum('type', ['product', 'study_material'])->default('product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_materials');
    }
};
