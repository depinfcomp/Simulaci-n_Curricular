<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Removes foreign key constraints from subject_code columns in student_subject and
     * student_current_subjects tables. This allows these tables to reference subject codes from
     * either the subjects table OR the elective_subjects table, enabling students to enroll in
     * both required subjects and optional electives.
     */
    public function up(): void
    {
        // Drop foreign key constraints that restricted subject_code to only subjects table
        // After this migration, both subjects and elective_subjects codes are allowed
        DB::statement('ALTER TABLE student_subject DROP CONSTRAINT IF EXISTS student_subject_subject_code_foreign');
        DB::statement('ALTER TABLE student_current_subjects DROP CONSTRAINT IF EXISTS student_current_subjects_subject_code_foreign');
    }

    /**
     * Restores the original foreign key constraints that only allow subjects from the subjects table.
     * This may fail if there are records referencing elective subjects.
     */
    public function down(): void
    {
        // Restore foreign key constraints to subjects table only
        Schema::table('student_subject', function (Blueprint $table) {
            $table->foreign('subject_code')
                  ->references('code')
                  ->on('subjects')
                  ->onDelete('cascade');
        });
        
        Schema::table('student_current_subjects', function (Blueprint $table) {
            $table->foreign('subject_code')
                  ->references('code')
                  ->on('subjects')
                  ->onDelete('cascade');
        });
    }
};
