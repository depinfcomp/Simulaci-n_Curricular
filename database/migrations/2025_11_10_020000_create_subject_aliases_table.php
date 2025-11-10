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
        // Drop if exists to avoid conflicts
        Schema::dropIfExists('subject_aliases');
        
        Schema::create('subject_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 20); // Código principal de la materia
            $table->string('alias_code', 20);   // Código alias (histórico/alternativo)
            $table->text('description')->nullable(); // Descripción del alias
            $table->timestamps();
            
            // Índices para búsqueda rápida
            $table->index('subject_code');
            $table->index('alias_code');
            
            // Evitar duplicados
            $table->unique(['subject_code', 'alias_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_aliases');
    }
};
