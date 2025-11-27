<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a table for N:N convalidation relationships.
     * Allows one external subject to be equivalent to multiple internal subjects.
     * Example: "Nueva Materia Combinada" = "Materia A" + "Materia B"
     */
    public function up(): void
    {
        Schema::create('convalidation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_curriculum_id')
                  ->constrained('external_curriculums')
                  ->onDelete('cascade')
                  ->comment('External curriculum this group belongs to');
            
            $table->foreignId('external_subject_id')
                  ->constrained('external_subjects')
                  ->onDelete('cascade')
                  ->comment('The external subject that represents the group');
            
            $table->string('group_name')
                  ->comment('Descriptive name for this equivalence group');
            
            $table->text('description')
                  ->nullable()
                  ->comment('Explanation of why these subjects are equivalent');
            
            $table->enum('equivalence_type', ['all', 'any', 'credits'])
                  ->default('all')
                  ->comment('all = must have ALL subjects, any = ANY subject counts, credits = sum of credits');
            
            $table->decimal('equivalence_percentage', 5, 2)
                  ->default(100.00)
                  ->comment('Percentage of equivalence (100 = full, 50 = half credit, etc.)');
            
            $table->json('metadata')
                  ->nullable()
                  ->comment('Additional configuration');
            
            $table->timestamps();
            
            $table->index(['external_curriculum_id', 'external_subject_id']);
        });
        
        // Pivot table for the N:N relationship between external subject and internal subjects
        Schema::create('convalidation_group_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convalidation_group_id')
                  ->constrained('convalidation_groups')
                  ->onDelete('cascade');
            
            $table->string('internal_subject_code')
                  ->comment('Code of internal subject in the group');
            
            $table->integer('sort_order')
                  ->default(0)
                  ->comment('Order of subjects in the group');
            
            $table->decimal('weight', 5, 2)
                  ->default(1.00)
                  ->comment('Weight of this subject in the equivalence (for partial credits)');
            
            $table->text('notes')
                  ->nullable()
                  ->comment('Specific notes for this subject in the group');
            
            $table->timestamps();
            
            // Foreign key to subjects table
            $table->foreign('internal_subject_code')
                  ->references('code')
                  ->on('subjects')
                  ->onDelete('cascade');
            
            // Unique constraint: each internal subject can only be in a group once
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
