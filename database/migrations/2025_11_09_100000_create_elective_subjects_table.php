<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the elective_subjects table which stores optional/elective subjects that students can
     * choose from to fulfill optional fundamental or optional professional credit requirements.
     * These subjects are not part of the fixed curriculum but are offered as choices.
     */
    public function up(): void
    {
        Schema::create('elective_subjects', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this elective subject');
            $table->string('code', 10)->unique()->comment('Subject code - unique identifier across all elective subjects');
            $table->string('name')->comment('Full name of the elective subject');
            $table->integer('semester')->nullable()->comment('Recommended semester to take this elective (1-10, nullable if no specific recommendation)');
            $table->integer('credits')->comment('Number of academic credits this elective is worth');
            $table->integer('classroom_hours')->default(0)->comment('Number of classroom contact hours per week with instructor');
            $table->integer('student_hours')->default(0)->comment('Number of independent student work hours per week (homework, study, projects)');
            $table->enum('elective_type', ['optativa_fundamental', 'optativa_profesional'])
                  ->comment('Type of elective component: optativa_fundamental (optional fundamental, general education), optativa_profesional (optional professional/disciplinary, specialized courses)');
            $table->text('description')->nullable()->comment('Detailed description of subject content, learning objectives, and any prerequisites');
            $table->boolean('is_active')->default(true)->comment('Flag indicating if the subject is currently being offered (false for discontinued electives)');
            $table->timestamps();
            
            // Indexes for query performance
            $table->index('elective_type')->comment('Index for filtering by elective type');
            $table->index('is_active')->comment('Index for finding currently offered electives');
        });
    }

    /**
     * Drops the elective_subjects table.
     */
    public function down(): void
    {
        Schema::dropIfExists('elective_subjects');
    }
};
