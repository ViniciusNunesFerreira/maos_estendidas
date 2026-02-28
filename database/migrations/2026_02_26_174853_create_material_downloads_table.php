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
        Schema::create('material_downloads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained();
            $table->foreignUuid('study_material_id')->constrained();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            $table->index(['user_id', 'study_material_id']);
            $table->index('study_material_id');
            $table->index('created_at');
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_downloads');
    }
};
