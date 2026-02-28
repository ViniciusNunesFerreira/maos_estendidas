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
        Schema::table('study_materials', function (Blueprint $table) {
            $table->string('external_video_id')->nullable()->after('file_size');
            $table->string('processing_status')->default('pending')->after('is_published'); // 'pending', 'processing', 'completed', 'failed'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_materials', function (Blueprint $table) {
            //
        });
    }
};
