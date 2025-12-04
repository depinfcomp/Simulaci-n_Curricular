<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the subjects table which stores the original UNAL curriculum subjects.
     * This table contains all subjects from the current Systems Engineering program.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->string('code', 10)->primary()->comment('Unique subject code - Primary key');
            $table->string('name')->comment('Full name of the subject');
            $table->integer('semester')->comment('Semester number where the subject is normally taken (1-10)');
            $table->integer('display_order')->default(0)->comment('Display order within the semester for UI presentation');
            $table->integer('credits')->comment('Number of academic credits the subject is worth');
            $table->integer('classroom_hours')->default(0)->comment('Number of classroom hours per week');
            $table->integer('student_hours')->default(0)->comment('Number of student independent work hours per week');
            $table->enum('type', ['fundamental', 'profesional', 'optativa_profesional', 'optativa_fundamentacion', 'libre_eleccion', 'nivelacion', 'trabajo_grado'])
                  ->default('fundamental')
                  ->comment('Subject type classification: fundamental (core basic), profesional (core professional), optativa_profesional (professional elective), optativa_fundamentacion (fundamental elective), libre_eleccion (free elective), nivelacion (leveling), trabajo_grado (thesis)');
            $table->boolean('is_required')->default(true)->comment('Whether the subject is required (true) or elective (false)');
            $table->boolean('is_leveling')->default(false)->comment('Whether this is a leveling subject (true) or counts toward career credits (false)');
            $table->timestamps();
            
            // Indexes for performance optimization
            $table->index(['semester', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the subjects table.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
