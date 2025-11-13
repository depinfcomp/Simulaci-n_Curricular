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
        Schema::table('academic_histories', function (Blueprint $table) {
            // Créditos efectivos que cuentan para el componente original
            $table->integer('effective_credits')->default(0)->after('credits');
            
            // Créditos que fueron redirigidos a libre elección (overflow)
            $table->integer('overflow_credits')->default(0)->after('effective_credits');
            
            // Componente real al que fue asignada la materia (puede diferir del original si hay overflow)
            // Valores: 'fundamentacion', 'profesional', 'disciplinar_optativo', 'libre_eleccion', 'nivelacion', 'na'
            $table->string('actual_component_type', 50)->nullable()->after('overflow_credits');
            
            // Indica si es una materia repetida (duplicado) - se permite para cálculo de promedio
            $table->boolean('is_duplicate')->default(false)->after('actual_component_type');
            
            // Indica si esta materia cuenta para el porcentaje de avance (false si es tipo 'na')
            $table->boolean('counts_for_percentage')->default(true)->after('is_duplicate');
            
            // Notas sobre la asignación (ej: "2 créditos al componente, 1 a libre elección")
            $table->text('assignment_notes')->nullable()->after('counts_for_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_histories', function (Blueprint $table) {
            $table->dropColumn([
                'effective_credits',
                'overflow_credits',
                'actual_component_type',
                'is_duplicate',
                'counts_for_percentage',
                'assignment_notes'
            ]);
        });
    }
};
