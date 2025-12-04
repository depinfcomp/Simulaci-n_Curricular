<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates two tables: academic_history_imports (for tracking bulk import operations) and academic_histories
     * (for storing individual student academic records). The academic_histories table stores historical enrollment
     * data including grades, credits, and tracks how credits are distributed across curricular components with
     * overflow handling when component limits are exceeded.
     */
    public function up(): void
    {
        Schema::create('academic_history_imports', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this import operation');
            $table->string('filename')->comment('Stored filename in the storage/app directory');
            $table->string('original_filename')->comment('Original filename as uploaded by the user');
            $table->integer('total_records')->default(0)->comment('Total number of records found in the import file');
            $table->integer('successful_imports')->default(0)->comment('Number of records successfully imported');
            $table->integer('failed_imports')->default(0)->comment('Number of records that failed to import');
            $table->json('column_mapping')->nullable()->comment('Manual column mappings from Excel headers to database fields stored as JSON object');
            $table->json('import_summary')->nullable()->comment('Import statistics including students processed, subjects found, credits totals stored as JSON');
            $table->text('error_log')->nullable()->comment('Detailed error messages for failed imports');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->comment('Current status of the import operation');
            $table->foreignId('imported_by')->nullable()->constrained('users')->onDelete('set null')->comment('User who initiated this import (foreign key to users table)');
            $table->timestamps();
        });

        Schema::create('academic_histories', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this academic history record');
            $table->foreignId('import_id')->constrained('academic_history_imports')->onDelete('cascade')->comment('Foreign key to the import operation that created this record');
            $table->string('student_code')->index()->comment('Student identification code');
            $table->string('subject_code')->comment('Subject code identifier');
            $table->string('subject_name')->comment('Subject name cached for display');
            $table->string('grade')->nullable()->comment('Letter or alphanumeric grade (e.g., "AP", "4.5", "A")');
            $table->decimal('numeric_grade', 3, 1)->nullable()->comment('Numerical grade on 0.0 to 5.0 scale for calculations');
            $table->integer('credits')->default(0)->comment('Number of academic credits for this subject');
            $table->string('period')->nullable()->comment('Academic period when subject was taken (format: YYYY-S, e.g., "2024-1")');
            $table->enum('status', ['approved', 'failed', 'in_progress', 'pending'])->default('pending')->comment('Current status: approved (passed), failed (not passed), in_progress (currently taking), pending (not yet started)');
            
            // Credit tracking fields (tracks if credits count toward degree)
            $table->boolean('counts_towards_degree')->default(true)
                  ->comment('Whether these credits count toward degree completion (false for excess credits beyond component limits)');
            $table->string('assigned_component')->nullable()
                  ->comment('Curricular component where credits were assigned: fundamental_required, professional_required, optional_fundamental, optional_professional, free_elective, lost (for credits that do not count)');
            $table->integer('credits_counted')->default(0)
                  ->comment('Number of credits actually counted toward degree (may be less than total credits if component limit was exceeded)');
            
            // Credit distribution fields (tracks overflow to free elective)
            $table->integer('effective_credits')->default(0)->comment('Number of credits that count toward the original subject component');
            $table->integer('overflow_credits')->default(0)->comment('Number of credits redirected to free elective component when original component limit exceeded');
            $table->string('actual_component_type', 50)->nullable()->comment('The actual component where credits count (may differ from assigned_component if overflow occurred, typically becomes "free_elective")');
            $table->boolean('is_duplicate')->default(false)->comment('Indicates if student has taken this subject multiple times (repeated subjects are allowed for GPA recalculation but only one counts for credits)');
            $table->boolean('counts_for_percentage')->default(true)->comment('Indicates if this subject counts toward degree completion percentage (false if subject type is "na" or not applicable)');
            $table->text('assignment_notes')->nullable()->comment('Explanatory notes about credit assignment, e.g., "2 credits assigned to professional_required, 1 credit redirected to free_elective due to component limit"');
            
            $table->text('notes')->nullable()->comment('General notes or observations about this academic record');
            $table->timestamps();
            
            $table->index(['student_code', 'subject_code'])->comment('Composite index for finding specific student-subject records');
        });
    }

    /**
     * Drops both the academic_histories table (first, due to foreign key) and academic_history_imports table.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_histories');
        Schema::dropIfExists('academic_history_imports');
    }
};
