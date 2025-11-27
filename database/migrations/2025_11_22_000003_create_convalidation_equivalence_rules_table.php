<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores N:N equivalence rules between subjects from different curricula.
     * One subject from the original curriculum can be equivalent to multiple subjects
     * in the new curriculum, and vice versa.
     */
    public function up(): void
    {
        Schema::create('convalidation_equivalence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('convalidation_simulations')->onDelete('cascade')
                  ->comment('The simulation this rule belongs to');
            
            // Original subject (can be internal subject or external subject)
            $table->string('original_subject_type')->comment('internal or external');
            $table->string('original_subject_code')->comment('Subject code from original curriculum');
            
            // New subject (usually from external/imported curriculum)
            $table->string('new_subject_type')->comment('internal or external');
            $table->string('new_subject_code')->comment('Subject code from new curriculum');
            
            // Equivalence details
            $table->enum('equivalence_type', ['direct', 'group'])
                  ->comment('direct = 1:1 equivalence, group = multiple subjects form complete equivalence');
            
            $table->text('notes')->nullable()->comment('Explanation of the equivalence');
            $table->string('created_by')->nullable()->comment('User who created this rule');
            
            $table->timestamps();
            
            // Index for fast lookups
            $table->index(['simulation_id', 'original_subject_code']);
            $table->index(['simulation_id', 'new_subject_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convalidation_equivalence_rules');
    }
};
