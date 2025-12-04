<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the student_current_subjects table which tracks subjects that students
     * are currently enrolled in for the current academic period.
     */
    public function up(): void
    {
        Schema::create('student_current_subjects', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key');
            $table->unsignedBigInteger('student_id')->comment('Foreign key reference to students table');
            $table->string('subject_code', 10)->comment('Foreign key reference to subjects table by code');
            $table->string('subject_name', 255)->nullable()->comment('Subject name from import file (consolidated from 2025_11_13_000001 migration)');
            $table->string('semester_period', 20)->comment('Academic period identifier (e.g., "2025-1", "2025-2")');
            $table->enum('status', ['cursando', 'en_examen', 'perdida'])->default('cursando')->comment('Current enrollment status: cursando (in progress), en_examen (in exam), perdida (failed)');
            $table->decimal('partial_grade', 3, 1)->nullable()->comment('Current partial grade (0.0 to 5.0 scale)');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('subject_code')->references('code')->on('subjects')->onDelete('cascade');
            
            // Indexes for query performance
            $table->index(['student_id', 'semester_period']);
            $table->index('subject_code');
            
            // Unique constraint to prevent duplicate enrollments
            $table->unique(['student_id', 'subject_code', 'semester_period']);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the student_current_subjects table.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_current_subjects');
    }
};
