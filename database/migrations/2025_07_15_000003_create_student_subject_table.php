<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_subject', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->foreignId('student_id')->constrained()->onDelete('cascade')->comment('Foreign key to students table');
            $table->string('subject_code', 10)->comment('Foreign key to subjects table');
            $table->foreign('subject_code')->references('code')->on('subjects')->onDelete('cascade');
            $table->decimal('grade', 3, 1)->nullable()->comment('Final grade (0.0 to 5.0)');
            $table->enum('status', ['enrolled', 'passed', 'failed', 'withdrawn'])->default('enrolled')->comment('Enrollment status');
            $table->timestamps();
            
            // Ensure a student can't be enrolled in the same subject twice
            $table->unique(['student_id', 'subject_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject');
    }
};
