<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the student_subject pivot table which stores the relationship between students and subjects
     * with their enrollment details, grades, and credit distribution. This table uses denormalized data
     * for performance (stores subject details directly) and tracks how credits are assigned to curricular
     * components, including overflow handling when component limits are exceeded.
     */
    public function up(): void
    {
        Schema::create('student_subject', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this enrollment record');
            
            // Student identification
            $table->string('student_document', 50)->index()->comment('Student identification document number (foreign key to students table)');
            
            // Subject information (denormalized for query performance)
            $table->string('subject_code', 20)->index()->comment('Subject code identifier (foreign key to subjects table)');
            $table->string('subject_name', 255)->comment('Subject name cached for quick display without joins');
            $table->integer('subject_credits')->comment('Number of academic credits this subject is worth');
            $table->string('subject_type', 50)->comment('Subject type indicating curricular component (fundamental_required, optional_fundamental, professional_required, optional_professional, free_elective, leveling, thesis)');
            
            // Credit distribution fields (tracks how credits are assigned to components)
            $table->integer('effective_credits')->default(0)->comment('Number of credits that count toward the subject original component (may be less than subject_credits if component limit exceeded)');
            $table->integer('overflow_credits')->default(0)->comment('Number of credits redirected to free elective component when original component limit is exceeded');
            $table->string('actual_component_type', 50)->nullable()->comment('The component type where credits actually count (may differ from subject_type if overflow occurred, e.g., "free_elective")');
            $table->boolean('is_duplicate')->default(false)->comment('Indicates if student has taken this subject multiple times (only best attempt counts for credits)');
            $table->boolean('counts_for_percentage')->default(true)->comment('Indicates if this subject counts toward degree completion percentage calculation');
            $table->text('assignment_notes')->nullable()->comment('Optional notes explaining how credits were assigned, overflow reasons, or special conditions');
            
            // Grading information
            $table->decimal('grade', 3, 2)->nullable()->comment('Numerical grade on 0.00 to 5.00 scale (Colombian system, 3.00 is passing)');
            $table->string('alphabetic_grade', 5)->nullable()->comment('Letter grade representation (AP for Aprobado/Passed, RE for Reprobado/Failed)');
            $table->enum('status', ['enrolled', 'passed', 'failed', 'withdrawn'])->default('enrolled')->comment('Current enrollment status: enrolled (currently taking), passed (successfully completed), failed (did not pass), withdrawn (dropped before completion)');
            
            // Academic period
            $table->string('period', 20)->nullable()->comment('Academic period when subject was taken (format: YYYY-S where S is semester, e.g., 2024-1)');
            
            $table->timestamps();
            
            // Composite indexes for common queries
            $table->index(['student_document', 'subject_code'])->comment('Index for finding specific student-subject enrollments');
            $table->index(['student_document', 'status'])->comment('Index for filtering enrollments by status');
            $table->index('subject_type')->comment('Index for grouping by curricular component');
        });
    }

    /**
     * Drops the student_subject pivot table.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject');
    }
};
