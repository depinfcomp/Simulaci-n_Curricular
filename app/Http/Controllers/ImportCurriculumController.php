<?php

namespace App\Http\Controllers;

use App\Models\CurriculumImport;
use App\Models\ExternalCurriculum;
use App\Models\ExternalSubject;
use App\Services\CurriculumImportAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportCurriculumController extends Controller
{
    protected $analyzer;

    public function __construct(CurriculumImportAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Mostrar vista principal del wizard de importación
     */
    public function index()
    {
        return view('convalidation.import.index');
    }

    /**
     * Paso 1: Subir archivo Excel
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
            'curriculum_name' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
        ]);

        try {
            // Guardar archivo
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $storedPath = $file->store('curriculum_imports', 'local');

            // Crear registro de importación
            $import = CurriculumImport::create([
                'original_filename' => $originalName,
                'stored_path' => $storedPath,
                'status' => 'uploaded',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo subido exitosamente',
                'import_id' => $import->id,
                'next_step' => 'analyze',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paso 2: Analizar archivo y detectar formato
     */
    public function analyze(Request $request, CurriculumImport $import)
    {
        if ($import->status !== 'uploaded') {
            return response()->json([
                'success' => false,
                'message' => 'El archivo ya fue analizado o está en otro estado'
            ], 400);
        }

        try {
            $import->updateStatus('analyzing');

            // Obtener ruta completa del archivo usando Storage
            $filePath = Storage::disk('local')->path($import->stored_path);

            // Analizar archivo
            $analysis = $this->analyzer->analyze($filePath);

            // Guardar resultados del análisis
            $import->update([
                'header_row' => $analysis['header_row'],
                'data_start_row' => $analysis['data_start_row'],
                'total_rows' => $analysis['total_rows'],
                'column_mapping' => $analysis['column_mapping'],
                'detected_columns' => $analysis['detected_columns'],
                'required_fields_status' => $analysis['required_fields_status'],
                'preview_data' => $analysis['preview_data'],
            ]);

            // Determinar siguiente paso
            $allRequiredMapped = !in_array(false, $analysis['required_fields_status'], true);
            $nextStep = $allRequiredMapped ? 'validate' : 'mapping';
            $nextStatus = $allRequiredMapped ? 'validating' : 'mapping';

            $import->updateStatus($nextStatus);

            return response()->json([
                'success' => true,
                'message' => 'Análisis completado',
                'import_id' => $import->id,
                'analysis' => $analysis,
                'next_step' => $nextStep,
                'all_required_mapped' => $allRequiredMapped,
                'missing_fields' => $analysis['missing_required_fields'] ?? [],
                'available_fields' => CurriculumImportAnalyzer::getAvailableFields(),
            ]);
        } catch (\Exception $e) {
            $import->updateStatus('failed');
            $import->update(['error_message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al analizar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paso 3: Actualizar mapeo de columnas manualmente
     */
    public function updateMapping(Request $request, CurriculumImport $import)
    {
        $request->validate([
            'column_mapping' => 'required|array',
        ]);

        try {
            $newMapping = $request->column_mapping;

            // Verificar que campos requeridos estén mapeados
            $requiredFields = ['code', 'name', 'semester', 'credits'];
            $mappedFields = array_values($newMapping);

            foreach ($requiredFields as $field) {
                if (!in_array($field, $mappedFields)) {
                    return response()->json([
                        'success' => false,
                        'message' => "El campo requerido '{$field}' no está mapeado"
                    ], 400);
                }
            }

            // Actualizar mapeo
            $import->update([
                'column_mapping' => $newMapping,
                'required_fields_status' => [
                    'code' => in_array('code', $mappedFields),
                    'name' => in_array('name', $mappedFields),
                    'semester' => in_array('semester', $mappedFields),
                    'credits' => in_array('credits', $mappedFields),
                ]
            ]);

            $import->updateStatus('validating');

            return response()->json([
                'success' => true,
                'message' => 'Mapeo actualizado correctamente',
                'next_step' => 'validate',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar mapeo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paso 4: Validar datos y detectar campos faltantes
     */
    public function validateData(Request $request, CurriculumImport $import)
    {
        if ($import->status !== 'validating') {
            return response()->json([
                'success' => false,
                'message' => 'El estado actual no permite validación'
            ], 400);
        }

        try {
            // Obtener ruta completa del archivo usando Storage
            $filePath = Storage::disk('local')->path($import->stored_path);

            // Cargar Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            $columnMapping = $import->column_mapping;
            $validationErrors = [];
            $missingDataRows = [];
            $validRowsCount = 0;

            // Validar cada fila de datos
            for ($row = $import->data_start_row; $row <= $highestRow; $row++) {
                $rowData = [];
                foreach ($columnMapping as $excelCol => $fieldName) {
                    $cellValue = $worksheet->getCell($excelCol . $row)->getValue();
                    $rowData[$fieldName] = $cellValue;
                }

                // Validar fila
                $validation = $this->analyzer->validateRow($rowData, $columnMapping);

                if (!$validation['valid']) {
                    $missingDataRows[] = [
                        'row_number' => $row,
                        'data' => $rowData,
                        'errors' => $validation['errors'],
                        'missing_fields' => $validation['missing_fields'],
                    ];
                    $validationErrors = array_merge($validationErrors, $validation['errors']);
                } else {
                    $validRowsCount++;
                }
            }

            // Guardar resultados de validación
            $import->update([
                'validation_errors' => $validationErrors,
                'missing_data_rows' => $missingDataRows,
            ]);

            // Determinar siguiente paso
            $hasErrors = count($missingDataRows) > 0;
            $nextStep = $hasErrors ? 'fill' : 'confirm';
            $nextStatus = $hasErrors ? 'filling' : 'confirmed';

            $import->updateStatus($nextStatus);

            return response()->json([
                'success' => true,
                'message' => 'Validación completada',
                'valid_rows' => $validRowsCount,
                'invalid_rows' => count($missingDataRows),
                'total_rows' => $highestRow - $import->data_start_row + 1,
                'next_step' => $nextStep,
                'has_errors' => $hasErrors,
                'missing_data_rows' => $missingDataRows,
                'validation_summary' => [
                    'total_errors' => count($validationErrors),
                    'rows_with_errors' => count($missingDataRows),
                ]
            ]);
        } catch (\Exception $e) {
            $import->updateStatus('failed');
            $import->update(['error_message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al validar datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paso 5: Actualizar datos faltantes proporcionados por el usuario
     */
    public function updateMissingData(Request $request, CurriculumImport $import)
    {
        $request->validate([
            'completed_rows' => 'required|array',
        ]);

        try {
            $completedRows = $request->completed_rows;
            $missingDataRows = $import->missing_data_rows;

            // Actualizar filas con datos completados
            foreach ($completedRows as $rowNumber => $rowData) {
                $key = array_search($rowNumber, array_column($missingDataRows, 'row_number'));
                
                if ($key !== false) {
                    // Actualizar datos de la fila
                    $missingDataRows[$key]['data'] = array_merge(
                        $missingDataRows[$key]['data'],
                        $rowData
                    );

                    // Re-validar fila
                    $validation = $this->analyzer->validateRow(
                        $missingDataRows[$key]['data'],
                        $import->column_mapping
                    );

                    if ($validation['valid']) {
                        // Marcar como completada removiéndola del array
                        unset($missingDataRows[$key]);
                    } else {
                        // Actualizar errores
                        $missingDataRows[$key]['errors'] = $validation['errors'];
                        $missingDataRows[$key]['missing_fields'] = $validation['missing_fields'];
                    }
                }
            }

            // Re-indexar array
            $missingDataRows = array_values($missingDataRows);

            // Actualizar import
            $import->update(['missing_data_rows' => $missingDataRows]);

            // Si ya no hay errores, cambiar a confirmado
            if (count($missingDataRows) === 0) {
                $import->updateStatus('confirmed');
            }

            return response()->json([
                'success' => true,
                'message' => 'Datos actualizados correctamente',
                'remaining_errors' => count($missingDataRows),
                'next_step' => count($missingDataRows) === 0 ? 'confirm' : 'fill',
                'missing_data_rows' => $missingDataRows,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar datos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paso 6: Confirmar e importar datos a la base de datos
     */
    public function confirm(Request $request, CurriculumImport $import)
    {
        $request->validate([
            'curriculum_name' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'save_as_template' => 'nullable|boolean',
            'template_name' => 'required_if:save_as_template,true|nullable|string|max:255',
        ]);

        if ($import->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'El import debe estar en estado confirmado'
            ], 400);
        }

        try {
            $import->updateStatus('importing');

            DB::beginTransaction();

            // Crear ExternalCurriculum
            $curriculum = ExternalCurriculum::create([
                'name' => $request->curriculum_name,
                'institution' => $request->institution,
                'year' => $request->year,
            ]);

            // Obtener ruta completa del archivo usando Storage
            $filePath = Storage::disk('local')->path($import->stored_path);

            // Cargar Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            $columnMapping = $import->column_mapping;
            $subjectsImported = 0;
            $importedSubjects = [];

            // Importar materias
            for ($row = $import->data_start_row; $row <= $highestRow; $row++) {
                $rowData = [];
                foreach ($columnMapping as $excelCol => $fieldName) {
                    $cellValue = $worksheet->getCell($excelCol . $row)->getValue();
                    $rowData[$fieldName] = $cellValue;
                }

                // Validar que tenga datos mínimos
                if (empty($rowData['code']) || empty($rowData['name'])) {
                    continue;
                }

                // Crear ExternalSubject
                $subject = ExternalSubject::create([
                    'external_curriculum_id' => $curriculum->id,
                    'code' => strtoupper(trim($rowData['code'])),
                    'name' => trim($rowData['name']),
                    'semester' => $rowData['semester'] ?? null,
                    'credits' => $rowData['credits'] ?? null,
                    'classroom_hours' => $rowData['classroom_hours'] ?? null,
                    'student_hours' => $rowData['student_hours'] ?? null,
                    'type' => $rowData['type'] ?? null,
                    'is_required' => $rowData['is_required'] ?? true,
                ]);

                $subjectsImported++;
                $importedSubjects[] = [
                    'code' => $subject->code,
                    'name' => $subject->name,
                ];
            }

            // Guardar template si se solicitó
            if ($request->save_as_template && $request->template_name) {
                $import->update(['template_name' => $request->template_name]);
            }

            // Actualizar import
            $import->update([
                'subjects_imported' => $subjectsImported,
                'import_summary' => [
                    'curriculum_id' => $curriculum->id,
                    'curriculum_name' => $curriculum->name,
                    'subjects' => $importedSubjects,
                    'total_imported' => $subjectsImported,
                ]
            ]);

            $import->updateStatus('completed');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Importación completada exitosamente',
                'curriculum_id' => $curriculum->id,
                'subjects_imported' => $subjectsImported,
                'summary' => $import->import_summary,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $import->updateStatus('failed');
            $import->update(['error_message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al importar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estado actual de la importación
     */
    public function status(CurriculumImport $import)
    {
        return response()->json([
            'success' => true,
            'import' => [
                'id' => $import->id,
                'status' => $import->status,
                'original_filename' => $import->original_filename,
                'total_rows' => $import->total_rows,
                'subjects_imported' => $import->subjects_imported,
                'error_message' => $import->error_message,
                'column_mapping' => $import->column_mapping,
                'required_fields_status' => $import->required_fields_status,
                'preview_data' => $import->preview_data,
                'validation_errors' => $import->validation_errors,
                'missing_data_rows' => $import->missing_data_rows,
                'import_summary' => $import->import_summary,
            ]
        ]);
    }

    /**
     * Listar templates guardados
     */
    public function templates()
    {
        $templates = CurriculumImport::whereNotNull('template_name')
            ->where('status', 'completed')
            ->select('id', 'template_name', 'column_mapping', 'original_filename', 'created_at')
            ->get();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Aplicar template guardado a importación actual
     */
    public function applyTemplate(Request $request, CurriculumImport $import)
    {
        $request->validate([
            'template_id' => 'required|exists:curriculum_imports,id',
        ]);

        try {
            $template = CurriculumImport::findOrFail($request->template_id);

            if (empty($template->column_mapping)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El template no tiene mapeo de columnas'
                ], 400);
            }

            // Aplicar mapeo del template
            $import->update([
                'column_mapping' => $template->column_mapping,
                'required_fields_status' => $template->required_fields_status,
            ]);

            $import->updateStatus('validating');

            return response()->json([
                'success' => true,
                'message' => 'Template aplicado correctamente',
                'column_mapping' => $template->column_mapping,
                'next_step' => 'validate',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar template: ' . $e->getMessage()
            ], 500);
        }
    }
}

