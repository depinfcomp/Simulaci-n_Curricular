<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Subject;
use App\Models\ElectiveSubject;
use App\Models\StudentCurrentSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicHistoryImportService
{
    private $validSubjects = [];
    private $validElectiveSubjects = [];
    private $subjectCredits = []; // Cache for subject credits
    private $subjectTypes = []; // Cache for subject types (component classification)
    private $invalidSubjectCodes = [];
    private $studentsCache = [];
    private $studentsByDocument = []; // Group records by document
    private $creditLimits = []; // Credit limits per component from simulation_config
    private $randomNames = [
        'first_names' => [
            'Alejandro', 'Alejandra', 'Andrea', 'Andrés', 'Antonio', 'Carlos', 'Carmen', 'Carolina',
            'Cristian', 'Cristina', 'Daniel', 'Diana', 'Diego', 'Eduardo', 'Elena', 'Fernando',
            'Francisco', 'Gabriela', 'Gustavo', 'Isabel', 'Javier', 'Jessica', 'Jorge', 'José',
            'Juan', 'Juliana', 'Laura', 'Leonardo', 'Luis', 'Luisa', 'Manuel', 'Marcela',
            'Marco', 'María', 'Mario', 'Martha', 'Miguel', 'Natalia', 'Nicole', 'Oscar',
            'Pablo', 'Patricia', 'Paula', 'Pedro', 'Rafael', 'Ricardo', 'Roberto', 'Santiago',
            'Sara', 'Sebastián', 'Sofia', 'Valentina', 'Valeria', 'Víctor'
        ],
        'last_names' => [
            'García', 'González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Sánchez', 'Pérez',
            'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández', 'Díaz', 'Moreno', 'Muñoz',
            'Álvarez', 'Romero', 'Alonso', 'Gutiérrez', 'Navarro', 'Torres', 'Domínguez', 'Vázquez',
            'Ramos', 'Gil', 'Ramírez', 'Serrano', 'Blanco', 'Suárez', 'Molina', 'Morales',
            'Ortega', 'Delgado', 'Castro', 'Ortiz', 'Rubio', 'Marín', 'Sanz', 'Iglesias',
            'Medina', 'Garrido', 'Cortés', 'Castillo', 'Santos', 'Lozano', 'Guerrero', 'Cano'
        ]
    ];
    private $stats = [
        'students' => ['total' => 0, 'created' => 0, 'existing' => 0],
        'subjects' => ['total_records' => 0, 'valid' => 0, 'invalid' => 0, 'invalid_codes' => []],
        'history' => ['created' => 0],
        'current' => ['created' => 0],
        'duplicates' => 0,
        'processing_time' => 0,
        'records_per_second' => 0
    ];
    
    // Track successful and failed records for export
    private $successfulRecords = [];
    private $failedRecords = [];

    public function __construct()
    {
        // Don't load subjects in constructor to avoid database queries during boot/migration
        // loadValidSubjects() will be called on demand in importFromCSV()
    }

    /**
     * Load all valid subjects from database
     */
    private function loadValidSubjects()
    {
        // Load regular subjects
        $subjects = Subject::all();
        foreach ($subjects as $subject) {
            $this->validSubjects[$subject->code] = true;
            $this->subjectCredits[$subject->code] = $subject->credits;
            $this->subjectTypes[$subject->code] = $this->mapSubjectTypeToComponent($subject->type);
        }
        
        // Load elective subjects
        $electiveSubjects = ElectiveSubject::where('is_active', true)->get();
        foreach ($electiveSubjects as $elective) {
            $this->validElectiveSubjects[$elective->code] = true;
            $this->subjectCredits[$elective->code] = $elective->credits;
            $this->subjectTypes[$elective->code] = $this->mapElectiveTypeToComponent($elective->elective_type);
        }
        
        // Load subject aliases and map them to main subjects
        $aliases = DB::table('subject_aliases')->get();
        foreach ($aliases as $alias) {
            // Map alias to main subject
            if (isset($this->validSubjects[$alias->subject_code])) {
                $this->validSubjects[$alias->alias_code] = true;
                $this->subjectCredits[$alias->alias_code] = $this->subjectCredits[$alias->subject_code];
                $this->subjectTypes[$alias->alias_code] = $this->subjectTypes[$alias->subject_code];
                Log::info("Alias loaded: {$alias->alias_code} -> {$alias->subject_code}");
            } elseif (isset($this->validElectiveSubjects[$alias->subject_code])) {
                $this->validElectiveSubjects[$alias->alias_code] = true;
                $this->subjectCredits[$alias->alias_code] = $this->subjectCredits[$alias->subject_code];
                $this->subjectTypes[$alias->alias_code] = $this->subjectTypes[$alias->subject_code];
                Log::info("Elective alias loaded: {$alias->alias_code} -> {$alias->subject_code}");
            }
        }
        
        // Load credit limits from credit_limits_config table
        // Use global config (external_curriculum_id = null) or first available
        try {
            $config = DB::table('credit_limits_config')
                ->whereNull('external_curriculum_id')
                ->first();
                
            if (!$config) {
                // If no global config, use any config available
                $config = DB::table('credit_limits_config')->first();
            }
                
            if ($config) {
                $this->creditLimits = [
                    'fundamental_required' => $config->max_required_fundamental_credits ?? 60,
                    'professional_required' => $config->max_required_professional_credits ?? 80,
                    'optional_fundamental' => $config->max_optional_fundamental_credits ?? 6,
                    'optional_professional' => $config->max_optional_professional_credits ?? 9,
                    'free_elective' => $config->max_free_elective_credits ?? 36,
                    'thesis' => $config->max_thesis_credits ?? 6,
                    'practice' => 0, // Not in credit_limits_config table
                    'leveling' => $config->max_leveling_credits ?? 12,
                ];
                Log::info("Credit limits loaded from credit_limits_config");
            } else {
                throw new \Exception("No credit_limits_config found");
            }
        } catch (\Exception $e) {
            Log::warning("Could not load credit limits: " . $e->getMessage());
            
            // Default limits based on main curriculum
            $this->creditLimits = [
                'fundamental_required' => 60,
                'professional_required' => 80,
                'optional_fundamental' => 6,
                'optional_professional' => 9,
                'free_elective' => 36,
                'thesis' => 6,
                'practice' => 0,
                'leveling' => 12,
            ];
            Log::info("Using default credit limits");
        }
        
        Log::info("Loaded " . count($this->validSubjects) . " regular subjects and " . 
                  count($this->validElectiveSubjects) . " elective subjects");
        Log::info("Credit limits: " . json_encode($this->creditLimits));
    }

    /**
     * Map Subject type to component classification
     */
    private function mapSubjectTypeToComponent(string $type): string
    {
        $mapping = [
            'fundamental' => 'fundamental_required',
            'profesional' => 'professional_required',
            'optativa_fundamentacion' => 'optional_fundamental',
            'optativa_profesional' => 'optional_professional',
            'trabajo_grado' => 'thesis',
            'practica' => 'practice',
            'nivelacion' => 'leveling',
            'libre_eleccion' => 'free_elective',
        ];
        
        return $mapping[$type] ?? 'free_elective';
    }

    /**
     * Map ElectiveSubject type to component classification
     */
    private function mapElectiveTypeToComponent(string $electiveType): string
    {
        $mapping = [
            'optativa_fundamental' => 'optional_fundamental',
            'optativa_profesional' => 'optional_professional',
        ];
        
        return $mapping[$electiveType] ?? 'free_elective';
    }

    /**
     * Import academic history from CSV file
     */
    public function importFromCSV(string $filePath, bool $dryRun = false): array
    {
        $startTime = microtime(true);
        
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        // Load valid subjects on demand
        if (empty($this->validSubjects)) {
            $this->loadValidSubjects();
        }

        Log::info("Starting academic history import from: {$filePath}");
        Log::info("Mode: " . ($dryRun ? "DRY RUN" : "REAL IMPORT"));

        // Phase 1: Read and group all records by document
        $this->studentsByDocument = $this->readAndGroupRecords($filePath);
        
        $totalStudents = count($this->studentsByDocument);
        $totalRecords = array_sum(array_map('count', $this->studentsByDocument));
        
        Log::info("Phase 1 completed: Found {$totalStudents} unique students with {$totalRecords} total records");
        
        // Phase 2: Process each student and their subjects
        $processedStudents = 0;
        foreach ($this->studentsByDocument as $documento => $studentRecords) {
            if (!$dryRun) {
                $this->processStudent($documento, $studentRecords);
            } else {
                $this->simulateStudent($documento, $studentRecords);
            }
            
            $processedStudents++;
            
            // Progress indicator every 10 students
            if ($processedStudents % 10 === 0) {
                Log::info("Processed {$processedStudents}/{$totalStudents} students...");
            }
        }

        // Calculate performance stats
        $endTime = microtime(true);
        $this->stats['processing_time'] = $endTime - $startTime;
        $this->stats['records_per_second'] = $totalRecords / max($this->stats['processing_time'], 0.001);
        $this->stats['subjects']['invalid_codes'] = array_unique($this->invalidSubjectCodes);

        Log::info("Import completed in " . round($this->stats['processing_time'], 2) . " seconds");
        Log::info("Students: {$this->stats['students']['created']} created, {$this->stats['students']['existing']} existing");
        Log::info("Academic records: {$this->stats['history']['created']} historical, {$this->stats['current']['created']} current");

        return $this->stats;
    }

    /**
     * Read CSV file and group records by document
     */
    private function readAndGroupRecords(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Cannot open file: {$filePath}");
        }

        // Read and skip empty lines until we find the header
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if (!$this->isEmptyRow($row)) {
                $header = $row;
                break;
            }
        }

        if ($header === null) {
            throw new \Exception("No valid header found in CSV file");
        }

        // Validate header
        $this->validateHeader($header);
        
        // Get column indexes
        $columnIndexes = $this->getColumnIndexes($header);
        
        $groupedRecords = [];
        $processedRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $processedData = $this->processRow($row, $columnIndexes);
            if ($processedData) {
                $documento = $processedData['documento'];
                
                if (!isset($groupedRecords[$documento])) {
                    $groupedRecords[$documento] = [];
                }
                
                $groupedRecords[$documento][] = $processedData;
                $processedRows++;
                
                // Progress indicator every 1000 rows
                if ($processedRows % 1000 === 0) {
                    Log::info("Reading CSV: processed {$processedRows} rows...");
                }
            }
        }

        fclose($handle);
        Log::info("CSV read completed: {$processedRows} valid records from " . count($groupedRecords) . " unique students");
        
        return $groupedRecords;
    }

    /**
     * Process a single student and all their academic records
     */
    private function processStudent(string $documento, array $studentRecords): void
    {
        DB::transaction(function () use ($documento, $studentRecords) {
            // Get or create student with case-insensitive document lookup
            $student = $this->getOrCreateStudentWithRandomName($documento);
            
            // Initialize credit tracking per component for this student
            $creditUsed = [
                'fundamental_required' => 0,
                'professional_required' => 0,
                'optional_fundamental' => 0,
                'optional_professional' => 0,
                'free_elective' => 0,
                'thesis' => 0,
                'practice' => 0,
                'leveling' => 0,
            ];
            
            // First, calculate current credit usage from existing records
            $existingRecords = DB::table('student_subject')
                ->where('student_id', $student->id)
                ->where('status', 'passed')
                ->get();
            
            foreach ($existingRecords as $record) {
                $subjectCode = $record->subject_code;
                if (isset($this->subjectTypes[$subjectCode]) && isset($this->subjectCredits[$subjectCode])) {
                    $component = $this->subjectTypes[$subjectCode];
                    $credits = $this->subjectCredits[$subjectCode];
                    
                    if (isset($creditUsed[$component])) {
                        $creditUsed[$component] += $credits;
                    }
                }
            }
            
            Log::info("Student {$student->document} existing credits: " . json_encode($creditUsed));
            
            // Process all subjects for this student
            foreach ($studentRecords as $record) {
                if ($record['is_current']) {
                    // This is a current subject (no grade)
                    $this->createCurrentSubject($student, $record);
                } else {
                    // This is historical data (has grade)
                    $this->createHistoricalRecordWithCreditDistribution($student, $record, $creditUsed);
                }
            }
            
            // After all records processed, update student metrics
            $student->updateAcademicMetrics();
            Log::info("Updated academic metrics for student {$student->document}: " .
                      "Progress: {$student->progress_percentage}%, Avg: {$student->average_grade}");
        });
    }

    /**
     * Simulate processing a single student (for dry run)
     */
    private function simulateStudent(string $documento, array $studentRecords): void
    {
        // Check if student exists or would be created
        if (!isset($this->studentsCache[$documento])) {
            $existingStudent = Student::where('document', $documento)->first();
            if ($existingStudent) {
                $this->stats['students']['existing']++;
                $this->studentsCache[$documento] = $existingStudent;
            } else {
                $this->stats['students']['created']++;
                $this->stats['students']['total']++;
                $this->studentsCache[$documento] = true; // placeholder
            }
        }

        // Count what would be created for this student
        foreach ($studentRecords as $record) {
            if ($record['is_current']) {
                $this->stats['current']['created']++;
            } else {
                $this->stats['history']['created']++;
            }
        }
    }

    /**
     * Get or create student with random name by document number
     */
    private function getOrCreateStudentWithRandomName(string $documento): Student
    {
        if (isset($this->studentsCache[$documento])) {
            return $this->studentsCache[$documento];
        }

        // Try to find by document number (case-insensitive)
        $student = Student::whereRaw('LOWER(document) = ?', [strtolower($documento)])->first();

        if (!$student) {
            // Generate random name
            $randomName = $this->generateRandomName();
            
            // Create new student
            $student = Student::create([
                'name' => $randomName,
                'document' => $documento,
                'progress_percentage' => 0.00,
                'average_grade' => 0.00,
                'total_credits_taken' => 0,
                'approved_credits' => 0,
            ]);
            
            $this->stats['students']['created']++;
            Log::info("Created new student: {$randomName} (Document: {$documento})");
        } else {
            $this->stats['students']['existing']++;
            Log::info("Found existing student: {$student->name} (Document: {$documento})");
        }

        $this->stats['students']['total'] = max($this->stats['students']['total'], $this->stats['students']['created'] + $this->stats['students']['existing']);
        $this->studentsCache[$documento] = $student;

        return $student;
    }

    /**
     * Generate a random name combining first and last names
     */
    private function generateRandomName(): string
    {
        $firstName = $this->randomNames['first_names'][array_rand($this->randomNames['first_names'])];
        $lastName1 = $this->randomNames['last_names'][array_rand($this->randomNames['last_names'])];
        $lastName2 = $this->randomNames['last_names'][array_rand($this->randomNames['last_names'])];
        
        return "{$firstName} {$lastName1} {$lastName2}";
    }

    /**
     * Validate CSV header
     */
    private function validateHeader(array $header): void
    {
        $requiredColumns = [
            'DOCUMENTO', 'COD_ASIGNATURA', 'NOTA_NUMERICA', 'PERIODO_INSCRIPCION'
        ];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $header)) {
                throw new \Exception("Required column '{$column}' not found in CSV header");
            }
        }
    }

    /**
     * Get column indexes from header
     */
    private function getColumnIndexes(array $header): array
    {
        return [
            'documento' => array_search('DOCUMENTO', $header),
            'cod_asignatura' => array_search('COD_ASIGNATURA', $header),
            'asignatura' => array_search('ASIGNATURA', $header),
            'nota_numerica' => array_search('NOTA_NUMERICA', $header),
            'nota_alfabetica' => array_search('NOTA_ALFABETICA', $header),
            'periodo_inscripcion' => array_search('PERIODO_INSCRIPCION', $header),
            'creditos' => array_search('CREDITOS', $header),
            'tipo' => array_search('TIPO', $header)
        ];
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        return empty(array_filter($row, function($value) {
            return !empty(trim($value));
        }));
    }

    /**
     * Process a single CSV row
     */
    private function processRow(array $row, array $indexes): ?array
    {
        $this->stats['subjects']['total_records']++;

        $documento = trim($row[$indexes['documento']] ?? '');
        $codAsignatura = trim($row[$indexes['cod_asignatura']] ?? '');
        $notaNumerica = trim($row[$indexes['nota_numerica']] ?? '');
        $notaAlfabetica = trim($row[$indexes['nota_alfabetica']] ?? '');
        $periodoInscripcion = trim($row[$indexes['periodo_inscripcion']] ?? '');

        // Skip if essential data is missing
        if (empty($documento) || empty($codAsignatura) || empty($periodoInscripcion)) {
            return null;
        }

        // Clean subject code: remove suffix "-Z" if present
        $codAsignatura = preg_replace('/-Z$/i', '', $codAsignatura);
        
        // Store original code for tracking
        $originalCode = $codAsignatura;

        // Resolve alias: Check if this code is an alias and get the main subject code
        // The alias resolution is already handled in loadValidSubjects() which maps
        // alias codes to their main codes in validSubjects/validElectiveSubjects
        // So we just use the code as-is, it will work for both main codes and aliases
        
        // Get subject name and other data for tracking
        $asignaturaNombre = trim($row[$indexes['asignatura']] ?? '');
        $notaAlfabeticaRaw = trim($row[$indexes['nota_alfabetica']] ?? '');
        $creditos = trim($row[$indexes['creditos']] ?? '');

        // Check if subject exists in our system (regular subjects or electives)
        // This will return true for both main codes and alias codes (loaded in loadValidSubjects)
        $isValid = isset($this->validSubjects[$codAsignatura]) || isset($this->validElectiveSubjects[$codAsignatura]);
        
        // Log if this was an alias
        if ($isValid && $originalCode !== $codAsignatura) {
            Log::info("Alias resolved: {$originalCode} -> {$codAsignatura}");
        }
        
        if (!$isValid) {
            $this->invalidSubjectCodes[] = $codAsignatura;
            $this->stats['subjects']['invalid']++;
            
            // Track failed record with error reason
            $this->failedRecords[] = [
                'documento' => $documento,
                'cod_asignatura' => $codAsignatura,
                'asignatura' => $asignaturaNombre,
                'periodo' => $periodoInscripcion,
                'nota_numerica' => $notaNumerica,
                'nota_alfabetica' => $notaAlfabeticaRaw,
                'creditos' => $creditos,
                'error' => 'Código de asignatura no encontrado en la base de datos (ni en asignaturas regulares ni en electivas)'
            ];
            
            return null; // Skip this record
        }

        $this->stats['subjects']['valid']++;

        // Determine if this is historical (has grade) or current (no grade)
        // Handle both numeric grade and alphabetic grade (AP = passed, RE = failed)
        $hasGrade = false;
        $grade = null;
        
        if (!empty($notaNumerica) && is_numeric($notaNumerica)) {
            $hasGrade = true;
            $grade = (float) $notaNumerica;
        } elseif (!empty($notaAlfabetica)) {
            // If only alphabetic grade, infer numeric value
            if (strtoupper($notaAlfabetica) === 'AP') {
                $hasGrade = true;
                $grade = 3.0; // Minimum passing grade
            } elseif (strtoupper($notaAlfabetica) === 'RE') {
                $hasGrade = true;
                $grade = 0.0; // Failed
            }
        }

        // Determine status based on grade
        $status = 'enrolled'; // default
        if ($hasGrade) {
            $status = $grade >= 3.0 ? 'passed' : 'failed';
        }

        // Track successful record (will be enhanced with credit distribution later)
        $this->successfulRecords[] = [
            'documento' => $documento,
            'cod_asignatura' => $codAsignatura,
            'asignatura' => $asignaturaNombre,
            'nota' => $grade,
            'nota_alfabetica' => $notaAlfabeticaRaw,
            'periodo' => $periodoInscripcion,
            'status' => $status,
            'creditos_asignatura' => $creditos,
            'is_current' => !$hasGrade
        ];

        return [
            'documento' => $documento,
            'subject_code' => $codAsignatura,
            'grade' => $grade,
            'status' => $status,
            'periodo_inscripcion' => $periodoInscripcion,
            'is_current' => !$hasGrade,
            'raw_data' => [
                'asignatura' => $asignaturaNombre,
                'nota_alfabetica' => $notaAlfabeticaRaw,
                'creditos' => $creditos,
                'tipo' => trim($row[$indexes['tipo']] ?? '')
            ]
        ];
    }

    /**
     * Create current subject record
     */
    private function createCurrentSubject(Student $student, array $record): void
    {
        // Check for duplicates in current subjects
        $existing = StudentCurrentSubject::where([
            'student_id' => $student->id,
            'subject_code' => $record['subject_code'],
            'semester_period' => $this->convertPeriod($record['periodo_inscripcion'])
        ])->first();

        if ($existing) {
            $this->stats['duplicates']++;
            return;
        }

        // Also check if this subject already exists in historical records (student_subject)
        $historicalRecord = DB::table('student_subject')
            ->where('student_id', $student->id)
            ->where('subject_code', $record['subject_code'])
            ->where('status', 'passed') // Only check if already passed
            ->first();

        if ($historicalRecord) {
            // Student already passed this subject, don't add as current
            $this->stats['duplicates']++;
            return;
        }

        StudentCurrentSubject::create([
            'student_id' => $student->id,
            'subject_code' => $record['subject_code'],
            'semester_period' => $this->convertPeriod($record['periodo_inscripcion']),
            'status' => 'cursando',
            'partial_grade' => null,
        ]);

        $this->stats['current']['created']++;
    }

    /**
     * Create historical academic record with credit distribution logic
     */
    private function createHistoricalRecordWithCreditDistribution(Student $student, array $record, array &$creditUsed): void
    {
        // Check if record already exists
        $existing = DB::table('student_subject')
            ->where('student_id', $student->id)
            ->where('subject_code', $record['subject_code'])
            ->first();

        if ($existing) {
            // Update only if new grade is better (higher than existing)
            if ($record['grade'] > $existing->grade) {
                DB::table('student_subject')
                    ->where('student_id', $student->id)
                    ->where('subject_code', $record['subject_code'])
                    ->update([
                        'grade' => $record['grade'],
                        'status' => $record['status'],
                        'updated_at' => now(),
                    ]);
                $this->stats['history']['created']++; // Count as updated
            } else {
                $this->stats['duplicates']++;
            }
            return;
        }

        // Only distribute credits if the subject was passed
        if ($record['status'] !== 'passed') {
            // Failed subjects: just record, no credit distribution
            DB::table('student_subject')->insert([
                'student_id' => $student->id,
                'subject_code' => $record['subject_code'],
                'grade' => $record['grade'],
                'status' => $record['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->stats['history']['created']++;
            return;
        }

        // Get subject info
        $subjectCode = $record['subject_code'];
        $subjectCredits = $this->subjectCredits[$subjectCode] ?? 0;
        $subjectComponent = $this->subjectTypes[$subjectCode] ?? 'free_elective';
        
        if ($subjectCredits == 0) {
            Log::warning("Subject {$subjectCode} has 0 credits, skipping credit distribution");
            DB::table('student_subject')->insert([
                'student_id' => $student->id,
                'subject_code' => $record['subject_code'],
                'grade' => $record['grade'],
                'status' => $record['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->stats['history']['created']++;
            return;
        }

        // Distribute credits according to component limits
        $distribution = $this->distributeCredits($subjectComponent, $subjectCredits, $creditUsed);
        
        Log::info("Subject {$subjectCode} ({$subjectComponent}, {$subjectCredits} credits) distributed as: " . 
                  json_encode($distribution));

        // Determine primary component for this subject
        $assignedComponent = $subjectComponent;
        $creditsCounted = $distribution['total_assigned'] ?? $subjectCredits;
        $countsTowardsDegree = $distribution['counts_towards_degree'] ?? true;
        
        // If credits went to free_elective instead, record that
        if (!empty($distribution['free_elective']) && empty($distribution[$subjectComponent])) {
            $assignedComponent = 'free_elective';
        }

        // Insert record with credit distribution tracking
        DB::table('student_subject')->insert([
            'student_id' => $student->id,
            'subject_code' => $record['subject_code'],
            'grade' => $record['grade'],
            'status' => $record['status'],
            'counts_towards_degree' => $countsTowardsDegree,
            'assigned_component' => $assignedComponent,
            'credits_counted' => $creditsCounted,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->stats['history']['created']++;
    }

    /**
     * Distribute credits across components respecting limits
     * Returns array with distribution details and updates $creditUsed
     * 
     * SPECIAL CASE: Nivelación subjects have NO LIMIT
     * 
     * OVERFLOW LOGIC:
     * 1. Try to assign to original component
     * 2. If component is full, overflow to free_elective
     * 3. If free_elective is also full, mark as 'lost' (doesn't count)
     * 
     * Example: 9 max optativa_profesional, student has 12 credits
     * - 9 go to optativa_profesional
     * - 3 overflow to free_elective (if space available)
     * - If free_elective full, those 3 are lost and don't count for progress
     */
    private function distributeCredits(string $component, int $credits, array &$creditUsed): array
    {
        $distribution = [];
        $remainingCredits = $credits;
        
        // SPECIAL CASE: Nivelación subjects have no limit and don't count for degree progress
        if ($component === 'leveling') {
            $distribution[$component] = $credits;
            $creditUsed[$component] += $credits;
            $distribution['total_assigned'] = $credits;
            $distribution['counts_towards_degree'] = false; // Nivelación doesn't count for degree
            $distribution['lost'] = 0;
            Log::info("Materia de Nivelación: {$credits} créditos asignados (sin límite, no cuenta para avance)");
            return $distribution;
        }
        
        // Get limit for this component
        $componentLimit = $this->creditLimits[$component] ?? 0;
        $currentUsed = $creditUsed[$component] ?? 0;
        $availableInComponent = max(0, $componentLimit - $currentUsed);
        
        // Step 1: Try to assign to original component
        if ($availableInComponent > 0) {
            $assignedToComponent = min($remainingCredits, $availableInComponent);
            $distribution[$component] = $assignedToComponent;
            $creditUsed[$component] += $assignedToComponent;
            $remainingCredits -= $assignedToComponent;
            
            Log::info("Assigned {$assignedToComponent} credits to {$component} (used: {$creditUsed[$component]}/{$componentLimit})");
        } else {
            Log::info("Component {$component} is FULL ({$currentUsed}/{$componentLimit}), overflow needed");
        }
        
        // Step 2: If there are remaining credits, overflow to free_elective
        if ($remainingCredits > 0 && $component !== 'free_elective') {
            $freeElectiveLimit = $this->creditLimits['free_elective'] ?? 36;
            $currentFreeElective = $creditUsed['free_elective'] ?? 0;
            $availableInFreeElective = max(0, $freeElectiveLimit - $currentFreeElective);
            
            if ($availableInFreeElective > 0) {
                $assignedToFreeElective = min($remainingCredits, $availableInFreeElective);
                $distribution['free_elective'] = ($distribution['free_elective'] ?? 0) + $assignedToFreeElective;
                $creditUsed['free_elective'] += $assignedToFreeElective;
                $remainingCredits -= $assignedToFreeElective;
                
                Log::info("Overflow: {$assignedToFreeElective} credits to free_elective (used: {$creditUsed['free_elective']}/{$freeElectiveLimit})");
            } else {
                Log::warning("Free elective is FULL ({$currentFreeElective}/{$freeElectiveLimit}), credits will be lost");
            }
        }
        
        // Step 3: If still remaining credits, they are LOST (don't count toward degree)
        $distribution['lost'] = $remainingCredits;
        if ($remainingCredits > 0) {
            Log::warning("LOST CREDITS: {$remainingCredits} from component {$component} (all components full)");
        }
        
        $distribution['total_assigned'] = $credits - $remainingCredits;
        $distribution['counts_towards_degree'] = $remainingCredits == 0;
        
        return $distribution;
    }

    /**
     * Convert period format from CSV to our format
     */
    private function convertPeriod(string $period): string
    {
        // Convert formats like "2025-1S" to "2025-1"
        return preg_replace('/^(\d{4})-(\d+)[SI]?$/', '$1-$2', $period);
    }

    /**
     * Get import statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Reset statistics
     */
    public function resetStats(): void
    {
        $this->stats = [
            'students' => ['total' => 0, 'created' => 0, 'existing' => 0],
            'subjects' => ['total_records' => 0, 'valid' => 0, 'invalid' => 0, 'invalid_codes' => []],
            'history' => ['created' => 0],
            'current' => ['created' => 0],
            'duplicates' => 0,
            'processing_time' => 0,
            'records_per_second' => 0
        ];
        $this->invalidSubjectCodes = [];
        $this->studentsCache = [];
        $this->studentsByDocument = [];
        $this->successfulRecords = [];
        $this->failedRecords = [];
    }

    /**
     * Get successful import records
     */
    public function getSuccessfulRecords(): array
    {
        return $this->successfulRecords;
    }

    /**
     * Get failed import records with error reasons
     */
    public function getFailedRecords(): array
    {
        return $this->failedRecords;
    }
}
