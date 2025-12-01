<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the leveling_subjects table which stores subjects that students must take to meet
     * prerequisite requirements when entering a program. These are remedial or preparatory courses
     * taken before or alongside regular curriculum subjects.
     */
    public function up(): void
    {
        Schema::create('leveling_subjects', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this leveling subject');
            $table->string('code', 10)->unique()->comment('Subject code - unique identifier across all leveling subjects');
            $table->string('name')->comment('Full name of the leveling subject');
            $table->integer('credits')->comment('Number of academic credits this subject is worth');
            $table->integer('classroom_hours')->default(0)->comment('Number of classroom contact hours per week with instructor');
            $table->integer('student_hours')->default(0)->comment('Number of independent student work hours per week (homework, study, projects)');
            $table->text('description')->nullable()->comment('Detailed description of subject content, learning objectives, and prerequisites');
            // Note: is_active field removed - not needed for current implementation (all leveling subjects are considered active)
            $table->timestamps();
            
            // Index for performance on code lookups
            $table->index('code')->comment('Index for quick code-based queries');
        });
    }

    /**
     * Drops the leveling_subjects table.
     */
    public function down(): void
    {
        Schema::dropIfExists('leveling_subjects');
    }
};
