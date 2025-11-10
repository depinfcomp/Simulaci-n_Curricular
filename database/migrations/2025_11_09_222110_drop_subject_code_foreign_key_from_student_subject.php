<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key constraints that only allow subjects table codes
        // This allows both subjects and elective_subjects codes in both tables
        DB::statement('ALTER TABLE student_subject DROP CONSTRAINT IF EXISTS student_subject_subject_code_foreign');
        DB::statement('ALTER TABLE student_current_subjects DROP CONSTRAINT IF EXISTS student_current_subjects_subject_code_foreign');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore foreign key constraints to subjects only
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
