<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates two tables for N:N convalidation relationships where one external subject can be equivalent
     * to multiple internal subjects. The convalidation_groups table defines equivalence groups, and
     * convalidation_group_subjects is the pivot table linking groups to their internal subjects.
     * 
     * Example: External subject "Combined Database Course" = Internal subjects "Database I" + "Database II"
     */
    public function up(): void
    {
        Schema::create('convalidation_groups', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this convalidation group');
            $table->foreignId('external_curriculum_id')
                  ->constrained('external_curriculums')
                  ->onDelete('cascade')
                  ->comment('Foreign key to the external curriculum this group belongs to');
            
            $table->foreignId('external_subject_id')
                  ->constrained('external_subjects')
                  ->onDelete('cascade')
                  ->comment('Foreign key to the external subject that represents the entire group equivalence');
            
            $table->string('group_name')
                  ->comment('Descriptive name for this equivalence group (e.g., "Advanced Programming Combination")');
            
            $table->text('description')
                  ->nullable()
                  ->comment('Detailed explanation of why these subjects are equivalent and how credits are distributed');
            
            $table->enum('equivalence_type', ['all', 'any', 'credits'])
                  ->default('all')
                  ->comment('Equivalence rule: all (student must have ALL internal subjects), any (ANY single internal subject counts), credits (sum credits from any combination until threshold met)');
            
            $table->decimal('equivalence_percentage', 5, 2)
                  ->default(100.00)
                  ->comment('Percentage of equivalence granted (100.00 = full credit, 50.00 = half credit, etc.)');
            
            // Component type field identifies curricular component
            $table->enum('component_type', [
                'fundamental_required',
                'professional_required',
                'optional_fundamental',
                'optional_professional',
                'free_elective',
                'thesis',
                'leveling'
            ])->nullable()
              ->comment('Curricular component this group belongs to (determines where credits count)');
            
            $table->json('metadata')
                  ->nullable()
                  ->comment('Additional configuration options stored as JSON (credit thresholds, special rules, etc.)');
            
            $table->timestamps();
            
            $table->index(['external_curriculum_id', 'external_subject_id'])->comment('Composite index for finding groups by curriculum and external subject');
        });
        
        // Pivot table for the many-to-many relationship between convalidation groups and internal subjects
        Schema::create('convalidation_group_subjects', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this group-subject relationship');
            $table->foreignId('convalidation_group_id')
                  ->constrained('convalidation_groups')
                  ->onDelete('cascade')
                  ->comment('Foreign key to the convalidation group this subject belongs to');
            
            $table->string('internal_subject_code')
                  ->comment('Code of the internal subject in this equivalence group (foreign key to subjects.code)');
            
            $table->integer('sort_order')
                  ->default(0)
                  ->comment('Display order of subjects within the group (for UI presentation)');
            
            $table->decimal('weight', 5, 2)
                  ->default(1.00)
                  ->comment('Weight of this subject in the equivalence calculation (for partial credit scenarios, typically 1.00 for equal weight)');
            
            $table->text('notes')
                  ->nullable()
                  ->comment('Specific notes or conditions for this subject within the group');
            
            $table->timestamps();
            
            // Foreign key to subjects table
            $table->foreign('internal_subject_code')
                  ->references('code')
                  ->on('subjects')
                  ->onDelete('cascade');
            
            // Unique constraint: each internal subject can only appear once per group
            $table->unique(['convalidation_group_id', 'internal_subject_code'], 'group_subject_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convalidation_group_subjects');
        Schema::dropIfExists('convalidation_groups');
    }
};
