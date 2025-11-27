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
        Schema::create('subject_convalidations', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementable ID');
            $table->foreignId('external_curriculum_id')->constrained()->onDelete('cascade')->comment('Foreign key to external_curriculums');
            $table->foreignId('external_subject_id')->constrained()->onDelete('cascade')->comment('Foreign key to external_subjects');
            $table->string('internal_subject_code')->nullable()->comment('Internal subject code (null for free electives)');
            $table->enum('convalidation_type', ['direct', 'free_elective', 'not_convalidated'])->comment('Convalidation type');
            $table->text('notes')->nullable()->comment('Convalidation notes and observations');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Approval status');
            $table->string('approved_by')->nullable()->comment('User who approved the convalidation');
            $table->timestamp('approved_at')->nullable()->comment('Approval timestamp');
            $table->timestamps();
            
            $table->foreign('internal_subject_code')->references('code')->on('subjects')->onDelete('cascade');
            $table->unique(['external_subject_id', 'internal_subject_code']); // One convalidation per external subject
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_convalidations');
    }
};
