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
        Schema::create('student_convalidations', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->foreignId('student_id')->constrained()->onDelete('cascade')->comment('Foreign key to students');
            $table->foreignId('subject_convalidation_id')->constrained()->onDelete('cascade')->comment('Foreign key to subject_convalidations');
            $table->decimal('external_grade', 4, 2)->comment('Grade obtained in external subject');
            $table->decimal('internal_grade', 4, 2)->nullable()->comment('Grade converted to internal system');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Convalidation status');
            $table->text('admin_notes')->nullable()->comment('Administrator notes and comments');
            $table->string('processed_by')->nullable()->comment('User who processed the convalidation');
            $table->timestamp('processed_at')->nullable()->comment('Processing timestamp');
            $table->timestamps();
            
            $table->unique(['student_id', 'subject_convalidation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_convalidations');
    }
};
