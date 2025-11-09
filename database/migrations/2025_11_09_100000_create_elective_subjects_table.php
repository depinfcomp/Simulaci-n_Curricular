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
        Schema::create('elective_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Subject code - Unique identifier');
            $table->string('name')->comment('Subject name');
            $table->integer('semester')->nullable()->comment('Recommended semester (1-10)');
            $table->integer('credits')->comment('Academic credits');
            $table->integer('classroom_hours')->default(0)->comment('Classroom hours per week');
            $table->integer('student_hours')->default(0)->comment('Student independent work hours per week');
            $table->enum('elective_type', ['optativa_fundamental', 'optativa_profesional'])
                  ->comment('Type of elective: fundamental or professional/disciplinary');
            $table->text('description')->nullable()->comment('Subject description');
            $table->boolean('is_active')->default(true)->comment('Whether the subject is currently offered');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('elective_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elective_subjects');
    }
};
