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
        Schema::create('external_subjects', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->foreignId('external_curriculum_id')->constrained()->onDelete('cascade')->comment('Foreign key to external_curriculums');
            $table->string('code')->comment('External subject code');
            $table->string('name')->comment('External subject name');
            $table->integer('credits')->comment('Academic credits');
            $table->integer('semester')->comment('Semester in external curriculum');
            $table->text('description')->nullable()->comment('Subject description');
            $table->json('additional_data')->nullable()->comment('Additional data from Excel import');
            $table->timestamps();
            
            $table->unique(['external_curriculum_id', 'code']); // One code per curriculum
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_subjects');
    }
};
