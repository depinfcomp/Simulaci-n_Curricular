<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the student_convalidations table which stores individual student convalidation requests.
     * Links students with their convalidation mappings (from subject_convalidations table) and tracks
     * the approval status, grades, and administrative notes for each convalidation.
     */
    public function up(): void
    {
        Schema::create('student_convalidations', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for this student convalidation record');
            $table->foreignId('student_id')->constrained()->onDelete('cascade')->comment('Foreign key to students table identifying who is requesting the convalidation');
            $table->foreignId('subject_convalidation_id')->constrained()->onDelete('cascade')->comment('Foreign key to subject_convalidations table defining which convalidation mapping applies');
            $table->decimal('external_grade', 4, 2)->comment('Grade obtained in the external subject (on external institution scale)');
            $table->decimal('internal_grade', 4, 2)->nullable()->comment('Grade converted to internal grading system (0.00 to 5.00 scale)');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Convalidation request status: pending (awaiting review), approved (convalidation granted), rejected (convalidation denied)');
            $table->text('admin_notes')->nullable()->comment('Administrative notes and comments explaining approval or rejection decisions');
            $table->string('processed_by')->nullable()->comment('Username or ID of the administrator who processed this convalidation');
            $table->timestamp('processed_at')->nullable()->comment('Timestamp when the convalidation was approved or rejected');
            $table->timestamps();
            
            $table->unique(['student_id', 'subject_convalidation_id'])->comment('Ensures each student can only have one convalidation request per subject mapping');
        });
    }

    /**
     * Drops the student_convalidations table.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_convalidations');
    }
};
