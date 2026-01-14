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
        Schema::table('filhos', function (Blueprint $table) {
            $table->string('address_number')->after('address')->nullable();
            $table->string('address_complement')->after('address_number')->nullable();
            $table->string('neighborhood')->after('address_complement')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filhos', function (Blueprint $table) {
            //
        });
    }
};
