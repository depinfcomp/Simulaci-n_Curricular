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

        // Calculate accurate statistics from imports
        $stats = [
            'total_imports' => AcademicHistoryImport::count(),
            
            // Sum of all records imported across all imports
            'total_records' => AcademicHistoryImport::sum('total_records'),
            
            // Count unique students from students table (most accurate)
            'unique_students' => DB::table('students')->count(),
            
            // Average success rate from completed imports
            'avg_success_rate' => AcademicHistoryImport::where('status', 'completed')
                ->where('total_records', '>', 0)
                ->avg(DB::raw('(successful_imports * 100.0 / total_records)')) ?? 0
        ];

        // Check if there is academic data to show "Clear All" button
        $hasAcademicData = DB::table('students')->count() > 0 
                        || DB::table('student_subject')->count() > 0 
                        || DB::table('academic_histories')->count() > 0;

        return view('academic-history.index', compact('imports', 'stats', 'hasAcademicData'));
    }

    /**
     * Upload and analyze Excel file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv|max:51200', // Solo CSV, 50MB max
            'current_period' => 'nullable|string' // Opcional - se detecta automáticamente si no se proporciona
        ]);

        try {
            $file = $request->file('file');
            $currentPeriod = $request->input('current_period'); // null si no se proporciona
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

            // Use the same service as docker.sh setup - PASS current_period
            $result = $this->importService->importFromCSV($fullPath, false, $currentPeriod);

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
     * Delete an import and ALL its associated data (students, subjects, histories)
     */
    public function destroy(AcademicHistoryImport $import)
    {
        try {
            DB::beginTransaction();
            
            Log::info('Starting import deletion with all associated data', [
                'import_id' => $import->id,
                'filename' => $import->original_filename
            ]);
            
            // Get date range for this import to identify students created during this import
            $importDate = $import->created_at;
            $importEndDate = $import->updated_at;
            
            // Get students imported in this batch
            $studentDocuments = DB::table('students')
                ->whereBetween('created_at', [$importDate, $importEndDate])
                ->pluck('document')
                ->toArray();
            
            Log::info('Found students to delete', [
                'count' => count($studentDocuments),
                'import_id' => $import->id
            ]);
            
            // Delete student_subject records for these students
            $deletedStudentSubjects = 0;
            if (!empty($studentDocuments)) {
                $deletedStudentSubjects = DB::table('student_subject')
                    ->whereIn('student_document', $studentDocuments)
                    ->delete();
            }
            
            // Delete academic_histories linked to this import
            $deletedHistories = DB::table('academic_histories')
                ->where('import_id', $import->id)
                ->delete();
            
            // Delete students
            $deletedStudents = 0;
            if (!empty($studentDocuments)) {
                $deletedStudents = DB::table('students')
                    ->whereIn('document', $studentDocuments)
                    ->delete();
            }
            
            // Delete associated file
            Storage::delete('academic-history-imports/' . $import->filename);
            
            // Delete the import record
            $import->delete();
            
            DB::commit();

            Log::info('Import and associated data deleted successfully', [
                'import_id' => $import->id,
                'deleted_students' => $deletedStudents,
                'deleted_student_subjects' => $deletedStudentSubjects,
                'deleted_histories' => $deletedHistories,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Importación y datos asociados eliminados correctamente',
                'deleted' => [
                    'students' => $deletedStudents,
                    'student_subjects' => $deletedStudentSubjects,
                    'histories' => $deletedHistories
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting import and data', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export import data to CSV (original format from database)
     */
    public function export(AcademicHistoryImport $import)
    {
        try {
            Log::info("Exporting original CSV for import ID: {$import->id}");
            
            // Get date range for this import
            $importDate = $import->created_at;
            $importEndDate = $import->updated_at;
            
            Log::info("Import date range: {$importDate->toDateTimeString()} to {$importEndDate->toDateTimeString()}");
            
            // Get students imported in this batch
            $students = DB::table('students')
                ->whereBetween('updated_at', [$importDate, $importEndDate])
                ->pluck('document');
            
            Log::info("Found " . $students->count() . " students");
            
            if ($students->isEmpty()) {
                return redirect()->back()->with('info', 'No hay datos para exportar');
            }
            
            // Get all subject records for these students created during import
            $records = DB::table('student_subject')
                ->whereIn('student_document', $students)
                ->whereBetween('created_at', [$importDate, $importEndDate])
                ->select(
                    'student_document',
                    'subject_code',
                    'subject_name',
                    'grade',
                    'subject_credits',
                    'period',
                    'status'
                )
                ->get();
            
            Log::info("Found " . $records->count() . " records");
            
            if ($records->isEmpty()) {
                return redirect()->back()->with('info', 'No hay registros para exportar');
            }
            
            // Create CSV content
            $csv = "Código Estudiante,Código Materia,Nombre Materia,Nota,Créditos,Período,Estado\n";
            
            foreach ($records as $record) {
                $csv .= implode(',', [
                    $record->student_document,
                    $record->subject_code,
                    '"' . str_replace('"', '""', $record->subject_name) . '"',
                    $record->grade ?? '',
                    $record->subject_credits ?? '',
                    $record->period ?? '',
                    $record->status ?? ''
                ]) . "\n";
            }
            
            $filename = 'export_' . pathinfo($import->original_filename, PATHINFO_FILENAME) . '_' . now()->format('Y-m-d_His') . '.csv';
            
            Log::info("Exporting CSV: {$filename}");
            
            return response($csv)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('Error exporting CSV: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    /**
     * Export successful import records with credit distribution details
     */
    public function exportSuccessful(AcademicHistoryImport $import)
    {
        try {
            Log::info("Exporting successful records for import ID: {$import->id}");
            
            // Get date range for this import
            $importDate = $import->created_at;
            $importEndDate = $import->updated_at;
            
            Log::info("Import date range: {$importDate->toDateTimeString()} to {$importEndDate->toDateTimeString()}");
            
            // Get students imported in this batch
            $students = DB::table('students')
                ->whereBetween('updated_at', [$importDate, $importEndDate])
                ->get();
            
            Log::info("Found " . $students->count() . " students in date range");
            
            if ($students->isEmpty()) {
                Log::warning("No students found for this import");
                return redirect()->back()->with('info', 'No hay registros exitosos en esta importación');
            }
            
            // Create a map of student data
            $studentMap = [];
            foreach ($students as $student) {
                $studentMap[$student->document] = [
                    'name' => $student->name,
                    'average' => $student->average ?? 0,
                    'progress_percentage' => $student->progress_percentage ?? 0,
                    'approved_credits' => $student->approved_credits ?? 0,
                    'enrolled_credits' => $student->enrolled_credits ?? 0
                ];
            }
            
            // Get all their subject records with ALL new fields
            $records = DB::table('student_subject')
                ->whereIn('student_document', array_keys($studentMap))
                ->whereBetween('created_at', [$importDate, $importEndDate])
                ->select(
                    'student_document',
                    'subject_code',
                    'subject_name',
                    'subject_credits',
                    'subject_type',
                    'grade',
                    'alphabetic_grade',
                    'status',
                    'period',
                    'effective_credits',
                    'overflow_credits',
                    'actual_component_type',
                    'is_duplicate',
                    'counts_for_percentage',
                    'assignment_notes',
                    'created_at'
                )
                ->get();
            
            Log::info("Found " . $records->count() . " successful records");
            
            if ($records->isEmpty()) {
                Log::warning("No records found for export");
                return redirect()->back()->with('info', 'No hay registros exitosos en esta importación');
            }
            
            // Map records to export format
            $successfulRecords = $records->map(function($record) use ($studentMap) {
                $studentData = $studentMap[$record->student_document] ?? [
                    'name' => 'Desconocido',
                    'average' => 0,
                    'progress_percentage' => 0,
                    'approved_credits' => 0,
                    'enrolled_credits' => 0
                ];
                
                return [
                    'documento' => $record->student_document,
                    'nombre_estudiante' => $studentData['name'],
                    'promedio' => $studentData['average'],
                    'progreso_porcentaje' => $studentData['progress_percentage'],
                    'creditos_aprobados' => $studentData['approved_credits'],
                    'creditos_cursados' => $studentData['enrolled_credits'],
                    'cod_asignatura' => $record->subject_code,
                    'asignatura' => $record->subject_name,
                    'creditos_asignatura' => $record->subject_credits,
                    'tipo_materia' => $record->subject_type,
                    'nota' => $record->grade,
                    'nota_alfabetica' => $record->alphabetic_grade,
                    'status' => $record->status,
                    'periodo' => $record->period,
                    'effective_credits' => $record->effective_credits ?? $record->subject_credits,
                    'overflow_credits' => $record->overflow_credits ?? 0,
                    'actual_component_type' => $record->actual_component_type ?? $record->subject_type,
                    'is_duplicate' => $record->is_duplicate ?? false,
                    'counts_for_percentage' => $record->counts_for_percentage ?? true,
                    'assignment_notes' => $record->assignment_notes ?? '',
                    'fecha_importacion' => $record->created_at,
                    'is_current' => $record->status === 'enrolled'
                ];
            })->toArray();
            
            // Use Excel export class
            $filename = 'exitosos_' . pathinfo($import->original_filename, PATHINFO_FILENAME) . '_' . now()->format('Y-m-d_His') . '.xlsx';
            
            Log::info("Starting Excel export: {$filename}");
            
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
     * Clear ONLY academic data, preserving import history
     * This will delete all: student_subject, students, academic_histories
     * Import records (academic_history_imports) are preserved but counters are reset
     */
    public function clearAll(Request $request)
    {
        try {
            DB::beginTransaction();

            // Get counts before deletion for logging
            $studentSubjectCount = DB::table('student_subject')->count();
            $studentsCount = DB::table('students')->count();
            $academicHistoriesCount = DB::table('academic_histories')->count();

            Log::info('Starting clearAll - counts before deletion', [
                'student_subject' => $studentSubjectCount,
                'students' => $studentsCount,
                'academic_histories' => $academicHistoriesCount,
            ]);

            // Delete in correct order (respecting foreign keys)
            // 1. First delete student_subject (no FK dependencies on it)
            $deletedSubjects = DB::table('student_subject')->delete();
            Log::info('Deleted student_subject records', ['count' => $deletedSubjects]);
            
            // 2. Delete academic_histories (no FK dependencies on it)
            $deletedHistories = DB::table('academic_histories')->delete();
            Log::info('Deleted academic_histories records', ['count' => $deletedHistories]);
            
            // 3. Finally delete students (other tables may reference it)
            $deletedStudents = DB::table('students')->delete();
            Log::info('Deleted students records', ['count' => $deletedStudents]);
            
            // 4. Reset counters in academic_history_imports (keep records but zero out stats)
            $updatedImports = DB::table('academic_history_imports')->update([
                'total_records' => 0,
                'successful_imports' => 0,
                'failed_imports' => 0,
            ]);
            Log::info('Reset import counters', ['imports_updated' => $updatedImports]);

            DB::commit();

            Log::info('Academic data cleared successfully (import records preserved with reset counters)', [
                'user_id' => auth()->id(),
                'student_subject_deleted' => $deletedSubjects,
                'students_deleted' => $deletedStudents,
                'academic_histories_deleted' => $deletedHistories,
                'imports_reset' => $updatedImports,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Datos académicos eliminados correctamente. Contadores de importación reseteados.',
                'deleted' => [
                    'student_subject' => $deletedSubjects,
                    'students' => $deletedStudents,
                    'academic_histories' => $deletedHistories,
                    'imports_reset' => $updatedImports,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error clearing academic data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar los datos académicos: ' . $e->getMessage()
            ], 500);
        }
    }
}

