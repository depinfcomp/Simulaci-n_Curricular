<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Updates the convalidation_type enum in subject_convalidations table by replacing 'free_elective'
     * with 'flexible_component'. This terminology change better reflects that these credits can be
     * assigned flexibly to various components based on student needs and component limits, not just
     * to free electives.
     */
    public function up(): void
    {
        // For PostgreSQL, use raw SQL to modify enum constraint
        // Step 1: Remove the existing enum constraint
        DB::statement("ALTER TABLE subject_convalidations DROP CONSTRAINT IF EXISTS subject_convalidations_convalidation_type_check");
        
        // Step 2: Change column to VARCHAR temporarily to allow value updates
        DB::statement("ALTER TABLE subject_convalidations ALTER COLUMN convalidation_type TYPE VARCHAR(50)");
        
        // Step 3: Update existing 'free_elective' values to new 'flexible_component' terminology
        DB::table('subject_convalidations')
            ->where('convalidation_type', 'free_elective')
            ->update(['convalidation_type' => 'flexible_component']);
        
        // Step 4: Recreate the constraint with the new enum values
        DB::statement("ALTER TABLE subject_convalidations ADD CONSTRAINT subject_convalidations_convalidation_type_check CHECK (convalidation_type IN ('direct', 'flexible_component', 'not_convalidated'))");
    }

    /**
     * Reverts the enum change back to the original 'free_elective' terminology.
     */
    public function down(): void
    {
        // Revert the enum change
        DB::statement("ALTER TABLE subject_convalidations DROP CONSTRAINT IF EXISTS subject_convalidations_convalidation_type_check");
        
        // Update 'flexible_component' back to 'free_elective'
        DB::table('subject_convalidations')
            ->where('convalidation_type', 'flexible_component')
            ->update(['convalidation_type' => 'free_elective']);
        
        // Recreate the original constraint with old values
        DB::statement("ALTER TABLE subject_convalidations ADD CONSTRAINT subject_convalidations_convalidation_type_check CHECK (convalidation_type IN ('direct', 'free_elective', 'not_convalidated'))");
    }
};
