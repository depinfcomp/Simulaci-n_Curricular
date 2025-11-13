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
        Schema::create('leveling_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Subject code - Unique identifier');
            $table->string('name')->comment('Subject name');
            $table->integer('credits')->comment('Academic credits');
            $table->integer('classroom_hours')->default(0)->comment('Classroom hours per week');
            $table->integer('student_hours')->default(0)->comment('Student independent work hours per week');
            $table->text('description')->nullable()->comment('Subject description');
            $table->boolean('is_active')->default(true)->comment('Whether the subject is currently offered');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('is_active');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leveling_subjects');
    }
};
