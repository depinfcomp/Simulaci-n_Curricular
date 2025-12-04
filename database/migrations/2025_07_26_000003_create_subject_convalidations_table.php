<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the subject_convalidations table which defines equivalency mappings between external
     * curriculum subjects and internal curriculum subjects. This table establishes which external
     * subjects can be convalidated (recognized as equivalent) to internal subjects.
     */
    public function up(): void
    {
        Schema::create('subject_convalidations', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this convalidation mapping');
            $table->foreignId('external_curriculum_id')->constrained()->onDelete('cascade')->comment('Foreign key to external_curriculums table identifying which external curriculum this mapping belongs to');
            $table->foreignId('external_subject_id')->constrained()->onDelete('cascade')->comment('Foreign key to external_subjects table identifying the external subject being mapped');
            $table->string('internal_subject_code')->nullable()->comment('Internal subject code that the external subject is equivalent to (foreign key to subjects.code, nullable for free electives)');
            $table->enum('convalidation_type', ['direct', 'free_elective', 'not_convalidated'])->comment('Type of convalidation: direct (1:1 equivalency with internal subject), free_elective (counts as elective credit), not_convalidated (no credit recognition)');
            $table->text('notes')->nullable()->comment('Additional notes and observations about this convalidation mapping including justification and special conditions');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Approval status of this mapping: pending (awaiting review), approved (mapping confirmed), rejected (mapping denied)');
            $table->string('approved_by')->nullable()->comment('Username or ID of administrator who approved this convalidation mapping');
            $table->timestamp('approved_at')->nullable()->comment('Timestamp when the convalidation mapping was approved');
            $table->timestamps();
            
            $table->foreign('internal_subject_code')->references('code')->on('subjects')->onDelete('cascade');
            $table->unique(['external_subject_id', 'internal_subject_code'])->comment('Ensures each external subject can only map to a specific internal subject once');
        });
    }

    /**
     * Drops the subject_convalidations table.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_convalidations');
    }
};
