<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the external_subjects table which stores subjects from external (target) curricula.
     * These subjects are used as targets for convalidation (course equivalency mapping) from the
     * original curriculum. Tracks subject changes during curriculum evolution (added, removed, moved).
     */
    public function up(): void
    {
        Schema::create('external_subjects', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this external subject');
            $table->foreignId('external_curriculum_id')->constrained()->onDelete('cascade')->comment('Foreign key to external_curriculums table defining which curriculum this subject belongs to');
            $table->string('code')->comment('External subject code identifier (unique within each curriculum)');
            $table->string('name')->comment('External subject full name');
            $table->integer('credits')->comment('Number of academic credits this subject is worth');
            $table->integer('semester')->comment('Recommended semester number in the curriculum sequence');
            $table->integer('display_order')
                  ->default(0)
                  ->comment('Visual position/order of the subject within its semester (0-indexed)');
            $table->text('description')->nullable()->comment('Detailed subject description including learning objectives and content');
            $table->json('additional_data')->nullable()->comment('Additional metadata from Excel import (prerequisite codes, extra attributes, etc.)');
            
            // Change tracking fields (tracks curriculum evolution)
            $table->enum('change_type', ['added', 'removed', 'modified', 'moved', 'unchanged'])
                  ->nullable()
                  ->comment('Type of change made during curriculum simulation: added (new subject), removed (deleted subject), modified (content changed), moved (semester changed), unchanged (no modifications)');
            $table->integer('original_semester')
                  ->nullable()
                  ->comment('Original semester before any modifications (used to track moved subjects)');
            $table->json('change_details')
                  ->nullable()
                  ->comment('Detailed information about the change including modified fields (prerequisites, credits, name, etc.) stored as JSON object');
            
            $table->timestamps();
            
            $table->unique(['external_curriculum_id', 'code'])->comment('Ensures each subject code is unique within its curriculum');
        });
    }

    /**
     * Drops the external_subjects table.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_subjects');
    }
};
