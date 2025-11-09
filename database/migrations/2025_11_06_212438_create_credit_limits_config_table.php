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
        Schema::create('credit_limits_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_curriculum_id')
                  ->nullable()
                  ->constrained('external_curriculums')
                  ->onDelete('cascade')
                  ->comment('Related external curriculum (null for global defaults)');
            
            // Límites de créditos por componente curricular (todos obligatorios)
            $table->integer('max_free_elective_credits')
                  ->comment('Máximo de créditos de libre elección');
            
            $table->integer('max_optional_professional_credits')
                  ->comment('Máximo de créditos del componente disciplinar optativo');
            
            $table->integer('max_required_fundamental_credits')
                  ->comment('Máximo de créditos del componente fundamental obligatorio');
            
            $table->integer('max_optional_fundamental_credits')
                  ->comment('Máximo de créditos del componente fundamental optativo');
            
            $table->integer('max_required_professional_credits')
                  ->comment('Máximo de créditos del componente disciplinar obligatorio');
            
            $table->integer('max_leveling_credits')
                  ->comment('Máximo de créditos del componente de nivelación');
            
            $table->integer('max_thesis_credits')
                  ->comment('Máximo de créditos del componente de trabajo de grado');
            
            $table->timestamps();
            
            // Índice único para evitar duplicados por curriculum
            $table->unique('external_curriculum_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_limits_config');
    }
};
