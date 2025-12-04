<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates two tables for curriculum version control: curriculum_versions (stores snapshots of entire
     * curriculum states) and curriculum_version_subjects (stores individual subjects for each version).
     * This allows tracking curriculum evolution over time and reverting to previous configurations.
     */
    public function up(): void
    {
        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this curriculum version');
            $table->string('version_number', 10)->comment('Semantic version number (format: Major.Minor, e.g., "1.0", "1.1", "2.0")');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Foreign key to users table identifying who created this version');
            $table->text('description')->nullable()->comment('Optional description of changes made in this version (e.g., "Added 3 new professional subjects, removed deprecated course X")');
            $table->boolean('is_current')->default(false)->comment('Flag indicating if this is the currently active curriculum version (only one version should be marked as current)');
            $table->json('curriculum_data')->comment('Complete snapshot of the curriculum state stored as JSON including all configuration, metadata, and structural information');
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('version_number')->comment('Index for version number lookups');
            $table->index('is_current')->comment('Index for quickly finding the current active version');
            $table->index('user_id')->comment('Index for finding versions by creator');
        });
        
        // Table to store individual subjects for each curriculum version
        Schema::create('curriculum_version_subjects', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this subject within a version');
            $table->foreignId('curriculum_version_id')->constrained('curriculum_versions')->onDelete('cascade')->comment('Foreign key to curriculum_versions table linking this subject to a specific version');
            $table->string('code', 20)->comment('Subject code identifier (may be reused across versions if subject persists)');
            $table->string('name', 255)->comment('Full name of the subject');
            $table->integer('semester')->comment('Recommended semester number in curriculum sequence (1-10)');
            $table->integer('credits')->default(3)->comment('Number of academic credits this subject is worth');
            $table->integer('classroom_hours')->default(3)->comment('Number of classroom contact hours per week with instructor');
            $table->integer('student_hours')->default(6)->comment('Number of independent student work hours per week (homework, study, projects)');
            $table->string('type', 50)->default('profesional')->comment('Subject type indicating curricular component (fundamental, profesional, optativa, etc.)');
            $table->boolean('is_required')->default(true)->comment('Flag indicating if subject is required (true) or elective/optional (false)');
            $table->text('description')->nullable()->comment('Detailed description of subject content, learning objectives, and prerequisites');
            $table->integer('display_order')->default(0)->comment('Sort order for displaying subjects within the same semester (0 = first, higher numbers display later)');
            $table->json('prerequisites')->nullable()->comment('Array of prerequisite subject codes stored as JSON, e.g., ["2025413", "2025414"]');
            $table->timestamps();
            
            // Indexes for query performance
            $table->index(['curriculum_version_id', 'semester'])->comment('Composite index for finding subjects by version and semester');
            $table->index('code')->comment('Index for subject code lookups');
        });
    }

    /**
     * Drops both the curriculum_version_subjects table (first, due to foreign key) and curriculum_versions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_version_subjects');
        Schema::dropIfExists('curriculum_versions');
    }
};
