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
            
            // Documento del estudiante (en lugar de student_id)
            $table->string('student_document', 50)->index()->comment('Student document number');
            
            // Información de la asignatura (desnormalizado para facilitar consultas)
            $table->string('subject_code', 20)->index()->comment('Subject code');
            $table->string('subject_name', 255)->comment('Subject name');
            $table->integer('subject_credits')->comment('Subject credits');
            $table->string('subject_type', 50)->comment('Subject type (fundamental, profesional, etc.)');
            
            // Información de calificación
            $table->decimal('grade', 3, 2)->nullable()->comment('Numeric grade (0.00 to 5.00)');
            $table->string('alphabetic_grade', 5)->nullable()->comment('Alphabetic grade (AP/RE)');
            $table->enum('status', ['enrolled', 'passed', 'failed', 'withdrawn'])->default('enrolled')->comment('Enrollment status');
            
            // Período académico
            $table->string('period', 20)->nullable()->comment('Academic period');
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['student_document', 'subject_code']);
            $table->index(['student_document', 'status']);
            $table->index('subject_type');
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
