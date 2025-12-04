<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the external_curriculums table which stores external (target) curriculum definitions.
     * These are the curricula that students may migrate to, and serve as targets for convalidation
     * (course equivalency) mappings from the original curriculum.
     */
    public function up(): void
    {
        Schema::create('external_curriculums', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this external curriculum');
            $table->string('name')->comment('External curriculum name (e.g., "Systems Engineering 2024", "Computer Science Plan 2023")');
            $table->string('institution')->nullable()->comment('Name of the institution offering this curriculum (e.g., "UNAL")');
            $table->text('description')->nullable()->comment('Detailed description of the curriculum including objectives and structure');
            $table->string('uploaded_file')->nullable()->comment('File path to the original Excel file used to import this curriculum');
            $table->json('metadata')->nullable()->comment('Additional metadata including import date, file hash, version info stored as JSON');
            $table->string('pdf_report_path')->nullable()->comment('File path to the generated PDF impact analysis report');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Curriculum status: active (available for convalidation), inactive (archived or deprecated)');
            $table->timestamps();
        });
    }

    /**
     * Drops the external_curriculums table.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_curriculums');
    }
};
