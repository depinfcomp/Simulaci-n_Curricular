<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add indexes to speed up academic history import queries
     */
    public function up(): void
    {
        Schema::table('student_subject', function (Blueprint $table) {
            // Index for finding records by student during import
            if (!$this->indexExists('student_subject', 'idx_student_subject_student_doc')) {
                $table->index('student_document', 'idx_student_subject_student_doc');
            }
            
            // Compound index for duplicate detection (student + subject)
            if (!$this->indexExists('student_subject', 'idx_student_subject_duplicate')) {
                $table->index(['student_document', 'subject_code'], 'idx_student_subject_duplicate');
            }
            
            // Index for date range queries during export
            if (!$this->indexExists('student_subject', 'idx_student_subject_created')) {
                $table->index('created_at', 'idx_student_subject_created');
            }
            
            // Index for status filtering
            if (!$this->indexExists('student_subject', 'idx_student_subject_status')) {
                $table->index('status', 'idx_student_subject_status');
            }
        });
        
        Schema::table('students', function (Blueprint $table) {
            // Index for document lookup (case-insensitive searches)
            if (!$this->indexExists('students', 'idx_students_document')) {
                $table->index('document', 'idx_students_document');
            }
            
            // Index for date range queries during export
            if (!$this->indexExists('students', 'idx_students_updated')) {
                $table->index('updated_at', 'idx_students_updated');
            }
        });
        
        // Note: subjects, elective_subjects, and leveling_subjects already have
        // code indexes from their original migrations, no need to add more
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_subject', function (Blueprint $table) {
            if ($this->indexExists('student_subject', 'idx_student_subject_student_doc')) {
                $table->dropIndex('idx_student_subject_student_doc');
            }
            if ($this->indexExists('student_subject', 'idx_student_subject_duplicate')) {
                $table->dropIndex('idx_student_subject_duplicate');
            }
            if ($this->indexExists('student_subject', 'idx_student_subject_created')) {
                $table->dropIndex('idx_student_subject_created');
            }
            if ($this->indexExists('student_subject', 'idx_student_subject_status')) {
                $table->dropIndex('idx_student_subject_status');
            }
        });
        
        Schema::table('students', function (Blueprint $table) {
            if ($this->indexExists('students', 'idx_students_document')) {
                $table->dropIndex('idx_students_document');
            }
            if ($this->indexExists('students', 'idx_students_updated')) {
                $table->dropIndex('idx_students_updated');
            }
        });
    }
    
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("
            SELECT indexname 
            FROM pg_indexes 
            WHERE tablename = ? AND indexname = ?
        ", [$table, $indexName]);
        
        return count($indexes) > 0;
    }
};
