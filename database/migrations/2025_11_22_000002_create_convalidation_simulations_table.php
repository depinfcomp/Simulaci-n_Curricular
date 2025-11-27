<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores convalidation simulation sessions.
     * Each simulation compares an original curriculum with a new/imported curriculum
     * to show how students would be affected by the transition.
     */
    public function up(): void
    {
        Schema::create('convalidation_simulations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Descriptive name for the simulation');
            $table->text('description')->nullable()->comment('Additional details about the simulation');
            
            // The original curriculum (usually the current internal curriculum represented as null or a specific version)
            $table->foreignId('original_curriculum_id')->nullable()->constrained('external_curriculums')->onDelete('cascade')
                  ->comment('Original curriculum (null = internal curriculum)');
            
            // The new/imported curriculum to compare against
            $table->foreignId('new_curriculum_id')->constrained('external_curriculums')->onDelete('cascade')
                  ->comment('New/imported curriculum');
            
            $table->enum('status', ['draft', 'completed', 'confirmed'])->default('draft')
                  ->comment('draft = being configured, completed = simulation run, confirmed = saved to real data');
            
            $table->json('configuration')->nullable()->comment('Simulation parameters and settings');
            $table->json('summary_stats')->nullable()->comment('Aggregate statistics from simulation results');
            
            $table->string('created_by')->nullable()->comment('User who created the simulation');
            $table->string('confirmed_by')->nullable()->comment('User who confirmed and applied the simulation');
            $table->timestamp('confirmed_at')->nullable()->comment('When the simulation was confirmed');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convalidation_simulations');
    }
};
