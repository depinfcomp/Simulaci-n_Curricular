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
        Schema::create('curriculum_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_number', 10); // Format: 1.0, 1.1, ..., 1.10, 2.0, etc.
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Usuario que creó la versión
            $table->text('description')->nullable(); // Descripción opcional de cambios
            $table->boolean('is_current')->default(false); // Marca la versión actual
            $table->json('curriculum_data'); // Almacena el estado completo de la malla en JSON
            $table->timestamps();
            
            // Indices
            $table->index('version_number');
            $table->index('is_current');
            $table->index('user_id');
        });
        
        // Tabla para almacenar las materias de cada versión
        Schema::create('curriculum_version_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_version_id')->constrained('curriculum_versions')->onDelete('cascade');
            $table->string('code', 20);
            $table->string('name', 255);
            $table->integer('semester');
            $table->integer('credits')->default(3);
            $table->integer('classroom_hours')->default(3);
            $table->integer('student_hours')->default(6);
            $table->string('type', 50)->default('profesional');
            $table->boolean('is_required')->default(true);
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->json('prerequisites')->nullable(); // Array de códigos de prerrequisitos
            $table->timestamps();
            
            // Indices
            $table->index(['curriculum_version_id', 'semester']);
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_version_subjects');
        Schema::dropIfExists('curriculum_versions');
    }
};
