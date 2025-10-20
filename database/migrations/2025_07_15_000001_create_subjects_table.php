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
        Schema::create('subjects', function (Blueprint $table) {
            $table->string('code', 10)->primary()->comment('Subject code - Primary key');
            $table->string('name')->comment('Subject name');
            $table->integer('semester')->comment('Semester number (1-10)');
            $table->integer('credits')->comment('Academic credits');
            $table->integer('classroom_hours')->default(0)->comment('Classroom hours per week');
            $table->integer('student_hours')->default(0)->comment('Student independent work hours per week');
            $table->enum('type', ['fundamental', 'profesional', 'optativa_profesional', 'optativa_fundamentacion', 'libre_eleccion', 'lengua_extranjera'])
                  ->default('fundamental')
                  ->comment('Subject type classification');
            $table->boolean('is_required')->default(true)->comment('True for required subjects, false for elective');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
