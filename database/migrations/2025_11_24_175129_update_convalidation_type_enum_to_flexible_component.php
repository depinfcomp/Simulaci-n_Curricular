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
        // For PostgreSQL, we need to use raw SQL to modify the enum
        // Replace 'free_elective' with 'flexible_component' in the enum
        DB::statement("ALTER TABLE subject_convalidations DROP CONSTRAINT IF EXISTS subject_convalidations_convalidation_type_check");
        DB::statement("ALTER TABLE subject_convalidations ALTER COLUMN convalidation_type TYPE VARCHAR(50)");
        
        // Update existing 'free_elective' values to 'flexible_component'
        DB::table('subject_convalidations')
            ->where('convalidation_type', 'free_elective')
            ->update(['convalidation_type' => 'flexible_component']);
        
        // Recreate the constraint with the new enum values
        DB::statement("ALTER TABLE subject_convalidations ADD CONSTRAINT subject_convalidations_convalidation_type_check CHECK (convalidation_type IN ('direct', 'flexible_component', 'not_convalidated'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the old enum
        DB::statement("ALTER TABLE subject_convalidations DROP CONSTRAINT IF EXISTS subject_convalidations_convalidation_type_check");
        
        // Update 'flexible_component' back to 'free_elective'
        DB::table('subject_convalidations')
            ->where('convalidation_type', 'flexible_component')
            ->update(['convalidation_type' => 'free_elective']);
        
        // Recreate the old constraint
        DB::statement("ALTER TABLE subject_convalidations ADD CONSTRAINT subject_convalidations_convalidation_type_check CHECK (convalidation_type IN ('direct', 'free_elective', 'not_convalidated'))");
    }
};
