<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds performance indexes to student_subject and students tables to optimize academic history
     * import operations. These indexes significantly speed up duplicate detection, student lookups,
     * status filtering, and date range queries during bulk imports and exports.
     */
    public function up(): void
    {
        Schema::table('student_subject', function (Blueprint $table) {
            // Index for quickly finding all enrollments by a specific student during import operations
            if (!$this->indexExists('student_subject', 'idx_student_subject_student_doc')) {
                $table->index('student_document', 'idx_student_subject_student_doc');
            }
            
            // Compound index for efficient duplicate detection when checking if a student is already enrolled in a subject
            if (!$this->indexExists('student_subject', 'idx_student_subject_duplicate')) {
                $table->index(['student_document', 'subject_code'], 'idx_student_subject_duplicate');
            }
            
            // Index for date range queries during export operations and audit trails
            if (!$this->indexExists('student_subject', 'idx_student_subject_created')) {
                $table->index('created_at', 'idx_student_subject_created');
            }
            
            // Index for filtering enrollments by status (enrolled, passed, failed, withdrawn)
            if (!$this->indexExists('student_subject', 'idx_student_subject_status')) {
                $table->index('status', 'idx_student_subject_status');
            }
        });
        
        Schema::table('students', function (Blueprint $table) {
            // Index for fast student document lookup during imports (supports case-insensitive searches)
            if (!$this->indexExists('students', 'idx_students_document')) {
                $table->index('document', 'idx_students_document');
            }
            
            // Index for date range queries during exports and finding recently updated student records
            if (!$this->indexExists('students', 'idx_students_updated')) {
                $table->index('updated_at', 'idx_students_updated');
            }
        });
        
        // Note: subjects, elective_subjects, and leveling_subjects tables already have
        // code indexes from their original create migrations, no additional indexes needed
    }

    /**
     * Removes the performance indexes added in the up() method, reverting tables to their original state.
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
     * Checks if an index exists on a PostgreSQL table by querying the pg_indexes system catalog.
     * This prevents errors when trying to create an index that already exists.
     * 
     * @param string $table Table name to check
     * @param string $indexName Index name to look for
     * @return bool True if index exists, false otherwise
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
