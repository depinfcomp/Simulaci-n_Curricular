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
        Schema::create('curriculum_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename')->comment('Nombre del archivo Excel original');
            $table->string('stored_path')->comment('Ruta donde se guardó el archivo');
            $table->enum('status', [
                'uploaded',      // Archivo subido, esperando análisis
                'analyzing',     // Detectando formato y columnas
                'mapping',       // Usuario mapeando columnas manualmente
                'validating',    // Validando datos detectados
                'filling',       // Completando datos faltantes
                'confirmed',     // Listo para importar
                'importing',     // Proceso de importación en curso
                'completed',     // Importación exitosa
                'failed'         // Falló en algún paso
            ])->default('uploaded')->comment('Estado del proceso de importación');
            
            // Metadatos del análisis automático
            $table->integer('header_row')->nullable()->comment('Fila donde están los encabezados (0-indexed)');
            $table->integer('data_start_row')->nullable()->comment('Fila donde empiezan los datos (0-indexed)');
            $table->integer('total_rows')->nullable()->comment('Total de filas con datos');
            
            // Mapeo de columnas (JSON)
            $table->json('column_mapping')->nullable()->comment('Mapeo de columnas Excel a campos: {"A":"code", "B":"name", ...}');
            $table->json('detected_columns')->nullable()->comment('Columnas detectadas automáticamente con confianza');
            $table->json('required_fields_status')->nullable()->comment('Estado de campos requeridos: {"code": true, "name": true, ...}');
            
            // Datos procesados
            $table->json('preview_data')->nullable()->comment('Primeras 10 filas para preview');
            $table->json('validation_errors')->nullable()->comment('Errores de validación por fila');
            $table->json('missing_data_rows')->nullable()->comment('Filas que requieren completar datos');
            
            // Resultado de la importación
            $table->integer('subjects_imported')->default(0)->comment('Cantidad de materias importadas');
            $table->json('import_summary')->nullable()->comment('Resumen de la importación');
            $table->text('error_message')->nullable()->comment('Mensaje de error si falló');
            
            // Plantilla usada
            $table->string('template_name')->nullable()->comment('Nombre de plantilla si se usó una');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_imports');
    }
};
