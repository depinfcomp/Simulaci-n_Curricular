<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores the calculated results for each student in a simulation.
     * Shows how each student's progress would change if transferred to the new curriculum.
     */
    public function up(): void
    {
        Schema::create('simulation_student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('convalidation_simulations')->onDelete('cascade');
            $table->string('student_document')->comment('Student document number');
            
            // Original curriculum metrics
            $table->decimal('original_progress_percentage', 5, 2)->default(0)
                  ->comment('Current progress percentage in original curriculum');
            $table->integer('original_approved_credits')->default(0)
                  ->comment('Approved credits in original curriculum');
            $table->json('original_component_distribution')->nullable()
                  ->comment('Credits by component in original curriculum');
            
            // New curriculum metrics
            $table->decimal('new_progress_percentage', 5, 2)->default(0)
                  ->comment('Projected progress percentage in new curriculum');
            $table->integer('new_approved_credits')->default(0)
                  ->comment('Credits that would count in new curriculum');
            $table->json('new_component_distribution')->nullable()
                  ->comment('Credits by component in new curriculum');
            
            // Change analysis
            $table->decimal('progress_change', 5, 2)->default(0)
                  ->comment('Difference in progress percentage (new - original)');
            $table->integer('credits_lost')->default(0)
                  ->comment('Credits that would not convalidate');
            $table->integer('credits_gained')->default(0)
                  ->comment('Additional credits recognized');
            
            // Detailed breakdown
            $table->json('convalidated_subjects')->nullable()
                  ->comment('List of subjects that convalidate and their mappings');
            $table->json('non_convalidated_subjects')->nullable()
                  ->comment('List of subjects that do not convalidate');
            $table->json('impact_details')->nullable()
                  ->comment('Detailed explanation of impact on student');
            
            $table->timestamps();
            
            // Index for fast student lookups
            $table->index(['simulation_id', 'student_document']);
            $table->foreign('student_document')->references('document')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_student_results');
    }
};
