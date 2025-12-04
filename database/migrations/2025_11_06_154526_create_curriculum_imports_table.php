<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the curriculum_imports table which tracks the multi-step process of importing curriculum
     * data from Excel files. This table manages the workflow from file upload through automatic column
     * detection, manual mapping, validation, data completion, and final import execution.
     */
    public function up(): void
    {
        Schema::create('curriculum_imports', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this import operation');
            $table->string('original_filename')->comment('Original filename of the uploaded Excel file as provided by the user');
            $table->string('stored_path')->comment('Server file path where the uploaded Excel file is stored (relative to storage directory)');
            $table->enum('status', [
                'uploaded',      // File uploaded successfully, awaiting analysis
                'analyzing',     // System is analyzing file format and detecting columns
                'mapping',       // User is manually mapping columns to database fields
                'validating',    // System is validating the mapped data
                'filling',       // User is completing missing or invalid data
                'confirmed',     // Data validated and ready for import
                'importing',     // Import process is actively running
                'completed',     // Import finished successfully
                'failed'         // Import failed at some step
            ])->default('uploaded')->comment('Current status of the import workflow');
            
            // Automatic analysis metadata
            $table->integer('header_row')->nullable()->comment('Zero-indexed row number where column headers are located in the Excel file');
            $table->integer('data_start_row')->nullable()->comment('Zero-indexed row number where actual data begins (typically header_row + 1)');
            $table->integer('total_rows')->nullable()->comment('Total number of data rows found in the Excel file');
            
            // Column mapping data (stored as JSON)
            $table->json('column_mapping')->nullable()->comment('User-confirmed mapping from Excel columns to database fields stored as JSON object, e.g., {"A":"code", "B":"name", "C":"credits"}');
            $table->json('detected_columns')->nullable()->comment('Automatically detected column mappings with confidence scores, e.g., [{"column":"A", "field":"code", "confidence":0.95}]');
            $table->json('required_fields_status')->nullable()->comment('Status of required fields mapping stored as JSON, e.g., {"code": true, "name": true, "credits": false} indicating which required fields are mapped');
            
            // Processed data
            $table->json('preview_data')->nullable()->comment('First 10 rows of data for user preview before import, stored as JSON array of row objects');
            $table->json('validation_errors')->nullable()->comment('Validation errors grouped by row number, e.g., {"5": ["Invalid credit value"], "12": ["Missing subject name"]}');
            $table->json('missing_data_rows')->nullable()->comment('Row numbers that have missing required data and need user intervention to complete');
            
            // Import results
            $table->integer('subjects_imported')->default(0)->comment('Total number of subjects successfully imported into the database');
            $table->json('import_summary')->nullable()->comment('Detailed summary of import results including counts, warnings, and statistics stored as JSON');
            $table->text('error_message')->nullable()->comment('Detailed error message if the import process failed at any step');
            
            // Template support
            $table->string('template_name')->nullable()->comment('Name of the predefined template used for this import (if any), for tracking common import formats');
            
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp to allow recovery of import records');
        });
    }

    /**
     * Drops the curriculum_imports table.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_imports');
    }
};
