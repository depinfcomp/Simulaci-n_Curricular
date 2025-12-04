<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Subject;
use App\Models\ElectiveSubject;
use App\Models\LevelingSubject;
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
    private $currentPeriod = null; // Current academic period (subjects from this period are "cursando")
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
        
        // Load leveling subjects (nivelación) - ALL of them, no filter
        $levelingSubjects = LevelingSubject::all();
        foreach ($levelingSubjects as $leveling) {
            // Store in validSubjects so they're recognized during import
            $this->validSubjects[$leveling->code] = true;
            $this->subjectCredits[$leveling->code] = $leveling->credits;
            $this->subjectTypes[$leveling->code] = 'leveling'; // Map to leveling component
            Log::info("Leveling subject loaded: {$leveling->code} - {$leveling->name}");
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
    public function importFromCSV(string $filePath, bool $dryRun = false, ?string $currentPeriod = null): array
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

        // Auto-detect current period if not provided - BEFORE reading records
        if (!$currentPeriod) {
            $currentPeriod = $this->detectCurrentPeriodFromFile($filePath);
            if ($currentPeriod) {
                Log::info("Auto-detected current period: {$currentPeriod}");
            }
        }
        
        // Store current period for later use in processRow()
        $this->currentPeriod = $currentPeriod;
        
        if ($currentPeriod) {
            Log::info("Current period: {$currentPeriod} - subjects from this period will be marked as 'cursando'");
        }

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
     * Detect current academic period by reading directly from CSV file
     * This is done BEFORE processing rows to avoid grade assignment
     */
    private function detectCurrentPeriodFromFile(string $filePath): ?string
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return null;
        }

        // Skip to header
        $header = null;
        while (($row = fgetcsv($handle)) !== false) {
            if (!$this->isEmptyRow($row)) {
                $header = $row;
                break;
            }
        }

        if ($header === null) {
            fclose($handle);
            return null;
        }

        // Find period column index
        $periodIndex = array_search('PERIODO_INSCRIPCION', $header);
        if ($periodIndex === false) {
            fclose($handle);
            return null;
        }

        // Collect all periods
        $periods = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }
            
            $period = trim($row[$periodIndex] ?? '');
            if ($period && !in_array($period, $periods)) {
                $periods[] = $period;
            }
        }

        fclose($handle);

        if (empty($periods)) {
            return null;
        }

        // Sort periods in descending order (most recent first)
        usort($periods, function($a, $b) {
            // Extract year and semester from format: 2025-1S, 2024-2S, etc.
            preg_match('/(\d{4})/', $a, $matchesA);
            preg_match('/(\d{4})/', $b, $matchesB);
            
            $yearA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
            $yearB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
            
            // Compare years first
            if ($yearA !== $yearB) {
                return $yearB - $yearA; // Descending order
            }
            
            // Same year, compare semester (2 > 1)
            preg_match('/[12]/', $a, $semA);
            preg_match('/[12]/', $b, $semB);
            
            $semesterA = isset($semA[0]) ? (int)$semA[0] : 0;
            $semesterB = isset($semB[0]) ? (int)$semB[0] : 0;
            
            return $semesterB - $semesterA; // Descending order
        });

        // Return the most recent period
        $detectedPeriod = $periods[0];
        Log::info("Detected periods from CSV: " . implode(', ', array_slice($periods, 0, 5)) . "...");
        
        return $detectedPeriod;
    }

    /**
     * Detect current academic period by finding the most recent period in the data
     * Periods are sorted in descending order (2025-1S > 2024-2S > 2024-1S, etc.)
     */
    private function detectCurrentPeriod(array $studentsByDocument): ?string
    {
        $periods = [];
        
        // Collect all unique periods from all student records
        foreach ($studentsByDocument as $studentRecords) {
            foreach ($studentRecords as $record) {
                $period = $record['periodo_inscripcion'] ?? null;
                if ($period && !in_array($period, $periods)) {
                    $periods[] = $period;
                }
            }
        }
        
        if (empty($periods)) {
            return null;
        }
        
        // Sort periods in descending order (most recent first)
        usort($periods, function($a, $b) {
            // Extract year and semester from format: 2025-1S, 2024-2S, etc.
            // Format can be: YYYY-1S, YYYY-2S, YYYY-1, YYYY-2, etc.
            
            // Extract year (first 4 digits)
            preg_match('/(\d{4})/', $a, $matchesA);
            preg_match('/(\d{4})/', $b, $matchesB);
            
            $yearA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
            $yearB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
            
            // Compare years first
            if ($yearA !== $yearB) {
                return $yearB - $yearA; // Descending order
            }
            
            // Same year, compare semester (2 > 1)
            // Extract semester number (look for 1 or 2 after the year)
            preg_match('/[12]/', $a, $semA);
            preg_match('/[12]/', $b, $semB);
            
            $semesterA = isset($semA[0]) ? (int)$semA[0] : 0;
            $semesterB = isset($semB[0]) ? (int)$semB[0] : 0;
            
            return $semesterB - $semesterA; // Descending order
        });
        
        // Return the most recent period (first after sorting)
        $detectedPeriod = $periods[0];
        Log::info("Detected periods: " . implode(', ', array_slice($periods, 0, 5)) . "...");
        
        return $detectedPeriod;
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
            
            // First pass: collect all historical records with their subject info
            $historicalRecordsToProcess = [];
            
            foreach ($studentRecords as $record) {
                if ($record['is_current']) {
                    // This is a current subject (no grade)
                    $this->createCurrentSubject($student, $record);
                } else {
                    // Collect for credit distribution
                    $subjectInfo = $this->getSubjectInfo($record['subject_code']);
                    
                    // If subject not found, create a placeholder for free elective
                    if (!$subjectInfo) {
                        $subjectInfo = [
                            'name' => $record['raw_data']['asignatura'] ?? 'Materia no definida',
                            'credits' => (int)($record['raw_data']['creditos'] ?? 3),
                            'type' => 'libre_eleccion',
                            'source' => 'undefined'
                        ];
                        Log::warning("Subject {$record['subject_code']} not found in system - treating as free elective");
                    }
                    
                    $historicalRecordsToProcess[] = array_merge($record, [
                        'subject_info' => $subjectInfo
                    ]);
                }
            }
            
            // Second pass: distribute credits intelligently
            $this->distributeCreditsAndCreateRecords($student, $historicalRecordsToProcess);
            
            // After all records processed, calculate and save student metrics using StudentMetricsService
            $metricsService = app(StudentMetricsService::class);
            $metrics = $metricsService->calculateAndSaveMetrics($student->document);
            
            Log::info("Updated academic metrics for student {$student->document}: " .
                      "Progress: {$metrics['progress_percentage']}%, Avg: {$metrics['average_grade']}, " .
                      "Credits: {$metrics['approved_credits']}");
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
        
        // IMPORTANT: Subjects NOT found are NOT errors
        // They will be imported as FREE ELECTIVE automatically
        if (!$isValid) {
            Log::info("Subject {$codAsignatura} not found in database - will be imported as free elective");
            // Do NOT add to invalidSubjectCodes
            // Do NOT add to failedRecords
            // Continue normal processing
        }

        $this->stats['subjects']['valid']++;

        // Determine if this is historical (has grade) or current (no grade)
        // IMPORTANT: Alphabetic grades (AP/APROBADA, RE/REPROBADA) should NOT set numeric grade
        // Only actual numeric scores should be stored in the grade field
        
        // CHECK IF THIS IS FROM CURRENT PERIOD - if so, force as "cursando" regardless of grades
        $isCurrentPeriod = false;
        if ($this->currentPeriod && $periodoInscripcion === $this->currentPeriod) {
            $isCurrentPeriod = true;
            Log::info("Subject {$codAsignatura} for student {$documento} is from current period {$periodoInscripcion} - marking as 'cursando'");
        }
        
        $hasGrade = false;
        $grade = null;
        
        if ($isCurrentPeriod) {
            // Force as current subject - NO GRADE
            $hasGrade = false;
            $grade = null;
            $notaAlfabeticaRaw = null; // Clear alphabetic grade too
        } else {
            // Normal processing for historical subjects
            if (!empty($notaNumerica) && is_numeric($notaNumerica)) {
                $hasGrade = true;
                $grade = (float) $notaNumerica;
            }
            // Do NOT convert alphabetic grades to numeric values
            // AP/RE should remain as alphabetic_grade only, grade should be NULL
        }

        // Determine status based on grade or alphabetic grade
        $status = 'enrolled'; // default
        
        if ($isCurrentPeriod) {
            // Current period subjects are always 'enrolled' (cursando)
            $status = 'enrolled';
        } elseif ($hasGrade) {
            $status = $grade >= 3.0 ? 'passed' : 'failed';
        } elseif (!empty($notaAlfabeticaRaw)) {
            // If only alphabetic grade, determine status but don't assign numeric grade
            if (strtoupper($notaAlfabeticaRaw) === 'AP' || strtoupper($notaAlfabeticaRaw) === 'APROBADA') {
                $hasGrade = true; // Mark as having evaluation (not current)
                $status = 'passed';
                // grade remains NULL - qualitative evaluation
            } elseif (strtoupper($notaAlfabeticaRaw) === 'RE' || strtoupper($notaAlfabeticaRaw) === 'REPROBADA') {
                $hasGrade = true;
                $status = 'failed';
                // grade remains NULL - qualitative evaluation
            }
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
            'alphabetic_grade' => $notaAlfabeticaRaw, // Include alphabetic grade
            'status' => $status,
            'period' => $periodoInscripcion, // Rename to 'period' for consistency
            'periodo_inscripcion' => $periodoInscripcion, // Keep old name for backward compatibility
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
            ->where('student_document', $student->document)
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
            'subject_name' => $record['raw_data']['asignatura'] ?? null,
            'semester_period' => $this->convertPeriod($record['periodo_inscripcion']),
            'status' => 'cursando',
            'partial_grade' => null,
        ]);

        $this->stats['current']['created']++;
    }

    /**
     * Create historical academic record (simplified - no credit distribution)
     * Credit distribution is now handled dynamically by CreditDistributionService
     */
    private function createHistoricalRecordWithCreditDistribution(Student $student, array $record, array &$creditUsed): void
    {
        $subjectCode = $record['subject_code'];
        
        // Get subject information from database
        $subjectInfo = $this->getSubjectInfo($subjectCode);
        
        if (!$subjectInfo) {
            Log::warning("Subject info not found for code: {$subjectCode}");
            return;
        }
        
        // Check if record already exists
        $existing = DB::table('student_subject')
            ->where('student_document', $student->document)
            ->where('subject_code', $subjectCode)
            ->first();

        if ($existing) {
            // Update only if new grade is better (higher than existing)
            if ($record['grade'] > $existing->grade) {
                DB::table('student_subject')
                    ->where('student_document', $student->document)
                    ->where('subject_code', $subjectCode)
                    ->update([
                        'grade' => $record['grade'],
                        'alphabetic_grade' => $record['alphabetic_grade'] ?? null,
                        'status' => $record['status'],
                        'period' => $record['period'] ?? null,
                        'updated_at' => now(),
                    ]);
                $this->stats['history']['created']++; // Count as updated
            } else {
                $this->stats['duplicates']++;
            }
            return;
        }

        // Insert the raw data with denormalized subject info
        // Credit counting will be calculated dynamically when needed
        DB::table('student_subject')->insert([
            'student_document' => $student->document,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectInfo['name'],
            'subject_credits' => $subjectInfo['credits'],
            'subject_type' => $subjectInfo['type'],
            'grade' => $record['grade'],
            'alphabetic_grade' => $record['alphabetic_grade'] ?? null,
            'status' => $record['status'],
            'period' => $record['period'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->stats['history']['created']++;
    }

    /**
     * Distribute credits intelligently across components with overflow handling
     * This is the core logic that implements the credit distribution rules
     */
    private function distributeCreditsAndCreateRecords(Student $student, array $historicalRecords): void
    {
        // Initialize credit trackers per component
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
        
        // Track which subject codes we've already seen (for duplicate detection)
        $seenSubjectCodes = [];
        
        foreach ($historicalRecords as $record) {
            $subjectCode = $record['subject_code'];
            $subjectInfo = $record['subject_info'];
            $totalCredits = $subjectInfo['credits'];
            $originalComponent = $this->subjectTypes[$subjectCode] ?? $this->mapSubjectTypeToComponent($subjectInfo['type']);
            
            // Check if this is a duplicate
            $isDuplicate = isset($seenSubjectCodes[$subjectCode]);
            $seenSubjectCodes[$subjectCode] = ($seenSubjectCodes[$subjectCode] ?? 0) + 1;
            
            // Determine credit distribution
            $effectiveCredits = 0;      // Credits that count for the original component
            $overflowCredits = 0;       // Credits that overflow to free elective
            $actualComponent = null;    // Final component assignment
            $countsForPercentage = true; // Whether it counts for progress percentage
            $assignmentNotes = '';
            
            // IMPORTANT: Leveling subjects DON'T count towards degree (167 credits)
            // They can count for PAPA if they have numeric grade, but not for progress
            if ($originalComponent === 'leveling') {
                $countsForPercentage = false;
                $effectiveCredits = $totalCredits;
                $overflowCredits = 0;
                $actualComponent = 'leveling';
                $creditUsed['leveling'] += $totalCredits;
                $assignmentNotes = "Nivelación - no cuenta para avance de 167 créditos";
                
                $this->createAcademicHistoryRecord($student, $record, $subjectInfo, [
                    'effective_credits' => $effectiveCredits,
                    'overflow_credits' => $overflowCredits,
                    'actual_component_type' => $actualComponent,
                    'is_duplicate' => $isDuplicate,
                    'counts_for_percentage' => $countsForPercentage,
                    'assignment_notes' => $assignmentNotes
                ]);
                
                continue; // Skip normal distribution logic for leveling
            }
            
            // If subject not found in system or marked as undefined, treat as free elective
            if (!isset($subjectInfo['source']) || $subjectInfo['source'] === 'undefined' || $subjectInfo['source'] === null) {
                $originalComponent = 'free_elective';
                Log::info("Subject {$subjectCode} not found in system - assigning to free_elective");
            }
            
            // Calculate remaining space in original component
            $componentLimit = $this->creditLimits[$originalComponent] ?? 0;
            $componentUsed = $creditUsed[$originalComponent] ?? 0;
            $componentRemaining = max(0, $componentLimit - $componentUsed);
            
            if ($componentRemaining >= $totalCredits) {
                // All credits fit in original component
                $effectiveCredits = $totalCredits;
                $overflowCredits = 0;
                $actualComponent = $originalComponent;
                $creditUsed[$originalComponent] += $totalCredits;
                $assignmentNotes = "Todos los {$totalCredits} créditos asignados a {$originalComponent}";
                
            } elseif ($componentRemaining > 0) {
                // Partial fit: some go to original, rest to free elective
                $effectiveCredits = $componentRemaining;
                $overflowCredits = $totalCredits - $componentRemaining;
                $actualComponent = $originalComponent;
                $creditUsed[$originalComponent] += $effectiveCredits;
                
                // Try to place overflow in free elective
                $freeElectiveLimit = $this->creditLimits['free_elective'] ?? 36;
                $freeElectiveUsed = $creditUsed['free_elective'] ?? 0;
                $freeElectiveRemaining = max(0, $freeElectiveLimit - $freeElectiveUsed);
                
                if ($freeElectiveRemaining >= $overflowCredits) {
                    $creditUsed['free_elective'] += $overflowCredits;
                    $assignmentNotes = "{$effectiveCredits} créditos a {$originalComponent}, {$overflowCredits} a libre_eleccion";
                } else {
                    // Free elective also full - mark as N/A
                    $countsForPercentage = false;
                    $actualComponent = 'na';
                    $assignmentNotes = "{$effectiveCredits} créditos a {$originalComponent}, {$overflowCredits} exceden límite (N/A)";
                }
                
            } else {
                // Original component full, try free elective
                $freeElectiveLimit = $this->creditLimits['free_elective'] ?? 36;
                $freeElectiveUsed = $creditUsed['free_elective'] ?? 0;
                $freeElectiveRemaining = max(0, $freeElectiveLimit - $freeElectiveUsed);
                
                if ($freeElectiveRemaining >= $totalCredits) {
                    // All fits in free elective
                    $effectiveCredits = $totalCredits;
                    $overflowCredits = 0;
                    $actualComponent = 'free_elective';
                    $creditUsed['free_elective'] += $totalCredits;
                    $assignmentNotes = "Componente {$originalComponent} lleno, {$totalCredits} créditos a libre_eleccion";
                    
                } elseif ($freeElectiveRemaining > 0) {
                    // Partial fit in free elective
                    $effectiveCredits = $freeElectiveRemaining;
                    $overflowCredits = $totalCredits - $freeElectiveRemaining;
                    $actualComponent = 'na';
                    $countsForPercentage = false;
                    $creditUsed['free_elective'] += $effectiveCredits;
                    $assignmentNotes = "{$effectiveCredits} créditos a libre_eleccion, {$overflowCredits} exceden límite (N/A)";
                    
                } else {
                    // Everything is full - mark as N/A
                    $effectiveCredits = 0;
                    $overflowCredits = $totalCredits;
                    $actualComponent = 'na';
                    $countsForPercentage = false;
                    $assignmentNotes = "Todos los componentes llenos - {$totalCredits} créditos no cuentan para porcentaje (N/A)";
                }
            }
            
            // Create the academic history record
            $this->createAcademicHistoryRecord($student, $record, $subjectInfo, [
                'effective_credits' => $effectiveCredits,
                'overflow_credits' => $overflowCredits,
                'actual_component_type' => $actualComponent,
                'is_duplicate' => $isDuplicate,
                'counts_for_percentage' => $countsForPercentage,
                'assignment_notes' => $assignmentNotes,
            ]);
            
            Log::info("Student {$student->document} - Subject {$subjectCode}: {$assignmentNotes}");
        }
        
        // Log final credit distribution
        Log::info("Student {$student->document} final credit distribution: " . json_encode($creditUsed));
    }
    
    /**
     * Create academic history record with credit distribution info
     */
    private function createAcademicHistoryRecord(Student $student, array $record, array $subjectInfo, array $creditDistribution): void
    {
        $subjectCode = $record['subject_code'];
        
        // Check if record already exists
        $existing = DB::table('student_subject')
            ->where('student_document', $student->document)
            ->where('subject_code', $subjectCode)
            ->first();

        if ($existing) {
            // Update only if new grade is better
            if ($record['grade'] > $existing->grade) {
                DB::table('student_subject')
                    ->where('student_document', $student->document)
                    ->where('subject_code', $subjectCode)
                    ->update([
                        'grade' => $record['grade'],
                        'alphabetic_grade' => $record['alphabetic_grade'] ?? null,
                        'status' => $record['status'],
                        'period' => $record['period'] ?? null,
                        'updated_at' => now(),
                    ]);
            }
            $this->stats['duplicates']++;
            return;
        }

        // Insert the record with credit distribution
        DB::table('student_subject')->insert([
            'student_document' => $student->document,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectInfo['name'],
            'subject_credits' => $subjectInfo['credits'],
            'subject_type' => $subjectInfo['type'],
            'grade' => $record['grade'],
            'alphabetic_grade' => $record['alphabetic_grade'] ?? null,
            'status' => $record['status'],
            'period' => $record['period'] ?? null,
            'effective_credits' => $creditDistribution['effective_credits'],
            'overflow_credits' => $creditDistribution['overflow_credits'],
            'actual_component_type' => $creditDistribution['actual_component_type'],
            'is_duplicate' => $creditDistribution['is_duplicate'],
            'counts_for_percentage' => $creditDistribution['counts_for_percentage'],
            'assignment_notes' => $creditDistribution['assignment_notes'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->stats['history']['created']++;
    }

    /**
     * Get subject information (name, credits, type) from database
     */
    private function getSubjectInfo(string $subjectCode): ?array
    {
        // Try regular subjects first
        $subject = Subject::where('code', $subjectCode)->first();
        if ($subject) {
            return [
                'name' => $subject->name,
                'credits' => $subject->credits,
                'type' => $subject->type,
                'source' => 'Subject',
            ];
        }
        
        // Try elective subjects
        $elective = ElectiveSubject::where('code', $subjectCode)->first();
        if ($elective) {
            return [
                'name' => $elective->name,
                'credits' => $elective->credits,
                'type' => $elective->elective_type,
                'source' => 'ElectiveSubject',
            ];
        }
        
        // Try leveling subjects
        $leveling = LevelingSubject::where('code', $subjectCode)->first();
        if ($leveling) {
            return [
                'name' => $leveling->name,
                'credits' => $leveling->credits,
                'type' => 'nivelacion',
                'source' => 'LevelingSubject',
            ];
        }
        
        // Try to find through alias
        $alias = DB::table('subject_aliases')
            ->where('alias_code', $subjectCode)
            ->first();
            
        if ($alias) {
            // Recursively get info for the main subject
            return $this->getSubjectInfo($alias->subject_code);
        }
        
        // Subject not found in any source - will be treated as free elective
        return null;
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
