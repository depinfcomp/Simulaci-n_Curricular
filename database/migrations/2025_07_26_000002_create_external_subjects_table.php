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
            $table->id();
            $table->foreignId('external_curriculum_id')->constrained()->onDelete('cascade');
            $table->string('code'); // External subject code
            $table->string('name'); // External subject name
            $table->integer('credits'); // Subject credits
            $table->integer('semester'); // Semester in external curriculum
            $table->text('description')->nullable(); // Additional description
            $table->json('additional_data')->nullable(); // Additional data from Excel
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
