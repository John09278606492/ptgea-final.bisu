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
        Schema::create('studentschoolyearsemesterpayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studentschoolyearsemester_id');

            // Custom foreign key constraint name
            $table->foreign('studentschoolyearsemester_id', 'fk_ssysp_ssys_id')
                ->references('id')
                ->on('studentschoolyearsemesters')
                ->onDelete('cascade');
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studentschoolyearsemesterpayments');
    }
};
