<?php

namespace App\Http\Controllers;

use App\Models\AcademicHistory;
use App\Models\AcademicHistoryImport;
use App\Services\AcademicHistoryImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AcademicHistoryController extends Controller
{
    protected $importService;
    
    public function __construct(AcademicHistoryImportService $importService)
    {
        $this->importService = $importService;
    }
    
    /**
     * Display a listing of imports
     */
    public function index()
    {
        $imports = AcademicHistoryImport::with('importedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total_imports' => AcademicHistoryImport::count(),
            'total_records' => DB::table('student_subject')->count(), // Changed to count from student_subject
            'unique_students' => DB::table('student_subject')->distinct('student_document')->count('student_document'),
            'avg_success_rate' => AcademicHistoryImport::where('status', 'completed')
                ->avg(DB::raw('(successful_imports / NULLIF(total_records, 0)) * 100')) ?? 0
        ];

        return view('academic-history.index', compact('imports', 'stats'));
    }

    /**
     * Upload and analyze Excel file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv|max:51200' // Solo CSV, 50MB max
        ]);

        try {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Store file
            $path = $file->storeAs('academic-history-imports', $filename, 'local');
            $fullPath = $this->resolveFilePath($path);
            
            if (!file_exists($fullPath)) {
                throw new \Exception("El archivo no se pudo localizar");
            }

            // Create import record
            $import = AcademicHistoryImport::create([
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'status' => 'processing',
                'imported_by' => auth()->id()
            ]);

            // Use the same service as docker.sh setup
            $result = $this->importService->importFromCSV($fullPath, false);

            // Calculate correct statistics
            $totalRecords = $result['history']['created'] + $result['current']['created'] + $result['subjects']['invalid'];
            $successfulImports = $result['history']['created'] + $result['current']['created'];
            $failedImports = $result['subjects']['invalid'];

            // Get successful and failed records from import service
            $successfulRecords = $this->importService->getSuccessfulRecords();
            $failedRecords = $this->importService->getFailedRecords();

            // Store records as array (model has cast to array) for later export
            $importSummary = [
                'successful_records' => $successfulRecords,
                'failed_records' => $failedRecords,
                'stats' => $result
            ];

            // Update import with results
            $import->update([
                'status' => 'completed',
                'total_records' => $totalRecords,
                'successful_imports' => $successfulImports,
                'failed_imports' => $failedImports,
                'import_summary' => $importSummary  // No need to json_encode, model will cast it
            ]);

            return response()->json([
                'success' => true,
                'import_id' => $import->id,
                'stats' => [
                    'students' => $result['students']['created'],
                    'historical' => $result['history']['created'],
                    'current' => $result['current']['created'],
                    'total' => $result['students']['total']
                ],
                'message' => 'Historia académica importada correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error uploading academic history file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($import)) {
                $import->update(['status' => 'failed']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al importar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Resolve file path with Docker compatibility
     */
    private function resolveFilePath($path)
    {
        $attempts = [
            storage_path('app/' . $path),
            Storage::disk('local')->path($path),
            base_path('storage/app/' . $path),
            '/app/storage/app/' . $path
        ];
        
        foreach ($attempts as $fullPath) {
            if (file_exists($fullPath)) {
                \Log::info("File found at: {$fullPath}");
                return $fullPath;
            }
        }
        
        throw new \Exception("No se pudo encontrar el archivo en ninguna ubicación");
    }    /**
     * Analyze file structure (supports both Excel and CSV)
     */
    private function analyzeFile($filePath, $extension = 'csv')
    {
        try {
            \Log::info('Analyzing file', ['path' => $filePath, 'extension' => $extension, 'exists' => file_exists($filePath)]);
            
            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado en: {$filePath}");
            }
            
            if (!is_readable($filePath)) {
                throw new \Exception("Archivo no es legible: {$filePath}");
            }
            
            // Handle CSV files
            if (strtolower($extension) === 'csv') {
                return $this->analyzeCSVFile($filePath);
            }
            
            // Handle Excel files
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Get headers from first row
            $headers = [];
            $highestColumn = $sheet->getHighestColumn();
            
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator('A', $highestColumn) as $cell) {
                    $value = $cell->getValue();
                    $headers[] = $value !== null ? (string)$value : '';
                }
            }

            // Get preview data (first 10 rows)
            $previewData = [];
            $rowCount = 2; // Start from row 2 (after header)
            foreach ($sheet->getRowIterator(2, min(11, $sheet->getHighestRow())) as $row) {
                $rowData = [];
                foreach ($row->getCellIterator('A', $highestColumn) as $cell) {
                    $value = $cell->getValue();
                    $rowData[] = $value !== null ? (string)$value : '';
                }
                $previewData[] = $rowData;
                $rowCount++;
            }

            // Total rows
            $totalRows = $sheet->getHighestRow() - 1; // Exclude header

            return [
                'columns' => $headers,
                'headers' => $headers, // Keep for BC
                'preview_data' => $previewData,
                'total_rows' => $totalRows,
                'file_type' => 'excel'
            ];
        } catch (\Exception $e) {
            \Log::error('Error analyzing file', [
                'path' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Analyze CSV file structure
     */
    private function analyzeCSVFile($filePath)
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Cannot open CSV file: {$filePath}");
        }
        
        // Read header (skip empty lines)
        $headers = null;
        while (($row = fgetcsv($handle)) !== false) {
            if (!$this->isEmptyRow($row)) {
                $headers = $row;
                break;
            }
        }
        
        if ($headers === null) {
            fclose($handle);
            throw new \Exception("No valid header found in CSV file");
        }
        
        // Get preview data (first 10 rows)
        $previewData = [];
        $totalRows = 0;
        
        while (($row = fgetcsv($handle)) !== false && count($previewData) < 10) {
            if (!$this->isEmptyRow($row)) {
                $previewData[] = $row;
                $totalRows++;
            }
        }
        
        // Count remaining rows
        while (($row = fgetcsv($handle)) !== false) {
            if (!$this->isEmptyRow($row)) {
                $totalRows++;
            }
        }
        
        fclose($handle);
        
        return [
            'columns' => $headers,
            'headers' => $headers, // Keep for BC
            'preview_data' => $previewData,
            'total_rows' => $totalRows,
            'file_type' => 'csv'
        ];
    }
    
    /**
     * Check if CSV row is empty
     */
    private function isEmptyRow($row)
    {
        if (empty($row)) {
            return true;
        }
        
        foreach ($row as $value) {
            if (!empty(trim($value))) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Suggest column mapping based on header names (compatible with docker.sh setup format)
     */
    private function suggestColumnMapping($headers)
    {
        $mapping = [];
        $patterns = [
            // Priorizar formato del docker.sh setup (CSV original)
            'student_code' => [
                'documento',  // Formato original del CSV
                'codigo.*estudiante', 
                'cod.*estudiante', 
                'id.*estudiante', 
                'estudiante.*codigo',
                'student.*code',
                'student.*id'
            ],
            'subject_code' => [
                'cod_asignatura',  // Formato original del CSV
                'codigo.*asignatura',
                'codigo.*materia', 
                'cod.*materia', 
                'asignatura.*codigo',
                'subject.*code',
                'course.*code'
            ],
            'subject_name' => [
                'nombre.*materia', 
                'materia', 
                'asignatura', 
                'nombre.*asignatura',
                'subject.*name',
                'course.*name'
            ],
            'grade' => [
                'nota_numerica',  // Formato original del CSV
                'nota', 
                'calificacion', 
                'calif', 
                'grade',
                'score'
            ],
            'credits' => [
                'creditos', 
                'cred', 
                'credits',
                'num.*creditos'
            ],
            'period' => [
                'periodo_inscripcion',  // Formato original del CSV
                'periodo', 
                'semestre', 
                'period', 
                'term',
                'academic.*period'
            ]
        ];

        foreach ($headers as $index => $header) {
            $headerLower = mb_strtolower(trim($header));
            
            // Exact match first (for CSV format)
            foreach ($patterns as $field => $patternArray) {
                foreach ($patternArray as $pattern) {
                    // Try exact match first
                    if ($headerLower === strtolower($pattern)) {
                        $mapping[$field] = $index;
                        break 2;
                    }
                    // Then try regex match
                    if (preg_match('/' . $pattern . '/i', $headerLower)) {
                        if (!isset($mapping[$field])) { // Only set if not already set by exact match
                            $mapping[$field] = $index;
                            break 2;
                        }
                    }
                }
            }
        }

        \Log::info('Column mapping suggested', ['mapping' => $mapping, 'headers' => $headers]);
        
        return $mapping;
    }

    /**
     * Process import automatically after upload
     */
    private function processImport($import, $filePath)
    {
        try {
            // Update status
            $import->update(['status' => 'processing']);
            
            $mapping = $import->column_mapping ?? [];
            
            if (empty($mapping['student_code']) || 
                empty($mapping['subject_code']) || empty($mapping['subject_name'])) {
                throw new \Exception('Mapeo de columnas incompleto');
            }
            
            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            $successCount = 0;
            $failCount = 0;
            $errors = [];
            $totalRows = 0;
            
            // Process rows starting from row 2 (skip header)
            foreach ($sheet->getRowIterator(2) as $row) {
                $totalRows++;
                try {
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    $index = 0;
                    foreach ($cellIterator as $cell) {
                        $rowData[$index] = $cell->getValue();
                        $index++;
                    }
                    
                    // Extract data using mapping
                    $studentCode = $rowData[$mapping['student_code']] ?? null;
                    $subjectCode = $rowData[$mapping['subject_code']] ?? null;
                    $subjectName = $rowData[$mapping['subject_name']] ?? null;
                    
                    // Skip empty rows
                    if (empty($studentCode) || empty($subjectCode)) {
                        continue;
                    }
                    
                    $data = [
                        'import_id' => $import->id,
                        'student_code' => $studentCode,
                        'subject_code' => $subjectCode,
                        'subject_name' => $subjectName,
                        'grade' => isset($mapping['grade']) ? ($rowData[$mapping['grade']] ?? null) : null,
                        'credits' => isset($mapping['credits']) ? ($rowData[$mapping['credits']] ?? 0) : 0,
                        'period' => isset($mapping['period']) ? ($rowData[$mapping['period']] ?? null) : null,
                    ];
                    
                    // Convert grade to numeric if present
                    if ($data['grade']) {
                        $data['numeric_grade'] = floatval(str_replace(',', '.', $data['grade']));
                        $data['status'] = $data['numeric_grade'] >= 3.0 ? 'approved' : 'failed';
                    }
                    
                    AcademicHistory::create($data);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $failCount++;
                    if (count($errors) < 100) { // Limit error messages
                        $errors[] = "Fila {$row->getRowIndex()}: " . $e->getMessage();
                    }
                }
            }
            
            // Update import statistics
            $import->update([
                'status' => 'completed',
                'total_records' => $totalRows,
                'successful_imports' => $successCount,
                'failed_imports' => $failCount,
                'error_summary' => !empty($errors) ? $errors : null
            ]);
            
        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_summary' => [$e->getMessage()]
            ]);
            throw $e;
        }
    }

    /**
     * Show preview and column mapping interface
     */
    public function preview(AcademicHistoryImport $import)
    {
        try {
            // Get file path
            $filePath = storage_path('app/academic-history-imports/' . $import->filename);
            
            // Re-analyze if needed
            if (!file_exists($filePath)) {
                throw new \Exception('El archivo ya no existe en el servidor');
            }
            
            $analysis = $this->analyzeFile($filePath);
            
            return view('academic-history.preview', [
                'import' => $import,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            return redirect()->route('academic-history.index')
                ->with('error', 'Error al cargar vista previa: ' . $e->getMessage());
        }
    }

    /**
     * Update column mapping
     */
    public function updateMapping(Request $request, AcademicHistoryImport $import)
    {
        $request->validate([
            'mapping' => 'required|array',
            'mapping.student_code' => 'required|integer',
            'mapping.subject_code' => 'required|integer',
            'mapping.subject_name' => 'required|integer',
        ]);

        try {
            $import->update([
                'column_mapping' => $request->mapping
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mapeo de columnas guardado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el mapeo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process the import and insert data
     */
    public function process(Request $request, AcademicHistoryImport $import)
    {
        try {
            // Update status
            $import->update(['status' => 'processing']);
            
            // Get file path and mapping
            $filePath = storage_path('app/academic-history-imports/' . $import->filename);
            $mapping = $import->column_mapping ?? [];
            
            if (empty($mapping)) {
                throw new \Exception('Debe configurar el mapeo de columnas antes de procesar');
            }
            
            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            $successCount = 0;
            $failCount = 0;
            $errors = [];
            
            // Process rows
            foreach ($sheet->getRowIterator(2) as $row) {
                try {
                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    $index = 0;
                    foreach ($cellIterator as $cell) {
                        $rowData[$index] = $cell->getValue();
                        $index++;
                    }
                    
                    // Extract data using mapping
                    $data = [
                        'import_id' => $import->id,
                        'student_code' => $rowData[$mapping['student_code']] ?? null,
                        'student_name' => $rowData[$mapping['student_name']] ?? null,
                        'subject_code' => $rowData[$mapping['subject_code']] ?? null,
                        'subject_name' => $rowData[$mapping['subject_name']] ?? null,
                        'grade' => $rowData[$mapping['grade'] ?? -1] ?? null,
                        'credits' => isset($mapping['credits']) ? ($rowData[$mapping['credits']] ?? 0) : 0,
                        'period' => isset($mapping['period']) ? ($rowData[$mapping['period']] ?? null) : null,
                    ];
                    
                    // Skip empty rows
                    if (empty($data['student_code']) || empty($data['subject_code'])) {
                        continue;
                    }
                    
                    // Convert grade to numeric
                    if ($data['grade']) {
                        $data['numeric_grade'] = floatval(str_replace(',', '.', $data['grade']));
                        $data['status'] = $data['numeric_grade'] >= 3.0 ? 'approved' : 'failed';
                    }
                    
                    AcademicHistory::create($data);
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "Fila {$row->getRowIndex()}: " . $e->getMessage();
                }
            }
            
            // Update import record
            $import->update([
                'status' => 'completed',
                'total_records' => $successCount + $failCount,
                'successful_imports' => $successCount,
                'failed_imports' => $failCount,
                'error_log' => implode("\n", array_slice($errors, 0, 100)) // Limit to 100 errors
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Procesamiento completado: {$successCount} exitosos, {$failCount} fallidos",
                'data' => [
                    'successful' => $successCount,
                    'failed' => $failCount
                ]
            ]);
            
        } catch (\Exception $e) {
            $import->update(['status' => 'failed']);
            
            \Log::error('Error processing academic history import', [
                'import_id' => $import->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la importación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show detailed view of an import
     */
    public function show(AcademicHistoryImport $import)
    {
        $import->load('histories', 'importedBy');
        
        return view('academic-history.show', compact('import'));
    }

    /**
     * Delete an import and its records
     */
    public function destroy(AcademicHistoryImport $import)
    {
        try {
            // Delete associated file
            Storage::delete('academic-history-imports/' . $import->filename);
            
            // Delete import and cascading histories
            $import->delete();

            return response()->json([
                'success' => true,
                'message' => 'Importación eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export import data to Excel
     */
    public function export(AcademicHistoryImport $import)
    {
        try {
            $histories = $import->histories()->get();
            
            // Create CSV content
            $csv = "Código Estudiante,Nombre Estudiante,Código Materia,Nombre Materia,Nota,Créditos,Período,Estado\n";
            
            foreach ($histories as $history) {
                $csv .= implode(',', [
                    $history->student_code,
                    '"' . $history->student_name . '"',
                    $history->subject_code,
                    '"' . $history->subject_name . '"',
                    $history->grade,
                    $history->credits,
                    $history->period,
                    $history->status
                ]) . "\n";
            }
            
            $filename = 'export_' . $import->original_filename . '.csv';
            
            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    /**
     * Export successful import records with credit distribution details
     */
    public function exportSuccessful(AcademicHistoryImport $import)
    {
        try {
            // Get import summary to get list of processed documents
            $summary = $import->import_summary;
            
            if (!$summary || !isset($summary['successful_records'])) {
                return redirect()->back()->with('error', 'No hay datos de registros exitosos para exportar');
            }

            $successfulRecords = $summary['successful_records'];
            
            if (empty($successfulRecords)) {
                return redirect()->back()->with('info', 'No hay registros exitosos en esta importación');
            }
            
            // Use Excel export class
            $filename = 'exitosos_' . pathinfo($import->original_filename, PATHINFO_FILENAME) . '_' . now()->format('Y-m-d_His') . '.xlsx';
            
            return Excel::download(
                new \App\Exports\SuccessfulImportExport($successfulRecords, $import->created_at->format('Y-m-d H:i:s')),
                $filename
            );
                
        } catch (\Exception $e) {
            Log::error('Error exporting successful records: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Error al exportar registros exitosos: ' . $e->getMessage());
        }
    }

    /**
     * Export failed import records with error reasons
     */
    public function exportFailed(AcademicHistoryImport $import)
    {
        try {
            // Get import summary
            $summary = $import->import_summary;
            
            if (!$summary || !isset($summary['failed_records'])) {
                return redirect()->back()->with('error', 'No hay datos de registros fallidos para exportar');
            }

            $failedRecords = $summary['failed_records'];
            
            if (empty($failedRecords)) {
                return redirect()->back()->with('info', 'No hay registros fallidos en esta importación');
            }
            
            // Use Excel export class
            $filename = 'fallidos_' . pathinfo($import->original_filename, PATHINFO_FILENAME) . '_' . now()->format('Y-m-d_His') . '.xlsx';
            
            return Excel::download(
                new \App\Exports\FailedImportExport($failedRecords, $import->created_at->format('Y-m-d H:i:s')),
                $filename
            );
                
        } catch (\Exception $e) {
            Log::error('Error exporting failed records: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Error al exportar registros fallidos: ' . $e->getMessage());
        }
    }

    /**
     * Clear all academic history data
     * This will truncate: student_subject, students, academic_histories, academic_history_imports
     */
    public function clearAll(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get counts before deletion for logging
            $studentSubjectCount = DB::table('student_subject')->count();
            $studentsCount = DB::table('students')->count();
            $academicHistoriesCount = DB::table('academic_histories')->count();
            $importsCount = DB::table('academic_history_imports')->count();

            // Truncate tables in order (respecting foreign keys)
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
            
            // Delete data from tables with foreign keys first
            DB::table('student_subject')->truncate();
            DB::table('academic_histories')->truncate();
            DB::table('students')->truncate();
            DB::table('academic_history_imports')->truncate();

            DB::commit();

            Log::info('Academic history data cleared', [
                'user_id' => auth()->id(),
                'student_subject_deleted' => $studentSubjectCount,
                'students_deleted' => $studentsCount,
                'academic_histories_deleted' => $academicHistoriesCount,
                'imports_deleted' => $importsCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las historias académicas han sido eliminadas correctamente',
                'deleted' => [
                    'student_subject' => $studentSubjectCount,
                    'students' => $studentsCount,
                    'academic_histories' => $academicHistoriesCount,
                    'imports' => $importsCount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error clearing academic history data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar las historias académicas: ' . $e->getMessage()
            ], 500);
        }
    }
}

