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
        Schema::create('collection_syear', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syear_id')->constrained()->onDelete('cascade');
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->foreignId('syear_semester_id')->constrained('syear_semester')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_syear');
    }
};