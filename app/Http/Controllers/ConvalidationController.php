<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExternalCurriculum;
use App\Models\ExternalSubject;
use App\Models\ExternalSubjectComponent;
use App\Models\SubjectConvalidation;
use App\Models\Subject;
use App\Models\Student;
use App\Models\StudentConvalidation;
use App\Models\ConvalidationGroup;
use App\Models\ConvalidationGroupSubject;
use App\Models\ConvalidationSimulation;
use App\Models\LevelingSubject;
use App\Models\ElectiveSubject;
use App\Services\ExcelImportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConvalidationController extends Controller
{
    /**
     * Display the main convalidation dashboard.
     */
    public function index()
    {
        $externalCurriculums = ExternalCurriculum::with('externalSubjects')
            ->where('status', 'active')
            ->latest()
            ->get();

        $stats = [
            'total_curriculums' => $externalCurriculums->count(),
            'total_external_subjects' => ExternalSubject::count(),
            'total_convalidations' => SubjectConvalidation::count(),
            'pending_convalidations' => ExternalSubject::where('change_type', 'added')->count(),
        ];

        return view('convalidation.index', compact('externalCurriculums', 'stats'));
    }

    /**
     * Show the form for uploading a new external curriculum.
     */
    public function create()
    {
        return view('convalidation.create');
    }

    /**
     * Store a newly uploaded external curriculum.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'excel_file' => 'required|file|mimes:csv,txt|max:10240' // 10MB max, CSV only for now
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Store the uploaded file
            $file = $request->file('excel_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('external_curriculums', $filename, 'public');

            // Create the external curriculum record
            $externalCurriculum = ExternalCurriculum::create([
                'name' => $request->name,
                'institution' => $request->institution,
                'description' => $request->description,
                'uploaded_file' => $filePath,
                'metadata' => [
                    'original_filename' => $file->getClientOriginalName(),
                    'uploaded_at' => now(),
                    'file_size' => $file->getSize()
                ]
            ]);

            // Import the CSV data
            $importService = new ExcelImportService();
            $importService->validateFile($file);
            $importedCount = $importService->importCurriculum($file, $externalCurriculum->id);

            return redirect()->route('convalidation.show', $externalCurriculum)
                ->with('success', 'Malla externa cargada exitosamente. ' . $importedCount . ' materias importadas.');

        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Error al procesar el archivo: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified external curriculum for convalidation.
     */
    public function show(ExternalCurriculum $externalCurriculum)
    {
        // Check if curriculum is locked (has PDF report)
        if ($externalCurriculum->pdf_report_path) {
            return redirect()->route('convalidation.index')
                ->with('warning', 'Esta malla curricular está bloqueada. No se pueden modificar las convalidaciones. Puede descargar el reporte PDF desde el listado.');
        }
        
        $externalCurriculum->load([
            'externalSubjects.convalidation.internalSubject',
            'externalSubjects.convalidationGroup.internalSubjects'
        ]);
        $subjectsBySemester = $externalCurriculum->getConvalidationsBySemester();
        $internalSubjects = Subject::orderBy('semester')->orderBy('name')->get();
        $stats = $externalCurriculum->getStats();
        
        // Parse metadata to check if it comes from simulation
        $metadata = null;
        if ($externalCurriculum->metadata) {
            $rawMetadata = $externalCurriculum->metadata;
            $metadata = is_array($rawMetadata) ? $rawMetadata : (is_string($rawMetadata) ? json_decode($rawMetadata, true) : null);
        }

        return view('convalidation.show', compact('externalCurriculum', 'subjectsBySemester', 'internalSubjects', 'stats', 'metadata'));
    }

    /**
     * Create or update a convalidation mapping.
     */
    public function storeConvalidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'external_subject_id' => 'required|exists:external_subjects,id',
            'convalidation_type' => 'required|in:direct,flexible_component,not_convalidated',
            'internal_subject_code' => 'nullable|exists:subjects,code',
            'notes' => 'nullable|string',
            'component_type' => 'required|in:fundamental_required,professional_required,optional_fundamental,optional_professional,free_elective,thesis,leveling',
            'create_new_code' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Check if we need to create a new placeholder code
        $createNewCode = $request->input('create_new_code', false);

        // Validate that direct convalidations have an internal subject (unless creating new code)
        if ($request->convalidation_type === 'direct' && !$request->internal_subject_code && !$createNewCode) {
            return response()->json(['error' => 'Las convalidaciones directas requieren una materia interna'], 422);
        }

        // Validate that flexible_component type doesn't have an internal subject
        if ($request->convalidation_type === 'flexible_component' && $request->internal_subject_code) {
            return response()->json(['error' => 'Los componentes electivos no deben tener una materia interna asignada'], 422);
        }

        // Validate that not_convalidated type doesn't have an internal subject
        if ($request->convalidation_type === 'not_convalidated' && $request->internal_subject_code) {
            return response()->json(['error' => 'Las materias no convalidadas no deben tener una materia interna asignada'], 422);
        }

        // Validate that flexible_component has a flexible component_type
        if ($request->convalidation_type === 'flexible_component') {
            $flexibleComponents = ['optional_fundamental', 'optional_professional', 'free_elective'];
            if (!in_array($request->component_type, $flexibleComponents)) {
                return response()->json(['error' => 'Los componentes electivos deben ser Optativa Fundamental, Optativa Profesional o Libre Elección'], 422);
            }
        }

        try {
            $externalSubject = ExternalSubject::findOrFail($request->external_subject_id);

            // CRITICAL: Check if there's already a convalidation being created (race condition protection)
            $existingConvalidation = SubjectConvalidation::where('external_subject_id', $externalSubject->id)->first();
            if ($existingConvalidation) {
                Log::warning("Ya existe una convalidación para external_subject_id: {$externalSubject->id}, eliminando duplicado...");
            }

            // Delete existing convalidation if any
            SubjectConvalidation::where('external_subject_id', $externalSubject->id)->delete();

            // Delete existing component assignment if any
            ExternalSubjectComponent::where('external_subject_id', $externalSubject->id)->delete();

            $internalSubjectCode = null;
            $notes = $request->notes ?? '';

            // If we need to create a new placeholder code
            if ($createNewCode && $request->convalidation_type === 'direct') {
                $placeholderCode = $this->generateNextPlaceholderCode($request->component_type, $externalSubject->external_curriculum_id);
                $internalSubjectCode = $placeholderCode;
                $notes .= "\n\nCódigo placeholder generado automáticamente: {$placeholderCode}";
            }
            // If it's a direct convalidation with existing code
            else if ($request->convalidation_type === 'direct') {
                $internalSubject = Subject::where('code', $request->internal_subject_code)->first();
                
                if ($internalSubject) {
                    // Check if this is an elective or optional subject
                    $isElectiveOrOptional = in_array($internalSubject->component_type, [
                        'free_elective',
                        'optional_fundamental',
                        'optional_professional'
                    ]);

                    if ($isElectiveOrOptional) {
                        // Check if this specific subject has already been used
                        $timesUsed = SubjectConvalidation::where('external_curriculum_id', $externalSubject->external_curriculum_id)
                            ->where('internal_subject_code', $internalSubject->code)
                            ->count();

                        if ($timesUsed > 0) {
                            // Try to find the next available subject of the same type
                            $nextAvailable = $this->getNextAvailableSubject($internalSubject->component_type, $externalSubject->external_curriculum_id);
                            
                            if ($nextAvailable) {
                                $internalSubjectCode = $nextAvailable->code;
                                $notes .= "\n\nNota: La materia {$internalSubject->code} ya estaba en uso. Se asignó automáticamente {$nextAvailable->code}.";
                            } else {
                                // No more subjects available, mark as not convalidated
                                return response()->json([
                                    'warning' => true,
                                    'message' => 'No hay más materias de tipo ' . $internalSubject->component_type . ' disponibles. ¿Desea marcarla como materia nueva?',
                                    'suggested_action' => 'not_convalidated'
                                ]);
                            }
                        } else {
                            $internalSubjectCode = $request->internal_subject_code;
                        }
                    } else {
                        $internalSubjectCode = $request->internal_subject_code;
                    }
                }
            }

            // Create new convalidation
            $convalidation = SubjectConvalidation::create([
                'external_curriculum_id' => $externalSubject->external_curriculum_id,
                'external_subject_id' => $externalSubject->id,
                'internal_subject_code' => $internalSubjectCode,
                'convalidation_type' => $request->convalidation_type,
                'notes' => $notes,
                'status' => 'pending'
            ]);

            // Create component assignment
            $componentAssignment = ExternalSubjectComponent::create([
                'external_curriculum_id' => $externalSubject->external_curriculum_id,
                'external_subject_id' => $externalSubject->id,
                'component_type' => $request->component_type,
                'notes' => null
            ]);

            Log::info("Convalidación creada exitosamente", [
                'external_subject_id' => $externalSubject->id,
                'convalidation_type' => $request->convalidation_type,
                'component_type' => $request->component_type
            ]);

            $convalidation->load('internalSubject');
            
            // Get updated statistics
            $curriculum = ExternalCurriculum::find($externalSubject->external_curriculum_id);
            $updatedStats = $curriculum->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Convalidación creada exitosamente',
                'convalidation' => $convalidation,
                'component_assignment' => $componentAssignment,
                'stats' => $updatedStats
            ]);

        } catch (\Exception $e) {
            Log::error("Error al crear convalidación: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Error al crear la convalidación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove a convalidation mapping.
     */
    public function destroyConvalidation($convalidationId)
    {
        try {
            $convalidation = SubjectConvalidation::findOrFail($convalidationId);
            $convalidation->delete();

            return response()->json([
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la convalidación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get suggestions for convalidation based on subject name similarity.
     */
    public function getSuggestions(Request $request)
    {
        $externalSubjectId = $request->external_subject_id;
        $externalSubject = ExternalSubject::findOrFail($externalSubjectId);
        
        // Get all internal subjects from all tables (subjects, leveling_subjects, elective_subjects)
        $internalSubjects = $this->getAllInternalSubjects();
        $suggestions = [];

        foreach ($internalSubjects as $internal) {
            $similarity = $this->calculateSimilarity($externalSubject->name, $internal['name']);
            
            if ($similarity > 0.3) { // 30% similarity threshold
                $suggestions[] = [
                    'code' => $internal['code'],
                    'name' => $internal['name'],
                    'credits' => $internal['credits'],
                    'component_type' => $internal['component_type'],
                    'source_table' => $internal['source_table'],
                    'similarity' => $similarity,
                    'match_percentage' => round($similarity * 100, 2)
                ];
            }
        }

        // Sort by similarity descending
        usort($suggestions, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Take top 5 suggestions
        $suggestions = array_slice($suggestions, 0, 5);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Calculate similarity between two strings.
     */
    private function calculateSimilarity($str1, $str2)
    {
        // Normalize strings
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        // Remove common words that don't add meaning
        $commonWords = ['de', 'la', 'el', 'y', 'del', 'las', 'los', 'con', 'para', 'por', 'en', 'a', 'un', 'una', 'al'];
        
        foreach ($commonWords as $word) {
            $str1 = str_replace(' ' . $word . ' ', ' ', ' ' . $str1 . ' ');
            $str2 = str_replace(' ' . $word . ' ', ' ', ' ' . $str2 . ' ');
        }

        $str1 = trim($str1);
        $str2 = trim($str2);

        // Calculate Levenshtein distance similarity
        $levenshtein = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        $levenshteinSimilarity = $maxLen > 0 ? 1 - ($levenshtein / $maxLen) : 0;

        // Calculate word-based similarity
        $words1 = explode(' ', $str1);
        $words2 = explode(' ', $str2);
        $commonWordsCount = count(array_intersect($words1, $words2));
        $totalWords = count(array_unique(array_merge($words1, $words2)));
        $wordSimilarity = $totalWords > 0 ? $commonWordsCount / $totalWords : 0;

        // Combine similarities with weights
        return ($levenshteinSimilarity * 0.6) + ($wordSimilarity * 0.4);
    }

    /**
     * Export convalidation report.
     */
    public function exportReport(ExternalCurriculum $externalCurriculum)
    {
        // This would generate a detailed report
        // For now, return JSON data
        $data = [
            'curriculum' => $externalCurriculum,
            'subjects' => $externalCurriculum->externalSubjects()->with('convalidation.internalSubject')->get(),
            'stats' => $externalCurriculum->getStats()
        ];

        return response()->json($data);
    }

    /**
     * Download the PDF report generated during simulation save
     */
    public function downloadPdfReport(ExternalCurriculum $externalCurriculum)
    {
        if (!$externalCurriculum->pdf_report_path) {
            abort(404, 'No se ha generado un reporte PDF para esta malla curricular.');
        }

        $filePath = storage_path('app/' . $externalCurriculum->pdf_report_path);

        if (!file_exists($filePath)) {
            abort(404, 'El archivo PDF no se encuentra disponible.');
        }

        return response()->download(
            $filePath,
            'Reporte_' . str_replace(' ', '_', $externalCurriculum->name) . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Delete an external curriculum and all its data.
     */
    public function destroy(ExternalCurriculum $externalCurriculum)
    {
        try {
            // Check if this curriculum has simulation metadata (came from /simulation)
            $hasSimulationSource = false;
            if ($externalCurriculum->metadata) {
                $rawMetadata = $externalCurriculum->metadata;
                $metadata = is_array($rawMetadata) ? $rawMetadata : (is_string($rawMetadata) ? json_decode($rawMetadata, true) : []);
                
                $hasSimulationSource = isset($metadata['source']) && $metadata['source'] === 'simulation';
            }
            
            // Delete the uploaded file
            if ($externalCurriculum->uploaded_file) {
                Storage::disk('public')->delete($externalCurriculum->uploaded_file);
            }

            // Delete the curriculum (cascade will handle related records)
            $externalCurriculum->delete();

            return redirect()->route('convalidation.index')
                ->with('success', 'Malla externa eliminada exitosamente')
                ->with('reset_simulation', $hasSimulationSource); // Flag to trigger localStorage clear

        } catch (\Exception $e) {
            return redirect()->route('convalidation.index')
                ->withErrors(['error' => 'Error al eliminar la malla: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete convalidation and reset simulation changes (bidirectional reset)
     */
    public function destroyAndResetSimulation(ExternalCurriculum $externalCurriculum)
    {
        try {
            // Delete the uploaded file
            if ($externalCurriculum->uploaded_file) {
                Storage::disk('public')->delete($externalCurriculum->uploaded_file);
            }

            // Delete the curriculum (cascade will handle related records)
            $externalCurriculum->delete();

            return response()->json([
                'success' => true,
                'message' => 'Convalidación eliminada exitosamente.',
                'reset_simulation' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar la convalidación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset all convalidations for a specific external curriculum
     * Deletes all convalidations and component assignments
     */
    public function resetConvalidations(ExternalCurriculum $externalCurriculum)
    {
        try {
            // Delete all convalidations for this curriculum
            $deletedConvalidations = SubjectConvalidation::where('external_curriculum_id', $externalCurriculum->id)->delete();
            
            // Delete all component assignments through external_subjects relationship
            // First get all external subject IDs for this curriculum
            $externalSubjectIds = $externalCurriculum->externalSubjects()->pluck('id');
            
            // Delete component assignments for these subjects
            $deletedComponents = ExternalSubjectComponent::whereIn('external_subject_id', $externalSubjectIds)->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deletedConvalidations} convalidaciones y {$deletedComponents} asignaciones de componentes.",
                'deleted_convalidations' => $deletedConvalidations,
                'deleted_components' => $deletedComponents
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al restablecer las convalidaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subjects that are already used in convalidations for a specific component type
     */
    public function getUsedSubjects(ExternalCurriculum $externalCurriculum, Request $request)
    {
        $componentType = $request->input('component_type');
        
        // Get all internal subject codes already used for this component type in this curriculum
        // We check subject_convalidations table because internal_subject_code is stored there
        $usedSubjects = \DB::table('subject_convalidations')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->join('external_subject_components', 'external_subject_components.external_subject_id', '=', 'external_subjects.id')
            ->where('external_subjects.external_curriculum_id', $externalCurriculum->id)
            ->where('external_subject_components.component_type', $componentType)
            ->where('subject_convalidations.convalidation_type', 'direct')
            ->whereNotNull('subject_convalidations.internal_subject_code')
            ->pluck('subject_convalidations.internal_subject_code')
            ->unique()
            ->values()
            ->toArray();
        
        return response()->json([
            'usedSubjects' => $usedSubjects
        ]);
    }

    /**
     * Get subjects used ONLY as optativas or free electives
     * This prevents human errors like selecting #LIBRE-01 when configuring a "Fundamental" component
     */
    public function getUsedOptativesAndFree(ExternalCurriculum $externalCurriculum)
    {
        // Get internal subject codes used ONLY for optativas and free electives
        $usedOptativesAndFree = \DB::table('subject_convalidations')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->join('external_subject_components', 'external_subject_components.external_subject_id', '=', 'external_subjects.id')
            ->where('external_subjects.external_curriculum_id', $externalCurriculum->id)
            ->whereIn('external_subject_components.component_type', [
                'optional_fundamental',
                'optional_professional',
                'free_elective'
            ])
            ->where('subject_convalidations.convalidation_type', 'direct')
            ->whereNotNull('subject_convalidations.internal_subject_code')
            ->pluck('subject_convalidations.internal_subject_code')
            ->unique()
            ->values()
            ->toArray();
        
        return response()->json([
            'usedOptativesAndFree' => $usedOptativesAndFree
        ]);
    }

    /**
     * Generate the next available placeholder code for a component type
     */
    private function generateNextPlaceholderCode($componentType, $curriculumId)
    {
        // Determine prefix based on component type
        $prefix = match($componentType) {
            'free_elective' => '#LIBRE-',
            'optional_fundamental', 'optional_professional' => '#OPT-',
            default => '#NEW-'
        };
        
        // Find all existing placeholder codes with this prefix in this curriculum
        $existingCodes = \DB::table('subject_convalidations')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->where('external_subjects.external_curriculum_id', $curriculumId)
            ->where('subject_convalidations.internal_subject_code', 'LIKE', $prefix . '%')
            ->pluck('subject_convalidations.internal_subject_code')
            ->toArray();
        
        // Extract numbers from existing codes
        $numbers = array_map(function($code) use ($prefix) {
            return (int) str_replace($prefix, '', $code);
        }, $existingCodes);
        
        // Find the next available number (start from 01)
        $nextNumber = 1;
        while (in_array($nextNumber, $numbers)) {
            $nextNumber++;
        }
        
        // Format with leading zeros
        return $prefix . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Analyze the impact of migrating students from original curriculum to external curriculum with convalidations
     */
    public function analyzeConvalidationImpact(ExternalCurriculum $externalCurriculum, Request $request)
    {
        try {
            // Log para debugging
            Log::info('Iniciando análisis de impacto', [
                'curriculum_id' => $externalCurriculum->id,
                'request_data' => $request->all()
            ]);

            // Get configuration parameters - Todos los límites de créditos
            $creditLimits = [
                'max_free_elective_credits' => $request->input('max_free_elective_credits', 36),
                'max_optional_professional_credits' => $request->input('max_optional_professional_credits', 9),
                'max_optional_fundamental_credits' => $request->input('max_optional_fundamental_credits', 6),
                'max_leveling_credits' => $request->input('max_leveling_credits', 12),
                'max_required_fundamental_credits' => $request->input('max_required_fundamental_credits', 60),
                'max_required_professional_credits' => $request->input('max_required_professional_credits', 80),
                'max_thesis_credits' => $request->input('max_thesis_credits', 6),
            ];

            Log::info('Límites de créditos configurados', $creditLimits);

            // Get all students from the original curriculum
            $students = Student::with([
                'subjects' => function($query) {
                    $query->wherePivot('status', 'passed');
                },
                'currentSubjects.subject'
            ])->get();

            // Get all convalidations for this external curriculum
            $convalidations = SubjectConvalidation::where('external_curriculum_id', $externalCurriculum->id)
                ->with(['externalSubject', 'internalSubject'])
                ->get();
            
            // Get N:N convalidation groups
            $nnGroups = ConvalidationGroup::where('external_curriculum_id', $externalCurriculum->id)
                ->with(['externalSubject', 'internalSubjects'])
                ->get();

            // Load external curriculum subjects if not already loaded
            if (!$externalCurriculum->relationLoaded('externalSubjects')) {
                $externalCurriculum->load('externalSubjects');
            }

            // Separate direct, flexible component, and not convalidated convalidations
            $directConvalidations = $convalidations->where('convalidation_type', 'direct');
            $flexibleConvalidations = $convalidations->where('convalidation_type', 'flexible_component');
            $notConvalidatedConvalidations = $convalidations->where('convalidation_type', 'not_convalidated');

            // Get all subjects from original curriculum
            $originalSubjects = Subject::with(['prerequisites', 'requiredFor'])->get()->keyBy('code');
            $totalOriginalSubjects = $originalSubjects->count();

            // Get REAL credit limits from the convalidation configuration
            // These are the actual component limits defined in the external curriculum
            $convalidatedCredits = $externalCurriculum->getCreditsByComponent();
            $realCreditLimits = [
                'max_fundamental_required' => $convalidatedCredits['fundamental_required'] ?? 0,
                'max_professional_required' => $convalidatedCredits['professional_required'] ?? 0,
                'max_optional_fundamental' => $convalidatedCredits['optional_fundamental'] ?? 0,
                'max_optional_professional' => $convalidatedCredits['optional_professional'] ?? 0,
                'max_free_elective' => $convalidatedCredits['free_elective'] ?? 0,
                'max_leveling' => $convalidatedCredits['leveling'] ?? 0,
                'max_thesis' => $convalidatedCredits['thesis'] ?? 0,
            ];

            $results = [
                'total_students' => $students->count(),
                'affected_students' => 0,
                'students_with_improved_progress' => 0,
                'students_with_no_change' => 0,
                'students_with_reduced_progress' => 0,
                'affected_percentage' => 0,
                'average_progress_change' => 0,
                'min_progress_change' => null,
                'max_progress_change' => null,
                'total_convalidated_subjects' => $directConvalidations->count() + $nnGroups->count(),
                'direct_convalidations_count' => $directConvalidations->count(),
                'nn_groups_count' => $nnGroups->count(),
                'flexible_convalidations_count' => $flexibleConvalidations->count(),
                'additional_subjects_required' => $notConvalidatedConvalidations->count(),
                'total_credits_lost' => $notConvalidatedConvalidations->sum(function($conv) { 
                    return $conv->externalSubject->credits ?? 0; 
                }),
                'curriculum_size_change' => [
                    'original_subjects' => $totalOriginalSubjects ?? Subject::count(),
                    'new_subjects' => $externalCurriculum->externalSubjects->count(),
                    'size_difference' => $externalCurriculum->externalSubjects->count() - (Subject::count())
                ],
                'student_details' => [],
                'subject_impact' => [],
                'configuration' => $realCreditLimits,
                'credits_by_component' => [
                    'fundamental_required' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'fundamental_optional' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'professional_required' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'professional_optional' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'leveling' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'thesis' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'free_elective' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                ],
                // Credits for progress comparison (same as "Progreso de Carrera")
                // These are the exact values shown in the dual progress bars
                'original_assigned_credits' => $externalCurriculum->getStats()['original_curriculum_stats']['assigned_credits'] ?? 0,
                'new_convalidated_credits' => $externalCurriculum->getStats()['new_curriculum_stats']['convalidated_credits'] ?? 0,
                // Keep these for the component breakdown table
                'original_curriculum_credits' => Subject::getCreditsByComponent(),
                'convalidated_credits_by_component' => $convalidatedCredits,
            ];

            $totalProgressChange = 0;
            $totalAbsoluteProgressChange = 0;
            $progressChanges = [];
            $subjectImpactMap = [];

            foreach ($students as $student) {
                try {
                    $impact = $this->calculateStudentConvalidationImpactWithComponentLimits(
                        $student, 
                        $directConvalidations, 
                        $flexibleConvalidations,
                        $notConvalidatedConvalidations,
                        $nnGroups,
                        $externalCurriculum,
                        $realCreditLimits,
                        $originalSubjects,
                        $totalOriginalSubjects
                    );

                    // Clasificar a todos los estudiantes, no solo los afectados
                    if (($impact['progress_change'] ?? 0) > 0.1) {
                        $results['students_with_improved_progress']++;
                        $results['affected_students']++;
                    } elseif (($impact['progress_change'] ?? 0) < -0.1) {
                        $results['students_with_reduced_progress']++;
                        $results['affected_students']++;
                    } else {
                        $results['students_with_no_change']++;
                        // Students with no change are also "affected" by the analysis
                        if (abs($impact['progress_change'] ?? 0) > 0) {
                            $results['affected_students']++;
                        }
                    }

                    $totalProgressChange += $impact['progress_change'] ?? 0;
                    $totalAbsoluteProgressChange += abs($impact['progress_change'] ?? 0);
                    $progressChanges[] = $impact['progress_change'] ?? 0;
                        
                    $results['student_details'][] = [
                        'student_id' => $student->id,
                        'document' => $student->document,
                        'original_progress' => round($impact['original_progress'] ?? 0, 1),
                        'new_progress' => round($impact['new_progress'] ?? 0, 1),
                        'progress_change' => round($impact['progress_change'] ?? 0, 1),
                        'convalidated_subjects' => $impact['convalidated_subjects_count'] ?? 0,
                        'new_subjects' => $impact['new_subjects_count'] ?? 0,
                        'total_convalidated_credits' => $impact['total_valid_credits'] ?? 0,
                        'convalidation_details' => $impact['convalidation_details'] ?? [],
                        'progress_explanation' => $impact['progress_explanation'] ?? 'Sin explicación disponible'
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing student ' . $student->id . ': ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue; // Skip this student and continue with the next one
                }
            }

            // Calculate final statistics
            $results['affected_percentage'] = $results['total_students'] > 0 
                ? round(($results['affected_students'] / $results['total_students']) * 100, 1)
                : 0;

            // Calculate average progress change in ABSOLUTE VALUE
            $results['average_progress_change'] = $results['affected_students'] > 0 
                ? round($totalAbsoluteProgressChange / $results['affected_students'], 1)
                : 0;

            // Calculate min and max progress change
            if (!empty($progressChanges)) {
                $results['min_progress_change'] = round(min($progressChanges), 1);
                $results['max_progress_change'] = round(max($progressChanges), 1);
            }

            // Calculate convalidated credits by component - use the same method as the main view
            $results['convalidated_credits_by_component'] = $externalCurriculum->getCreditsByComponent();

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Error en análisis de impacto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al analizar el impacto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total credits from external curriculum
     */
    public function getTotalCredits(ExternalCurriculum $externalCurriculum)
    {
        try {
            Log::info('getTotalCredits called for curriculum ID: ' . $externalCurriculum->id);
            $startTime = microtime(true);
            
            $totalCredits = $externalCurriculum->externalSubjects()
                ->sum('credits');
            
            $totalSubjects = $externalCurriculum->externalSubjects()->count();
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            Log::info('getTotalCredits completed', [
                'duration_ms' => $duration,
                'total_credits' => $totalCredits,
                'total_subjects' => $totalSubjects
            ]);

            return response()->json([
                'success' => true,
                'total_credits' => $totalCredits ?? 0,
                'total_subjects' => $totalSubjects,
                'duration_ms' => $duration
            ]);

        } catch (\Exception $e) {
            Log::error('getTotalCredits error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular créditos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary of convalidations for preview
     */
    public function getConvalidationsSummary(ExternalCurriculum $externalCurriculum)
    {
        try {
            $convalidations = SubjectConvalidation::where('external_curriculum_id', $externalCurriculum->id)
                ->with(['externalSubject', 'internalSubject'])
                ->get();

            $summary = $convalidations->map(function ($convalidation) {
                return [
                    'id' => $convalidation->id,
                    'external_subject_code' => $convalidation->externalSubject->code,
                    'external_subject_name' => $convalidation->externalSubject->name,
                    'internal_subject_code' => $convalidation->internalSubject ? $convalidation->internalSubject->code : null,
                    'internal_subject_name' => $convalidation->internalSubject ? $convalidation->internalSubject->name : null,
                    'type' => $convalidation->convalidation_type,
                    'credits' => $convalidation->externalSubject->credits,
                    'semester' => $convalidation->externalSubject->semester
                ];
            });

            return response()->json([
                'success' => true,
                'convalidations' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Select free electives based on credit limit and priority criteria
     */
    private function selectFreeElectives($freeElectiveConvalidations, $maxCredits, $priorityCriteria)
    {
        // Convert to array for sorting
        $electives = $freeElectiveConvalidations->toArray();

        // Sort based on priority criteria
        switch ($priorityCriteria) {
            case 'credits':
                usort($electives, function($a, $b) {
                    return $b['external_subject']['credits'] - $a['external_subject']['credits'];
                });
                break;
            case 'semester':
                usort($electives, function($a, $b) {
                    return $a['external_subject']['semester'] - $b['external_subject']['semester'];
                });
                break;
            case 'students':
                // For now, use credits as fallback since we'd need additional analysis
                usort($electives, function($a, $b) {
                    return $b['external_subject']['credits'] - $a['external_subject']['credits'];
                });
                break;
        }

        // Select electives up to the credit limit
        $selectedElectives = collect();
        $usedCredits = 0;

        foreach ($electives as $elective) {
            $electiveCredits = $elective['external_subject']['credits'];
            if ($usedCredits + $electiveCredits <= $maxCredits) {
                $selectedElectives->push(
                    SubjectConvalidation::with(['externalSubject', 'internalSubject'])
                        ->find($elective['id'])
                );
                $usedCredits += $electiveCredits;
            }
        }

        return $selectedElectives;
    }

    /**
     * Calculate the impact of migrating a student from original curriculum to external curriculum
     * considering convalidations correctly
     */
    private function calculateStudentConvalidationImpactCorrect(
        Student $student, 
        $directConvalidations, 
        $selectedFreeElectives, 
        $notConvalidatedConvalidations,
        $externalCurriculum
    ) {
        try {
            // Get student's passed subjects in original curriculum
            $passedSubjects = $student->subjects->where('pivot.status', 'passed')->keyBy('code');
            $originalTotalSubjects = Subject::count(); // Original curriculum size
            
            // Prevent division by zero
            if ($originalTotalSubjects == 0) {
                $originalTotalSubjects = 1;
            }
            
            $originalProgress = ($passedSubjects->count() / $originalTotalSubjects) * 100;
            
            // Get new curriculum size (external curriculum)
            $externalSubjects = $externalCurriculum->externalSubjects ?? $externalCurriculum->subjects;
            $newTotalSubjects = $externalSubjects ? $externalSubjects->count() : 0;
            
            // Prevent division by zero
            if ($newTotalSubjects == 0) {
                $newTotalSubjects = 1;
            }
            
            // CORRECTED LOGIC: Check each convalidation to see if student has the external subject
            $convalidatedCount = 0;
            $convalidationDetails = [];
            
            // Check direct convalidations - these require the student to have passed the EXTERNAL subject
            foreach ($directConvalidations as $convalidation) {
                if ($convalidation->externalSubject) {
                    $externalSubjectCode = $convalidation->externalSubject->code;
                    
                    // If student passed a subject that matches this external subject code
                    if ($passedSubjects->has($externalSubjectCode)) {
                        $convalidatedCount++;
                        $convalidationDetails[] = [
                            'type' => 'direct',
                            'student_subject' => $passedSubjects[$externalSubjectCode]->name,
                            'external_subject' => $convalidation->externalSubject->name,
                            'internal_subject' => $convalidation->internalSubject ? $convalidation->internalSubject->name : 'N/A'
                        ];
                    }
                }
            }
            
            // Check free elective convalidations
            foreach ($selectedFreeElectives as $convalidation) {
                if ($convalidation->externalSubject) {
                    $externalSubjectCode = $convalidation->externalSubject->code;
                    
                    // If student passed a subject that matches this external subject code
                    if ($passedSubjects->has($externalSubjectCode)) {
                        $convalidatedCount++;
                        $convalidationDetails[] = [
                            'type' => 'free_elective',
                            'student_subject' => $passedSubjects[$externalSubjectCode]->name,
                            'external_subject' => $convalidation->externalSubject->name,
                            'note' => 'Convalidada como libre elección'
                        ];
                    }
                }
            }
            
            // Calculate new subjects (subjects in new curriculum that student must take)
            // These are subjects in the external curriculum that are marked as "not_convalidated"
            $newSubjectsCount = 0;
            foreach ($notConvalidatedConvalidations as $convalidation) {
                if ($convalidation->externalSubject) {
                    $newSubjectsCount++;
                    $convalidationDetails[] = [
                        'type' => 'new_subject',
                        'external_subject' => $convalidation->externalSubject->name ?? 'Unknown',
                        'note' => 'Materia nueva que debe cursar'
                    ];
                }
            }
            
            // Calculate lost credits: subjects student took but can't be convalidated
            $lostCreditsCount = 0;
            $convalidatedSubjectCodes = collect();
            
            // Collect all subject codes that were successfully convalidated
            foreach ($directConvalidations as $convalidation) {
                if ($convalidation->externalSubject && $passedSubjects->has($convalidation->externalSubject->code)) {
                    $convalidatedSubjectCodes->push($convalidation->externalSubject->code);
                }
            }
            
            foreach ($selectedFreeElectives as $convalidation) {
                if ($convalidation->externalSubject && $passedSubjects->has($convalidation->externalSubject->code)) {
                    $convalidatedSubjectCodes->push($convalidation->externalSubject->code);
                }
            }
            
            // Make sure we don't have duplicates
            $convalidatedSubjectCodes = $convalidatedSubjectCodes->unique();
            
            // Lost credits are subjects the student passed but couldn't be convalidated
            // Only count subjects that were NOT successfully convalidated
            foreach ($passedSubjects as $subjectCode => $subject) {
                if (!$convalidatedSubjectCodes->contains($subjectCode)) {
                    $lostCreditsCount++;
                    // Note: We don't add lost credits to the explanation by default 
                    // as they are not relevant for the technical calculation display
                }
            }
            
            // Calculate new progress in external curriculum
            // Progress = subjects that can be convalidated / total subjects in new curriculum
            $newProgress = ($convalidatedCount / $newTotalSubjects) * 100;
            $progressChange = $newProgress - $originalProgress;
            
            // Generate detailed explanation of percentage change
            $progressExplanation = $this->generateProgressExplanationDetailed(
                $originalProgress, 
                $newProgress, 
                $progressChange, 
                $passedSubjects->count(), 
                $originalTotalSubjects, 
                $convalidatedCount, 
                $newTotalSubjects, 
                $newSubjectsCount,
                $lostCreditsCount,
                $convalidationDetails
            );
            
            return [
                'has_impact' => abs($progressChange) > 0.1,
                'original_progress' => round($originalProgress, 1),
                'new_progress' => round($newProgress, 1),
                'progress_change' => round($progressChange, 1),
                'original_subjects_passed' => $passedSubjects->count(),
                'original_total_subjects' => $originalTotalSubjects,
                'new_total_subjects' => $newTotalSubjects,
                'convalidated_subjects_count' => $convalidatedCount,
                'new_subjects_count' => $newSubjectsCount,
                'lost_credits_count' => $lostCreditsCount,
                'convalidation_details' => $convalidationDetails,
                'progress_explanation' => $progressExplanation
            ];
        } catch (\Exception $e) {
            Log::error('Error in calculateStudentConvalidationImpactCorrect: ' . $e->getMessage());
            Log::error('Student ID: ' . $student->id);
            Log::error('External Curriculum ID: ' . $externalCurriculum->id);
            
            // Return safe default values
            return [
                'has_impact' => false,
                'original_progress' => 0,
                'new_progress' => 0,
                'progress_change' => 0,
                'original_subjects_passed' => 0,
                'original_total_subjects' => 1,
                'new_total_subjects' => 1,
                'convalidated_subjects_count' => 0,
                'new_subjects_count' => 0,
                'lost_credits_count' => 0,
                'convalidation_details' => [],
                'progress_explanation' => 'Error en el cálculo del progreso: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate student convalidation impact with component-based credit limits.
     * Credits that exceed a component's limit are converted to free electives.
     * Credits exceeding the free elective limit are marked as excess and don't count toward progress.
     */
    private function calculateStudentConvalidationImpactWithComponentLimits(
        Student $student,
        $directConvalidations,
        $flexibleConvalidations,
        $notConvalidatedConvalidations,
        $nnGroups,
        $externalCurriculum,
        array $creditLimits,
        $originalSubjects,
        $totalOriginalSubjects
    ) {
        try {
            // Get student's passed subjects
            $passedSubjects = $student->subjects->where('pivot.status', 'passed')->keyBy('code');
            
            // Use the student's actual imported progress percentage (already calculated)
            $originalProgress = $student->progress_percentage ?? 0;
            
            // Get new curriculum size (EXCLUDING leveling subjects for progress calculation)
            $externalSubjects = $externalCurriculum->externalSubjects ?? collect();
            $newTotalSubjects = $externalSubjects->filter(function($subject) {
                // Exclude leveling subjects from total count for progress calculation
                $component = $subject->assigned_component ?? '';
                return $component !== 'leveling';
            })->count();
            $newTotalSubjects = max($newTotalSubjects, 1);
            
            // Initialize credit counters by component
            $componentCredits = [
                'fundamental_required' => 0,
                'fundamental_optional' => 0,
                'professional_required' => 0,
                'professional_optional' => 0,
                'leveling' => 0,
                'thesis' => 0,
                'free_elective' => 0,
            ];
            
            $overflowCredits = []; // Credits that exceed component limits
            $convalidatedCount = 0; // Subjects that count toward progress (excludes leveling)
            $convalidatedCountIncludingLeveling = 0; // Total convalidated including leveling
            $convalidationDetails = [];
            
            // Process direct convalidations
            foreach ($directConvalidations as $convalidation) {
                if (!$convalidation->externalSubject || !$convalidation->internalSubject) {
                    continue;
                }
                
                $externalSubjectCode = $convalidation->externalSubject->code;
                
                // Check if student passed this external subject
                if (!$passedSubjects->has($externalSubjectCode)) {
                    continue;
                }
                
                $internalSubject = $convalidation->internalSubject;
                $credits = $internalSubject->credits ?? 0;
                $type = $internalSubject->type ?? 'fundamental';
                $isRequired = $internalSubject->is_required ?? true;
                $isLeveling = ($type === 'nivelacion') || ($internalSubject->is_leveling ?? false);
                
                // Determine component
                $component = $this->getComponentKey($type, $isRequired, $isLeveling, $internalSubject->code);
                $limit = $this->getComponentLimit($component, $creditLimits);
                
                // Check if adding these credits exceeds the component limit
                if ($limit !== null && $componentCredits[$component] + $credits > $limit) {
                    // Calculate overflow
                    $overflow = ($componentCredits[$component] + $credits) - $limit;
                    $accepted = $credits - $overflow;
                    
                    if ($accepted > 0) {
                        $componentCredits[$component] += $accepted;
                        $convalidatedCountIncludingLeveling++;
                        
                        // Only count toward progress if NOT leveling
                        if ($component !== 'leveling') {
                            $convalidatedCount++;
                        }
                        
                        $convalidationDetails[] = [
                            'type' => 'direct_partial',
                            'subject' => $internalSubject->name,
                            'component' => $component,
                            'credits_accepted' => $accepted,
                            'credits_overflow' => $overflow,
                        ];
                    }
                    
                    // Add overflow to pending overflow list
                    $overflowCredits[] = [
                        'credits' => $overflow,
                        'subject' => $internalSubject->name,
                        'original_component' => $component,
                    ];
                } else {
                    // Fits within limit
                    $componentCredits[$component] += $credits;
                    $convalidatedCountIncludingLeveling++;
                    
                    // Only count toward progress if NOT leveling
                    if ($component !== 'leveling') {
                        $convalidatedCount++;
                    }
                    
                    $convalidationDetails[] = [
                        'type' => 'direct',
                        'subject' => $internalSubject->name,
                        'component' => $component,
                        'credits' => $credits,
                    ];
                }
            }
            
            // Process N:N convalidation groups
            foreach ($nnGroups as $group) {
                if (!$group->externalSubject || !$group->internalSubjects || $group->internalSubjects->isEmpty()) {
                    continue;
                }
                
                $externalSubjectCode = $group->externalSubject->code;
                
                // IMPORTANT: Groups N:N work backwards from direct convalidations
                // The student has OLD subjects (internal) that convalidate to a NEW subject (external)
                // So we DON'T check if student has the external subject - we check if they have the internal subjects
                
                // Get group configuration
                $equivalenceType = $group->equivalence_type; // 'all', 'any', 'credits'
                $equivalencePercentage = $group->equivalence_percentage ?? 100;
                $componentType = $group->component_type;
                
                // Check if student meets the group requirements (has the OLD internal subjects)
                $meetsRequirements = false;
                $matchedSubjects = [];
                $totalGroupCredits = 0;
                $studentMatchedCredits = 0;
                
                foreach ($group->internalSubjects as $internalSubject) {
                    $totalGroupCredits += $internalSubject->credits ?? 0;
                    
                    if ($passedSubjects->has($internalSubject->code)) {
                        $matchedSubjects[] = $internalSubject;
                        $studentMatchedCredits += $internalSubject->credits ?? 0;
                    }
                }
                
                // Determine if requirements are met
                if ($equivalenceType === 'all') {
                    // Student must have ALL internal subjects
                    $meetsRequirements = count($matchedSubjects) === $group->internalSubjects->count();
                } elseif ($equivalenceType === 'any') {
                    // Student must have at least ONE internal subject
                    $meetsRequirements = count($matchedSubjects) > 0;
                } elseif ($equivalenceType === 'credits') {
                    // Student must have percentage of credits
                    $requiredCredits = ($totalGroupCredits * $equivalencePercentage) / 100;
                    $meetsRequirements = $studentMatchedCredits >= $requiredCredits;
                }
                
                if (!$meetsRequirements) {
                    continue;
                }
                
                // Student meets requirements: they have the OLD internal subjects
                // So they GET the NEW external subject in the new curriculum
                // The credit value is simply the external subject's credits
                $credits = $group->externalSubject->credits ?? 0;
                
                // Calculate informational balance (for reporting purposes)
                $oldCreditsUsed = 0;
                foreach ($matchedSubjects as $matchedSubject) {
                    $oldCreditsUsed += $matchedSubject->credits ?? 0;
                }
                $netCredits = $credits - $oldCreditsUsed;
                
                // Map component_type to internal component key
                $componentMap = [
                    'fundamental_required' => 'fundamental_required',
                    'professional_required' => 'professional_required',
                    'optional_fundamental' => 'fundamental_optional',
                    'optional_professional' => 'professional_optional',
                    'free_elective' => 'free_elective',
                    'thesis' => 'thesis',
                    'leveling' => 'leveling',
                ];
                
                $component = $componentMap[$componentType] ?? 'free_elective';
                $limit = $this->getComponentLimit($component, $creditLimits);
                $isLeveling = ($component === 'leveling');
                
                // Check if adding these credits exceeds the component limit
                if ($limit !== null && $componentCredits[$component] + $credits > $limit) {
                    // Calculate overflow
                    $overflow = ($componentCredits[$component] + $credits) - $limit;
                    $accepted = $credits - $overflow;
                    
                    if ($accepted > 0) {
                        $componentCredits[$component] += $accepted;
                        $convalidatedCountIncludingLeveling++;
                        
                        if (!$isLeveling) {
                            $convalidatedCount++;
                        }
                        
                        $convalidationDetails[] = [
                            'type' => 'nn_group_partial',
                            'subject' => $group->group_name,
                            'component' => $component,
                            'credits_accepted' => $accepted,
                            'credits_overflow' => $overflow,
                            'matched_subjects' => implode(', ', array_map(fn($s) => $s->name, $matchedSubjects)),
                            'old_credits_used' => $oldCreditsUsed,
                            'new_credits' => $credits,
                            'net_credits' => $netCredits,
                        ];
                    }
                    
                    // Add overflow to pending overflow list
                    $overflowCredits[] = [
                        'credits' => $overflow,
                        'subject' => $group->group_name,
                        'original_component' => $component,
                    ];
                } else {
                    // Fits within limit
                    $componentCredits[$component] += $credits;
                    $convalidatedCountIncludingLeveling++;
                    
                    if (!$isLeveling) {
                        $convalidatedCount++;
                    }
                    
                    $convalidationDetails[] = [
                        'type' => 'nn_group',
                        'subject' => $group->group_name,
                        'component' => $component,
                        'credits' => $credits,
                        'equivalence_type' => $equivalenceType,
                        'matched_subjects' => implode(', ', array_map(fn($s) => $s->name, $matchedSubjects)),
                        'old_credits_used' => $oldCreditsUsed,
                        'new_credits' => $credits,
                        'net_credits' => $netCredits,
                    ];
                }
            }
            
            // Process overflow credits → convert to free electives
            $freeElectiveLimit = $creditLimits['max_free_elective_credits'] ?? 36;
            $excessCredits = [];
            
            foreach ($overflowCredits as $overflow) {
                if ($componentCredits['free_elective'] + $overflow['credits'] <= $freeElectiveLimit) {
                    // Fits in free elective
                    $componentCredits['free_elective'] += $overflow['credits'];
                    $convalidationDetails[] = [
                        'type' => 'overflow_to_free_elective',
                        'subject' => $overflow['subject'],
                        'original_component' => $overflow['original_component'],
                        'credits' => $overflow['credits'],
                    ];
                } else {
                    // Doesn't fit in free elective → excess (not counted)
                    $excess = $overflow['credits'];
                    if ($componentCredits['free_elective'] < $freeElectiveLimit) {
                        $accepted = $freeElectiveLimit - $componentCredits['free_elective'];
                        $componentCredits['free_elective'] += $accepted;
                        $excess = $overflow['credits'] - $accepted;
                        
                        $convalidationDetails[] = [
                            'type' => 'overflow_to_free_elective_partial',
                            'subject' => $overflow['subject'],
                            'credits_accepted' => $accepted,
                            'credits_excess' => $excess,
                        ];
                    }
                    
                    if ($excess > 0) {
                        $excessCredits[] = [
                            'credits' => $excess,
                            'subject' => $overflow['subject'],
                        ];
                        
                        $convalidationDetails[] = [
                            'type' => 'excess',
                            'subject' => $overflow['subject'],
                            'credits' => $excess,
                            'note' => 'Excedente no contado',
                        ];
                    }
                }
            }
            
            // Process flexible component convalidations (optativas y electivas)
            foreach ($flexibleConvalidations as $convalidation) {
                if (!$convalidation->externalSubject) {
                    continue;
                }
                
                $externalSubjectCode = $convalidation->externalSubject->code;
                
                if (!$passedSubjects->has($externalSubjectCode)) {
                    continue;
                }
                
                $credits = $convalidation->externalSubject->credits ?? 3;
                $componentType = $convalidation->component_type; // professional_optional, fundamental_optional, free_elective
                
                // Map component_type to internal component key
                $componentMap = [
                    'fundamental_optional' => 'fundamental_optional',
                    'professional_optional' => 'professional_optional',
                    'free_elective' => 'free_elective',
                ];
                
                $component = $componentMap[$componentType] ?? 'free_elective';
                $limit = $this->getComponentLimit($component, $creditLimits);
                
                // Check if fits within component limit
                if ($limit !== null && $componentCredits[$component] + $credits > $limit) {
                    // Partial or overflow
                    $overflow = ($componentCredits[$component] + $credits) - $limit;
                    $accepted = $credits - $overflow;
                    
                    if ($accepted > 0) {
                        $componentCredits[$component] += $accepted;
                        $convalidatedCountIncludingLeveling++;
                        $convalidatedCount++;
                        
                        $convalidationDetails[] = [
                            'type' => 'flexible_partial',
                            'subject' => $convalidation->externalSubject->name,
                            'component' => $component,
                            'credits_accepted' => $accepted,
                            'credits_overflow' => $overflow,
                        ];
                    }
                    
                    // Add overflow to pending overflow list
                    $overflowCredits[] = [
                        'credits' => $overflow,
                        'subject' => $convalidation->externalSubject->name,
                        'original_component' => $component,
                    ];
                } else {
                    // Fits within limit
                    $componentCredits[$component] += $credits;
                    $convalidatedCountIncludingLeveling++;
                    $convalidatedCount++;
                    
                    $convalidationDetails[] = [
                        'type' => 'flexible',
                        'subject' => $convalidation->externalSubject->name,
                        'component' => $component,
                        'credits' => $credits,
                    ];
                }
            }
            
            // Calculate total valid credits
            $totalValidCredits = array_sum($componentCredits);
            $totalExcessCredits = array_sum(array_column($excessCredits, 'credits'));
            
            // Calculate new progress based on CREDITS (not subject count)
            // We need to account for:
            // 1. Student's original approved credits
            // 2. Credits from their academic history that were convalidated
            // 3. Credits NOT in their history (homologations, external credits, etc.)
            
            $studentApprovedCredits = $student->approved_credits ?? 0;
            $originalTotalCredits = ($studentApprovedCredits > 0 && $originalProgress > 0) 
                ? ($studentApprovedCredits * 100) / $originalProgress 
                : 167; // Default fallback (plan viejo)
            
            // Get NEW curriculum total credits (excluding leveling) for percentage calculation
            $newCurriculumCredits = $externalCurriculum->getCreditsByComponent();
            $newTotalCredits = 0;
            foreach ($newCurriculumCredits as $component => $credits) {
                if ($component !== 'leveling' && $component !== 'pending') {
                    $newTotalCredits += $credits;
                }
            }
            $newTotalCredits = max($newTotalCredits, 1); // Avoid division by zero
            
            // Calculate credits from passed subjects (subjects in their history)
            $creditsFromHistory = $passedSubjects->sum('credits');
            
            // Calculate credits NOT in history (difference between approved_credits and sum of subjects)
            // These are likely: optativas, electivas, libre elección, homologaciones, etc.
            $creditsNotInHistory = max(0, $studentApprovedCredits - $creditsFromHistory);
            
            // Automatically distribute credits NOT in history to flexible components
            // Priority: professional_optional → fundamental_optional → free_elective → excess
            if ($creditsNotInHistory > 0) {
                $remainingCredits = $creditsNotInHistory;
                
                // Try to add to professional_optional (disciplinares optativas)
                $professionalOptionalLimit = $this->getComponentLimit('professional_optional', $creditLimits);
                if ($professionalOptionalLimit !== null && $remainingCredits > 0) {
                    $available = $professionalOptionalLimit - $componentCredits['professional_optional'];
                    if ($available > 0) {
                        $toAdd = min($remainingCredits, $available);
                        $componentCredits['professional_optional'] += $toAdd;
                        $remainingCredits -= $toAdd;
                        $convalidatedCount += 1;
                        
                        $convalidationDetails[] = [
                            'type' => 'auto_flexible',
                            'subject' => 'Créditos optativas/electivas no en historial',
                            'component' => 'professional_optional',
                            'credits' => $toAdd,
                            'note' => 'Auto-convalidación de créditos flexibles'
                        ];
                    }
                }
                
                // Try to add to fundamental_optional (fundamentales optativas)
                $fundamentalOptionalLimit = $this->getComponentLimit('fundamental_optional', $creditLimits);
                if ($fundamentalOptionalLimit !== null && $remainingCredits > 0) {
                    $available = $fundamentalOptionalLimit - $componentCredits['fundamental_optional'];
                    if ($available > 0) {
                        $toAdd = min($remainingCredits, $available);
                        $componentCredits['fundamental_optional'] += $toAdd;
                        $remainingCredits -= $toAdd;
                        $convalidatedCount += 1;
                        
                        $convalidationDetails[] = [
                            'type' => 'auto_flexible',
                            'subject' => 'Créditos optativas/electivas no en historial',
                            'component' => 'fundamental_optional',
                            'credits' => $toAdd,
                            'note' => 'Auto-convalidación de créditos flexibles'
                        ];
                    }
                }
                
                // Try to add to free_elective (libre elección)
                $freeElectiveLimit = $creditLimits['max_free_elective_credits'] ?? 36;
                if ($remainingCredits > 0) {
                    $available = $freeElectiveLimit - $componentCredits['free_elective'];
                    if ($available > 0) {
                        $toAdd = min($remainingCredits, $available);
                        $componentCredits['free_elective'] += $toAdd;
                        $remainingCredits -= $toAdd;
                        $convalidatedCount += 1;
                        
                        $convalidationDetails[] = [
                            'type' => 'auto_flexible',
                            'subject' => 'Créditos de libre elección no en historial',
                            'component' => 'free_elective',
                            'credits' => $toAdd,
                            'note' => 'Auto-convalidación de créditos flexibles'
                        ];
                    }
                }
                
                // Remaining credits become excess (don't count toward progress)
                if ($remainingCredits > 0) {
                    $excessCredits[] = [
                        'credits' => $remainingCredits,
                        'subject' => 'Créditos excedentes no en historial',
                        'note' => 'Exceden los límites de componentes flexibles'
                    ];
                    
                    $convalidationDetails[] = [
                        'type' => 'excess',
                        'subject' => 'Créditos excedentes no en historial',
                        'credits' => $remainingCredits,
                        'note' => 'No cuentan para avance (exceden límites)'
                    ];
                }
            }
            
            // Recalculate total valid credits after adding credits not in history
            $totalValidCredits = array_sum($componentCredits);
            $totalExcessCredits = array_sum(array_column($excessCredits, 'credits'));
            
            // Effective credits = total valid credits (already includes auto-distributed credits)
            $effectiveCredits = $totalValidCredits;
            
            // Calculate percentage based on NEW curriculum total credits (sin nivelación)
            $newProgress = ($effectiveCredits / $newTotalCredits) * 100;
            $progressChange = $newProgress - $originalProgress;
            
            // Count new subjects required
            $newSubjectsCount = 0;
            foreach ($notConvalidatedConvalidations as $convalidation) {
                if ($convalidation->externalSubject) {
                    $newSubjectsCount++;
                }
            }
            
            return [
                'has_impact' => abs($progressChange) > 0.1,
                'original_progress' => round($originalProgress, 1),
                'new_progress' => round($newProgress, 1),
                'progress_change' => round($progressChange, 1),
                'original_subjects_passed' => $passedSubjects->count(),
                'original_total_subjects' => $totalOriginalSubjects,
                'new_total_subjects' => $newTotalSubjects,
                'convalidated_subjects_count' => $convalidatedCount,
                'new_subjects_count' => $newSubjectsCount,
                'component_credits' => $componentCredits,
                'total_valid_credits' => $totalValidCredits,
                'total_excess_credits' => $totalExcessCredits,
                'excess_details' => $excessCredits,
                'convalidation_details' => $convalidationDetails,
                'progress_explanation' => $this->generateProgressExplanationWithComponents(
                    $originalProgress,
                    $newProgress,
                    $progressChange,
                    $componentCredits,
                    $totalValidCredits,
                    $totalExcessCredits
                ),
            ];
        } catch (\Exception $e) {
            Log::error('Error in calculateStudentConvalidationImpactWithComponentLimits: ' . $e->getMessage());
            Log::error('Student ID: ' . $student->id);
            
            return [
                'has_impact' => false,
                'original_progress' => 0,
                'new_progress' => 0,
                'progress_change' => 0,
                'original_subjects_passed' => 0,
                'original_total_subjects' => 1,
                'new_total_subjects' => 1,
                'convalidated_subjects_count' => 0,
                'new_subjects_count' => 0,
                'component_credits' => [],
                'total_valid_credits' => 0,
                'total_excess_credits' => 0,
                'excess_details' => [],
                'convalidation_details' => [],
                'progress_explanation' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get component key for a subject
     */
    private function getComponentKey(string $type, bool $isRequired, bool $isLeveling, string $code): string
    {
        // Nivelación
        if ($isLeveling || $type === 'nivelacion') {
            return 'leveling';
        }
        
        // Trabajo de grado
        if ($code === '4100573' || $type === 'trabajo_grado') {
            return 'thesis';
        }
        
        // Fundamental
        if ($type === 'fundamental' && $isRequired) {
            return 'fundamental_required';
        }
        if ($type === 'fundamental' && !$isRequired) {
            return 'fundamental_optional';
        }
        if ($type === 'optativa_fundamentacion') {
            return 'fundamental_optional';
        }
        
        // Disciplinar (profesional)
        if ($type === 'profesional' && $isRequired) {
            return 'professional_required';
        }
        if ($type === 'profesional' && !$isRequired) {
            return 'professional_optional';
        }
        if ($type === 'optativa_profesional') {
            return 'professional_optional';
        }
        
        // Libre elección
        if ($type === 'libre_eleccion') {
            return 'free_elective';
        }
        
        // Default: treat as required fundamental
        return 'fundamental_required';
    }

    /**
     * Get credit limit for a component
     */
    private function getComponentLimit(string $component, array $creditLimits): ?int
    {
        $map = [
            'fundamental_required' => 'max_fundamental_required',
            'fundamental_optional' => 'max_optional_fundamental',
            'professional_required' => 'max_professional_required',
            'professional_optional' => 'max_optional_professional',
            'leveling' => 'max_leveling',
            'thesis' => 'max_thesis',
            'free_elective' => 'max_free_elective',
        ];
        
        $key = $map[$component] ?? null;
        if (!$key) {
            return null;
        }
        
        $limit = $creditLimits[$key] ?? null;
        
        // null means no limit (or 0 means no limit for that component)
        return $limit !== null && $limit !== '' && $limit > 0 ? (int)$limit : null;
    }

    /**
     * Generate progress explanation with component breakdown
     */
    private function generateProgressExplanationWithComponents(
        float $originalProgress,
        float $newProgress,
        float $progressChange,
        array $componentCredits,
        int $totalValidCredits,
        int $totalExcessCredits
    ): string {
        $explanation = sprintf(
            "Progreso original: %.1f%% → Nuevo progreso: %.1f%% (cambio: %+.1f%%). ",
            $originalProgress,
            $newProgress,
            $progressChange
        );
        
        $explanation .= sprintf("Total de créditos válidos: %d. ", $totalValidCredits);
        
        if ($totalExcessCredits > 0) {
            $explanation .= sprintf("Créditos excedentes no contados: %d. ", $totalExcessCredits);
        }
        
        // Component breakdown
        $componentNames = [
            'fundamental_required' => 'Fund. Obligatorio',
            'fundamental_optional' => 'Fund. Optativo',
            'professional_required' => 'Disc. Obligatorio',
            'professional_optional' => 'Disc. Optativo',
            'leveling' => 'Nivelación',
            'thesis' => 'Trabajo de Grado',
            'free_elective' => 'Libre Elección',
        ];
        
        $breakdown = [];
        foreach ($componentCredits as $component => $credits) {
            if ($credits > 0) {
                $breakdown[] = sprintf("%s: %d", $componentNames[$component] ?? $component, $credits);
            }
        }
        
        if (!empty($breakdown)) {
            $explanation .= "Desglose: " . implode(", ", $breakdown) . ".";
        }
        
        return $explanation;
    }
    
    /**
     * Calculate the impact of convalidations on a specific student with credit limits (OLD VERSION - DEPRECATED)
     */
    private function calculateStudentConvalidationImpactWithLimits(
        Student $student, 
        $directConvalidations, 
        $selectedFreeElectives, 
        $originalSubjects, 
        $totalOriginalSubjects
    ) {
        $passedSubjects = $student->subjects->keyBy('code');
        
        // Calculate original progress
        $originalProgress = ($passedSubjects->count() / $totalOriginalSubjects) * 100;
        
        // Apply direct convalidations
        $convalidatedSubjects = [];
        $directConvalidationsApplied = [];
        $additionalPassedCount = 0;
        
        foreach ($directConvalidations as $convalidation) {
            if ($convalidation->internalSubject) {
                $internalSubjectCode = $convalidation->internalSubject->code;
                
                // If student hasn't passed this subject, they benefit from convalidation
                if (!isset($passedSubjects[$internalSubjectCode])) {
                    $convalidatedSubjects[] = $internalSubjectCode;
                    $directConvalidationsApplied[] = $convalidation->externalSubject->code;
                    $additionalPassedCount++;
                }
            }
        }

        // Apply selected free electives (these don't map to specific internal subjects)
        $freeElectivesApplied = [];
        foreach ($selectedFreeElectives as $convalidation) {
            $freeElectivesApplied[] = $convalidation->externalSubject->code;
            // Free electives contribute to progress but don't map to specific subjects
            $additionalPassedCount++;
        }
        
        // Calculate new progress with convalidations
        $newPassedCount = $passedSubjects->count() + $additionalPassedCount;
        $newProgress = ($newPassedCount / $totalOriginalSubjects) * 100;
        $progressChange = $newProgress - $originalProgress;
        
        return [
            'has_impact' => $additionalPassedCount > 0,
            'original_progress' => $originalProgress,
            'new_progress' => $newProgress,
            'progress_change' => $progressChange,
            'convalidated_subjects_count' => $additionalPassedCount,
            'convalidated_subjects' => array_merge($convalidatedSubjects, $freeElectivesApplied),
            'direct_convalidations' => $directConvalidationsApplied,
            'free_electives' => $freeElectivesApplied
        ];
    }

    /**
     * Test endpoint to verify CSRF and basic functionality
     */
    public function testEndpoint(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint funciona correctamente',
            'csrf_token' => $request->header('X-CSRF-TOKEN'),
            'method' => $request->method(),
            'data' => $request->all()
        ]);
    }

    /**
     * Check the status of a convalidation (complete or incomplete)
     * Returns details about the convalidation progress
     */
    public function checkConvalidationStatus(Request $request)
    {
        try {
            $curriculumId = $request->input('curriculum_id');
            
            if (!$curriculumId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere curriculum_id'
                ], 400);
            }
            
            $curriculum = ExternalCurriculum::find($curriculumId);
            
            if (!$curriculum) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'message' => 'La convalidación no existe'
                ]);
            }
            
            // Get total subjects (excluding removed)
            $totalSubjects = $curriculum->externalSubjects()
                ->where(function($query) {
                    $query->whereNull('change_type')
                          ->orWhere('change_type', '!=', 'removed');
                })
                ->count();
            
            // Get subjects with direct convalidation
            $directConvalidations = $curriculum->convalidations()
                ->whereHas('externalSubject', function($query) {
                    $query->where(function($q) {
                        $q->whereNull('change_type')
                          ->orWhere('change_type', '!=', 'removed');
                    });
                })
                ->count();
            
            // Get subjects with N:N group convalidation
            $nnGroupConvalidations = $curriculum->convalidationGroups()
                ->whereHas('externalSubject', function($query) {
                    $query->where(function($q) {
                        $q->whereNull('change_type')
                          ->orWhere('change_type', '!=', 'removed');
                    });
                })
                ->count();
            
            // Total convalidated
            $convalidatedSubjects = $directConvalidations + $nnGroupConvalidations;
            
            // Subjects pending convalidation
            $pendingSubjects = $totalSubjects - $convalidatedSubjects;
            
            // Is complete when all subjects have been convalidated
            $isComplete = $pendingSubjects === 0 && $totalSubjects > 0;
            
            // Calculate percentage
            $completionPercentage = $totalSubjects > 0 
                ? round(($convalidatedSubjects / $totalSubjects) * 100, 1) 
                : 0;
            
            return response()->json([
                'success' => true,
                'exists' => true,
                'curriculum_id' => $curriculum->id,
                'curriculum_name' => $curriculum->name,
                'is_complete' => $isComplete,
                'total_subjects' => $totalSubjects,
                'convalidated_subjects' => $convalidatedSubjects,
                'pending_subjects' => $pendingSubjects,
                'completion_percentage' => $completionPercentage,
                'redirect_url' => route('convalidation.show', $curriculum->id),
                'created_at' => $curriculum->created_at->toISOString(),
                'updated_at' => $curriculum->updated_at->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error checking convalidation status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Classify the impact type based on how many students benefit from a convalidation
     */
    private function classifyImpactType(int $studentsBenefited, int $totalStudents): string
    {
        if ($totalStudents == 0) {
            return 'none';
        }

        $benefitPercentage = ($studentsBenefited / $totalStudents) * 100;

        if ($benefitPercentage >= 75) {
            return 'high';
        } elseif ($benefitPercentage >= 50) {
            return 'medium';
        } elseif ($benefitPercentage >= 25) {
            return 'low';
        } elseif ($benefitPercentage > 0) {
            return 'minimal';
        } else {
            return 'none';
        }
    }

    /**
     * Save a modified curriculum from simulation as a new external curriculum for convalidation
     */
    public function saveModifiedCurriculum(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'curriculum' => 'required|array',
                'changes' => 'array',
                'institution' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $curriculumData = $request->input('curriculum');
            $changes = $request->input('changes', []);
            $name = $request->input('name');
            $institution = $request->input('institution', 'Simulación Curricular');

            // DEBUG: Log incoming data
            Log::info('=== SAVE MODIFIED CURRICULUM DEBUG ===', [
                'name' => $name,
                'total_semesters' => count($curriculumData),
                'changes_count' => count($changes),
                'changes_summary' => collect($changes)->groupBy('type')->map->count(),
                'sample_change' => !empty($changes) ? $changes[0] : null,
                'changes_sample' => array_slice($changes, 0, 3)
            ]);

            // Build a map of changes by subject code for easy lookup
            // simulationChanges structure: [{subject_code, type, new_value, old_value, ...}]
            $changesMap = collect($changes)->keyBy('subject_code');

            // Calculate total subjects
            $totalSubjects = 0;
            foreach ($curriculumData as $semester => $subjects) {
                $totalSubjects += count($subjects);
            }

            // Generate a unique name if one with the same name already exists
            $baseName = $name;
            $counter = 1;
            while (ExternalCurriculum::where('name', $name)->where('institution', $institution)->exists()) {
                $name = $baseName . ' (' . $counter . ')';
                $counter++;
            }

            Log::info('Creating new external curriculum', [
                'name' => $name,
                'institution' => $institution,
                'original_name' => $baseName
            ]);

            // Always create a new external curriculum for each simulation save
            $externalCurriculum = ExternalCurriculum::create([
                'name' => $name,
                'institution' => $institution,
                'description' => 'Malla curricular modificada desde simulación. Total de cambios realizados: ' . count($changes) . '. Total de materias: ' . $totalSubjects,
                'uploaded_file' => null, // No file uploaded, created from simulation
                'metadata' => [
                    'source' => 'simulation',
                    'total_subjects' => $totalSubjects,
                    'changes_count' => count($changes),
                    'changes' => $changes,
                    'created_at' => now()->toISOString()
                ],
                'status' => 'active'
            ]);

            // Process curriculum data and create external subjects
            Log::info('Starting to process curriculum subjects...');
            $processedCount = 0;
            $changeTypeCounts = ['added' => 0, 'removed' => 0, 'modified' => 0, 'moved' => 0, 'unchanged' => 0];
            $processedCodes = []; // Track already processed subject codes to avoid duplicates
            
            foreach ($curriculumData as $semester => $subjects) {
                Log::info("Processing semester {$semester} with " . count($subjects) . " subjects");
                
                foreach ($subjects as $displayOrder => $subjectData) {
                    $subjectCode = $subjectData['code'];
                    
                    // Skip ghost cards / duplicates (shouldn't happen with frontend fix, but just in case)
                    if (in_array($subjectCode, $processedCodes)) {
                        Log::info("Skipping duplicate subject: {$subjectCode} in semester {$semester}");
                        continue;
                    }
                    
                    // Skip subjects marked as removed
                    if (!empty($subjectData['isRemoved'])) {
                        Log::info("Skipping removed subject: {$subjectCode}");
                        $changeTypeCounts['removed']++;
                        continue;
                    }
                    
                    $processedCodes[] = $subjectCode;
                    $processedCount++;
                
                    // Sample first 3 subjects for detailed logging
                    if ($processedCount <= 3) {
                        Log::info("Sample subject data:", [
                            'code' => $subjectCode,
                            'name' => $subjectData['name'],
                            'semester' => $semester,
                            'isRemoved' => $subjectData['isRemoved'] ?? 'not set',
                            'isAdded' => $subjectData['isAdded'] ?? 'not set',
                            'all_keys' => array_keys($subjectData)
                        ]);
                    }
                
                    // Determine change type based on multiple sources
                    $changeType = 'unchanged';
                    $originalSemester = null;
                    $changeDetails = [];
                
                    // Check if subject is marked as added in the frontend
                    if (isset($subjectData['isAdded']) && $subjectData['isAdded']) {
                        $changeType = 'added';
                    }
                    // Check simulationChanges array for this subject
                    else {
                        $changeInfo = $changesMap->get($subjectCode);
                        if ($changeInfo) {
                            $infoType = $changeInfo['type'] ?? '';
                            
                            // Map simulation change types
                            if ($infoType === 'added') {
                                $changeType = 'added';
                            } elseif ($infoType === 'removed') {
                                $changeType = 'removed';
                            } elseif ($infoType === 'semester') {
                                $changeType = 'moved';
                                $originalSemester = $changeInfo['old_value'] ?? null;
                            } elseif ($infoType === 'prerequisites' || $infoType === 'edit') {
                                $changeType = 'modified';
                            }
                        }
                    }
                    
                    // DEBUG: Log change type detection
                    Log::info("Processing subject: {$subjectCode} - {$subjectData['name']}", [
                        'semester' => $semester,
                        'isRemoved_flag' => $subjectData['isRemoved'] ?? false,
                        'isAdded_flag' => $subjectData['isAdded'] ?? false,
                        'has_change_in_map' => $changesMap->has($subjectCode),
                        'detected_changeType' => $changeType
                    ]);
                    
                    // Build change details based on type
                    switch ($changeType) {
                        case 'added':
                            $changeDetails = [
                                'added_reason' => 'Nueva materia agregada en simulación',
                                'added_at' => now()->toISOString()
                            ];
                            break;

                        case 'removed':
                            $changeDetails = [
                                'removed_reason' => 'Materia eliminada en simulación',
                                'removed_at' => now()->toISOString()
                            ];
                            break;

                        case 'moved':
                            $changeDetails = [
                                'old_semester' => $originalSemester,
                                'new_semester' => $semester,
                                'moved_at' => now()->toISOString()
                            ];
                            break;

                        case 'modified':
                            $changeInfo = $changesMap->get($subjectCode);
                            $changeDetails = [
                                'modifications' => $changeInfo['new_value'] ?? [],
                                'old_value' => $changeInfo['old_value'] ?? null,
                                'modified_at' => now()->toISOString()
                            ];
                            break;
                    }

                    // Get credits from actual subject data in the database if available
                    $credits = $subjectData['credits'] ?? 3;
                    
                    // Try to get credits from the original subject if not in subjectData
                    if (!isset($subjectData['credits']) || $credits === 3) {
                        $originalSubject = Subject::where('code', $subjectCode)->first();
                        if ($originalSubject) {
                            $credits = $originalSubject->credits;
                        }
                    }

                    // Prepare additional_data with comprehensive information
                    $additionalData = [
                        'prerequisites' => $subjectData['prerequisites'] ?? [],
                        'is_added_in_simulation' => ($changeType === 'added'),
                        'is_removed_in_simulation' => ($changeType === 'removed'),
                        'is_modified_in_simulation' => ($changeType === 'modified'),
                        'is_moved_in_simulation' => ($changeType === 'moved'),
                        'original_description' => $subjectData['description'] ?? null,
                        'source' => 'simulation',
                        'classroom_hours' => $subjectData['classroom_hours'] ?? null,
                        'student_hours' => $subjectData['student_hours'] ?? null,
                        'type' => $subjectData['type'] ?? null
                    ];

                    // Create the external subject (curriculum is always new, so no duplicates)
                    ExternalSubject::create([
                        'external_curriculum_id' => $externalCurriculum->id,
                        'code' => $subjectCode,
                        'name' => $subjectData['name'],
                        'semester' => (int) $semester,
                        'display_order' => (int) $displayOrder,
                        'credits' => $credits,
                        'description' => $subjectData['description'] ?? $subjectData['name'],
                        'additional_data' => $additionalData,
                        'change_type' => $changeType !== 'unchanged' ? $changeType : null,
                        'original_semester' => $originalSemester,
                        'change_details' => !empty($changeDetails) ? $changeDetails : null
                    ]);
                    
                    // DEBUG: Log what was actually saved
                    if ($changeType === 'removed' || $changeType === 'added') {
                        Log::info("Saved subject with change_type:", [
                            'code' => $subjectCode,
                            'change_type_variable' => $changeType,
                            'change_type_saved' => ($changeType !== 'unchanged' ? $changeType : null)
                        ]);
                    }
                    
                    // Count change types
                    $changeTypeCounts[$changeType]++;
                }
            }
            
            Log::info('Finished processing curriculum', [
                'total_processed' => $processedCount,
                'change_type_counts' => $changeTypeCounts
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Malla curricular guardada exitosamente para convalidación',
                'curriculum_id' => $externalCurriculum->id,
                'redirect_url' => route('convalidation.show', $externalCurriculum->id)
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving modified curriculum: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al guardar la malla curricular',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate detailed explanation of why the progress percentage changed
     */
    private function generateProgressExplanationDetailed(
        float $originalProgress, 
        float $newProgress, 
        float $progressChange, 
        int $originalSubjectsPassed, 
        int $originalTotalSubjects, 
        float $convalidatedCount, 
        int $newTotalSubjects, 
        int $newSubjectsCount,
        int $lostCreditsCount,
        array $convalidationDetails
    ): string {
        $explanation = [];
        
        // Direct technical calculation
        $explanation[] = "CURRICULUM PROGRESS CALCULATION";
        $explanation[] = "";
        $explanation[] = "Original Curriculum:";
        $explanation[] = "Passed subjects: {$originalSubjectsPassed}";
        $explanation[] = "Total subjects: {$originalTotalSubjects}";
        $explanation[] = "Progress: " . round($originalProgress, 1) . "% ({$originalSubjectsPassed}/{$originalTotalSubjects})";
        $explanation[] = "";
        
        $explanation[] = "New Curriculum:";
        $explanation[] = "Total subjects: {$newTotalSubjects}";
        $explanation[] = "Convalidated subjects: {$convalidatedCount}";
        $explanation[] = "Progress: " . round($newProgress, 1) . "% ({$convalidatedCount}/{$newTotalSubjects})";
        $explanation[] = "";
        
        // Mathematical analysis of the change
        $explanation[] = "CHANGE ANALYSIS:";
        if ($progressChange > 0.1) {
            $explanation[] = "Incremento: " . round(abs($progressChange), 1) . " puntos porcentuales";
            
            if ($newTotalSubjects < $originalTotalSubjects) {
                $diff = $originalTotalSubjects - $newTotalSubjects;
                $explanation[] = "Causa: Reducción de {$diff} materias en nueva malla";
                $explanation[] = "Cálculo: {$convalidatedCount}/{$newTotalSubjects} > {$originalSubjectsPassed}/{$originalTotalSubjects}";
            } elseif ($convalidatedCount > $originalSubjectsPassed) {
                $diff = $convalidatedCount - $originalSubjectsPassed;
                $explanation[] = "Causa: {$diff} materias adicionales convalidadas";
            }
            
        } elseif ($progressChange < -0.1) {
            $explanation[] = "Disminución: " . round(abs($progressChange), 1) . " puntos porcentuales";
            
            if ($newTotalSubjects > $originalTotalSubjects) {
                $diff = $newTotalSubjects - $originalTotalSubjects;
                $explanation[] = "Causa: Incremento de {$diff} materias en nueva malla";
                $explanation[] = "Cálculo: {$convalidatedCount}/{$newTotalSubjects} < {$originalSubjectsPassed}/{$originalTotalSubjects}";
            } elseif ($convalidatedCount < $originalSubjectsPassed) {
                $diff = $originalSubjectsPassed - $convalidatedCount;
                $explanation[] = "Causa: {$diff} materias no pudieron convalidarse";
            }
            
        } else {
            $explanation[] = "Sin cambio significativo (< 0.1 puntos)";
            $explanation[] = "Cálculo: {$convalidatedCount}/{$newTotalSubjects} ≈ {$originalSubjectsPassed}/{$originalTotalSubjects}";
        }
        
        // Solo mostrar materias convalidadas exitosamente
        $directConvalidations = [];
        $freeElectiveConvalidations = [];
        
        foreach ($convalidationDetails as $detail) {
            if ($detail['type'] === 'direct') {
                $directConvalidations[] = $detail['student_subject'];
            } elseif ($detail['type'] === 'free_elective') {
                $freeElectiveConvalidations[] = $detail['student_subject'];
            }
        }
        
        if (!empty($directConvalidations) || !empty($freeElectiveConvalidations)) {
            $explanation[] = "";
            $explanation[] = "MATERIAS CONVALIDADAS:";
            
            if (!empty($directConvalidations)) {
                $explanation[] = "";
                $explanation[] = "Convalidaciones directas (" . count($directConvalidations) . "):";
                foreach ($directConvalidations as $subject) {
                    $explanation[] = "• {$subject}";
                }
            }
            
            if (!empty($freeElectiveConvalidations)) {
                $explanation[] = "";
                $explanation[] = "Libre elección (" . count($freeElectiveConvalidations) . "):";
                foreach ($freeElectiveConvalidations as $subject) {
                    $explanation[] = "• {$subject}";
                }
            }
        }
        
        return implode("\n", $explanation);
    }

    /**
     * Debug method to check why convalidations are not matching
     * TEMPORAL - REMOVE IN PRODUCTION
     */
    public function debugConvalidationMatching(ExternalCurriculum $externalCurriculum)
    {
        try {
            // Get a sample student (first one available)
            $student = Student::with('subjects')->first();
            
            if (!$student) {
                return response()->json(['error' => 'No student found']);
            }
            
            // Get student's passed subjects
            $passedSubjects = $student->subjects->where('pivot.status', 'passed');
            
            // Get ALL configured convalidations
            $directConvalidations = SubjectConvalidation::where('external_curriculum_id', $externalCurriculum->id)
                ->where('convalidation_type', 'direct')
                ->with(['internalSubject', 'externalSubject'])
                ->get();
                
            $freeElectiveConvalidations = SubjectConvalidation::where('external_curriculum_id', $externalCurriculum->id)
                ->where('convalidation_type', 'free_elective')
                ->with(['internalSubject', 'externalSubject'])
                ->get();
            
            // Prepare debug data
            $debugData = [
                'curriculum_info' => [
                    'id' => $externalCurriculum->id,
                    'name' => $externalCurriculum->name,
                    'institution' => $externalCurriculum->institution
                ],
                'student_info' => [
                    'id' => $student->id,
                    'document' => $student->document,
                    'passed_subjects_count' => $passedSubjects->count()
                ],
                'passed_subjects' => $passedSubjects->map(function($subject) {
                    return [
                        'id' => $subject->id,
                        'code' => $subject->code,
                        'name' => $subject->name
                    ];
                })->values()->toArray(),
                'convalidations_info' => [
                    'direct_count' => $directConvalidations->count(),
                    'free_elective_count' => $freeElectiveConvalidations->count(),
                    'total_count' => $directConvalidations->count() + $freeElectiveConvalidations->count()
                ],
                'direct_convalidations' => $directConvalidations->map(function($conv) {
                    return [
                        'id' => $conv->id,
                        'internal_subject' => $conv->internalSubject ? [
                            'id' => $conv->internalSubject->id,
                            'code' => $conv->internalSubject->code,
                            'name' => $conv->internalSubject->name
                        ] : null,
                        'external_subject' => $conv->externalSubject ? [
                            'id' => $conv->externalSubject->id,
                            'code' => $conv->externalSubject->code,
                            'name' => $conv->externalSubject->name
                        ] : null,
                    ];
                })->values()->toArray(),
                'free_elective_convalidations' => $freeElectiveConvalidations->map(function($conv) {
                    return [
                        'id' => $conv->id,
                        'external_subject' => $conv->externalSubject ? [
                            'id' => $conv->externalSubject->id,
                            'code' => $conv->externalSubject->code,
                            'name' => $conv->externalSubject->name
                        ] : null,
                    ];
                })->values()->toArray(),
                'matching_analysis' => [],
                'problem_diagnosis' => []
            ];
            
            // CORRECTED LOGIC: Check if student has subjects matching EXTERNAL subjects in convalidations
            $directMatches = 0;
            $freeElectiveMatches = 0;
            
            // Check direct convalidations
            foreach ($directConvalidations as $convalidation) {
                if ($convalidation->externalSubject) {
                    $externalCode = $convalidation->externalSubject->code;
                    $matchingSubject = $passedSubjects->where('code', $externalCode)->first();
                    
                    if ($matchingSubject) {
                        $directMatches++;
                        $debugData['matching_analysis'][] = [
                            'type' => 'direct',
                            'match_found' => true,
                            'student_subject' => [
                                'code' => $matchingSubject->code,
                                'name' => $matchingSubject->name
                            ],
                            'external_subject' => [
                                'code' => $convalidation->externalSubject->code,
                                'name' => $convalidation->externalSubject->name
                            ],
                            'maps_to_internal' => $convalidation->internalSubject ? [
                                'code' => $convalidation->internalSubject->code,
                                'name' => $convalidation->internalSubject->name
                            ] : null
                        ];
                    } else {
                        $debugData['matching_analysis'][] = [
                            'type' => 'direct',
                            'match_found' => false,
                            'external_subject' => [
                                'code' => $convalidation->externalSubject->code,
                                'name' => $convalidation->externalSubject->name
                            ],
                            'reason' => 'Student did not pass this external subject'
                        ];
                    }
                }
            }
            
            // Check free elective convalidations
            foreach ($freeElectiveConvalidations as $convalidation) {
                if ($convalidation->externalSubject) {
                    $externalCode = $convalidation->externalSubject->code;
                    $matchingSubject = $passedSubjects->where('code', $externalCode)->first();
                    
                    if ($matchingSubject) {
                        $freeElectiveMatches++;
                        $debugData['matching_analysis'][] = [
                            'type' => 'free_elective',
                            'match_found' => true,
                            'student_subject' => [
                                'code' => $matchingSubject->code,
                                'name' => $matchingSubject->name
                            ],
                            'external_subject' => [
                                'code' => $convalidation->externalSubject->code,
                                'name' => $convalidation->externalSubject->name
                            ]
                        ];
                    }
                }
            }
            
            $debugData['results'] = [
                'direct_matches' => $directMatches,
                'free_elective_matches' => $freeElectiveMatches,
                'total_matches' => $directMatches + $freeElectiveMatches
            ];
            
            // Diagnosis
            if ($directMatches === 0 && $freeElectiveMatches === 0) {
                if ($directConvalidations->count() === 0 && $freeElectiveConvalidations->count() === 0) {
                    $debugData['problem_diagnosis'][] = "NO HAY CONVALIDACIONES CONFIGURADAS para esta malla externa";
                } else {
                    $debugData['problem_diagnosis'][] = "El estudiante NO CURSÓ ninguna de las materias externas que están en las convalidaciones";
                    $debugData['problem_diagnosis'][] = "Esto es normal si el estudiante está en la malla original y la malla externa es diferente";
                    $debugData['problem_diagnosis'][] = "Las convalidaciones solo aplican si el estudiante cursó materias de la malla EXTERNA";
                }
            } else {
                $debugData['problem_diagnosis'][] = "Se encontraron {$directMatches} convalidaciones directas y {$freeElectiveMatches} de libre elección";
            }
            
            return response()->json($debugData, 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Show the component assignment view for an external curriculum.
     */
    public function assignComponents($curriculumId)
    {
        $curriculum = ExternalCurriculum::with('externalSubjects.assignedComponent')
            ->findOrFail($curriculumId);

        // Get available component types
        $componentTypes = [
            'fundamental_required' => 'Fundamental Obligatoria',
            'professional_required' => 'Profesional Obligatoria',
            'optional_fundamental' => 'Optativa Fundamental',
            'optional_professional' => 'Optativa Profesional',
            'free_elective' => 'Libre Elección',
            'thesis' => 'Trabajo de Grado',
            'leveling' => 'Nivelación'
        ];

        return view('convalidation.assign-components', compact('curriculum', 'componentTypes'));
    }

    /**
     * Store component assignment for an external subject.
     */
    public function storeComponentAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'external_subject_id' => 'required|exists:external_subjects,id',
            'component_type' => 'required|in:fundamental_required,professional_required,optional_fundamental,optional_professional,free_elective,thesis,leveling',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $externalSubject = ExternalSubject::findOrFail($request->external_subject_id);

            // Delete existing assignment if any
            ExternalSubjectComponent::where('external_subject_id', $externalSubject->id)->delete();

            // Create new assignment
            $assignment = ExternalSubjectComponent::create([
                'external_curriculum_id' => $externalSubject->external_curriculum_id,
                'external_subject_id' => $externalSubject->id,
                'component_type' => $request->component_type,
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Componente asignado exitosamente',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the simulation analysis with credit sums by component.
     */
    public function showSimulationAnalysis($curriculumId)
    {
        $curriculum = ExternalCurriculum::with(['externalSubjects.assignedComponent'])
            ->findOrFail($curriculumId);

        // Calculate credit sums by component
        $creditsByComponent = [];
        $componentTypes = [
            'fundamental_required',
            'professional_required',
            'optional_fundamental',
            'optional_professional',
            'free_elective',
            'thesis',
            'leveling'
        ];

        foreach ($componentTypes as $type) {
            $creditsByComponent[$type] = $curriculum->externalSubjects()
                ->whereHas('assignedComponent', function($query) use ($type) {
                    $query->where('component_type', $type);
                })
                ->sum('credits');
        }

        // Get component limits for reference
        $componentLimits = [
            'fundamental_required' => 39,
            'professional_required' => 79,
            'optional_fundamental' => 6,
            'optional_professional' => 9,
            'free_elective' => 28,
            'thesis' => 6,
            'leveling' => null // No limit
        ];

        return view('convalidation.simulation-analysis', compact(
            'curriculum',
            'creditsByComponent',
            'componentLimits'
        ));
    }

    /**
     * Create a new simulation based on component assignments.
     */
    public function createSimulation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'external_curriculum_id' => 'required|exists:external_curriculums,id',
            'simulation_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'leveling_credits' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $curriculum = ExternalCurriculum::findOrFail($request->external_curriculum_id);

            // Calculate actual credits by component from assignments
            $componentCredits = [];
            $componentTypes = [
                'fundamental_required',
                'professional_required',
                'optional_fundamental',
                'optional_professional',
                'free_elective',
                'thesis',
                'leveling'
            ];

            foreach ($componentTypes as $type) {
                $sum = $curriculum->externalSubjects()
                    ->whereHas('assignedComponent', function($query) use ($type) {
                        $query->where('component_type', $type);
                    })
                    ->sum('credits');
                
                $componentCredits[$type] = $sum;
            }

            // Validate leveling credits (can only increase, not decrease)
            $calculatedLevelingCredits = $componentCredits['leveling'];
            $requestedLevelingCredits = $request->leveling_credits ?? $calculatedLevelingCredits;

            if ($requestedLevelingCredits < $calculatedLevelingCredits) {
                return response()->json([
                    'error' => "Los créditos de nivelación no pueden ser menores a {$calculatedLevelingCredits}"
                ], 422);
            }

            // Update leveling credits if increased
            $componentCredits['leveling'] = $requestedLevelingCredits;

            // Create simulation
            $simulation = ConvalidationSimulation::create([
                'external_curriculum_id' => $curriculum->id,
                'simulation_name' => $request->simulation_name,
                'description' => $request->description,
                'fundamental_required_credits' => $componentCredits['fundamental_required'],
                'professional_required_credits' => $componentCredits['professional_required'],
                'optional_fundamental_credits' => $componentCredits['optional_fundamental'],
                'optional_professional_credits' => $componentCredits['optional_professional'],
                'free_elective_credits' => $componentCredits['free_elective'],
                'thesis_credits' => $componentCredits['thesis'],
                'leveling_credits' => $componentCredits['leveling'],
                'status' => 'draft'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Simulación creada exitosamente',
                'simulation' => $simulation,
                'redirect' => route('convalidation.simulation.show', $simulation->id)
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update leveling credits for a simulation.
     */
    public function updateLevelingCredits(Request $request, $simulationId)
    {
        $validator = Validator::make($request->all(), [
            'leveling_credits' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $simulation = ConvalidationSimulation::findOrFail($simulationId);

            // Get original calculated leveling credits from component assignments
            $calculatedLevelingCredits = $simulation->externalCurriculum->externalSubjects()
                ->whereHas('assignedComponent', function($query) {
                    $query->where('component_type', 'leveling');
                })
                ->sum('credits');

            // Validate that new value is not less than calculated
            if ($request->leveling_credits < $calculatedLevelingCredits) {
                return response()->json([
                    'error' => "Los créditos de nivelación no pueden ser menores a {$calculatedLevelingCredits} (suma de materias asignadas como nivelación)"
                ], 422);
            }

            // Update simulation
            $simulation->update([
                'leveling_credits' => $request->leveling_credits
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Créditos de nivelación actualizados exitosamente',
                'simulation' => $simulation
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the next available subject for a given component type.
     * Returns null if no subjects are available.
     */
    /**
     * Get the next available subject of a specific component type that hasn't been used yet.
     * Searches across subjects, leveling_subjects, and elective_subjects tables.
     * 
     * @param string $componentType
     * @param int $externalCurriculumId
     * @return mixed|null Subject model or null if none available
     */
    private function getNextAvailableSubject($componentType, $externalCurriculumId)
    {
        $subjects = collect();

        // Get subjects based on component type
        switch ($componentType) {
            case 'leveling':
                // Search in leveling_subjects table
                $subjects = LevelingSubject::all();
                break;

            case 'optional_fundamental':
                // Search in elective_subjects with type optativa_fundamental
                $subjects = ElectiveSubject::where('elective_type', 'optativa_fundamental')
                    ->where('is_active', true)
                    ->get();
                break;

            case 'optional_professional':
                // Search in elective_subjects with type optativa_profesional
                $subjects = ElectiveSubject::where('elective_type', 'optativa_profesional')
                    ->where('is_active', true)
                    ->get();
                break;

            case 'free_elective':
            case 'fundamental_required':
            case 'professional_required':
            case 'thesis':
                // Search in main subjects table using the 'type' field
                // Map component_type back to 'type' field values
                $typeMapping = [
                    'free_elective' => 'libre_eleccion',
                    'fundamental_required' => 'fundamental',
                    'professional_required' => 'profesional',
                    'thesis' => 'trabajo_grado',
                ];
                
                $typeValue = $typeMapping[$componentType] ?? null;
                if ($typeValue) {
                    $subjects = Subject::where('type', $typeValue)->get();
                }
                break;
        }

        // For each subject, check if it's already been used in a convalidation for this curriculum
        foreach ($subjects as $subject) {
            $timesUsed = SubjectConvalidation::where('external_curriculum_id', $externalCurriculumId)
                ->where('internal_subject_code', $subject->code)
                ->count();

            // If not used yet, return this subject
            if ($timesUsed === 0) {
                return $subject;
            }
        }

        // No available subjects found
        return null;
    }

    /**
     * Check if a code is a placeholder.
     * Placeholders start with # or are generic codes that shouldn't match directly.
     * 
     * @param string $code
     * @return bool
     */
    private function isPlaceholderCode($code)
    {
        // Placeholders start with #
        if (strpos($code, '#') === 0) {
            return true;
        }

        // Additional placeholder patterns if needed
        // e.g., codes that are clearly generic like "CODIGO-01", "LIBRE-01", etc.
        
        return false;
    }

    /**
     * Get all internal subjects from all tables (subjects, leveling_subjects, elective_subjects)
     * Returns a collection with normalized component_type for each subject
     * 
     * @return \Illuminate\Support\Collection
     */
    private function getAllInternalSubjects()
    {
        $allSubjects = collect();

        // 1. Get subjects from main 'subjects' table
        $mainSubjects = Subject::all()->map(function ($subject) {
            return [
                'code' => $subject->code,
                'name' => $subject->name,
                'credits' => $subject->credits,
                'component_type' => $subject->component, // Uses the accessor
                'source_table' => 'subjects',
                'model' => $subject
            ];
        });
        $allSubjects = $allSubjects->merge($mainSubjects);

        // 2. Get leveling subjects
        $levelingSubjects = LevelingSubject::all()->map(function ($subject) {
            return [
                'code' => $subject->code,
                'name' => $subject->name,
                'credits' => $subject->credits,
                'component_type' => 'leveling',
                'source_table' => 'leveling_subjects',
                'model' => $subject
            ];
        });
        $allSubjects = $allSubjects->merge($levelingSubjects);

        // 3. Get elective subjects
        $electiveSubjects = ElectiveSubject::where('is_active', true)->get()->map(function ($subject) {
            // Map elective_type to component_type
            $componentType = match($subject->elective_type) {
                'optativa_fundamental' => 'optional_fundamental',
                'optativa_profesional' => 'optional_professional',
                default => 'free_elective'
            };

            return [
                'code' => $subject->code,
                'name' => $subject->name,
                'credits' => $subject->credits,
                'component_type' => $componentType,
                'source_table' => 'elective_subjects',
                'model' => $subject
            ];
        });
        $allSubjects = $allSubjects->merge($electiveSubjects);

        return $allSubjects;
    }

    /**
     * Bulk convalidation: automatically match external subjects with internal subjects.
     */
    public function bulkConvalidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'external_curriculum_id' => 'required|exists:external_curriculums,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $curriculum = ExternalCurriculum::findOrFail($request->external_curriculum_id);
            $externalSubjects = $curriculum->externalSubjects()
                ->whereDoesntHave('convalidation')
                ->where(function($query) {
                    // Exclude removed subjects from bulk convalidation
                    $query->whereNull('change_type')
                          ->orWhere('change_type', '!=', 'removed');
                })
                ->get();

            // Get all internal subjects from all tables (subjects, leveling_subjects, elective_subjects)
            $internalSubjects = $this->getAllInternalSubjects();
            $results = [];

            foreach ($externalSubjects as $externalSubject) {
                // Skip placeholders - don't process them in bulk convalidation
                if ($this->isPlaceholderCode($externalSubject->code)) {
                    continue; // Skip this subject completely
                }

                $result = [
                    'external_subject' => [
                        'id' => $externalSubject->id,
                        'code' => $externalSubject->code,
                        'name' => $externalSubject->name,
                        'credits' => $externalSubject->credits
                    ],
                    'internal_subject' => null,
                    'component_type' => null,
                    'method' => null,
                    'status' => 'skipped',
                    'message' => 'Sin coincidencias'
                ];

                // Try to find a match
                $matchedSubject = null;
                $matchMethod = null;

                // 1. Try exact code match
                $codeMatch = $internalSubjects->firstWhere('code', $externalSubject->code);
                if ($codeMatch) {
                    $matchedSubject = $codeMatch;
                    $matchMethod = 'code';
                }
                
                // 2. If no code match, try name similarity (≥80%)
                if (!$matchedSubject) {
                    $bestMatch = null;
                    $bestSimilarity = 0;

                    foreach ($internalSubjects as $internalSubject) {
                        similar_text(
                            strtolower($externalSubject->name),
                            strtolower($internalSubject['name']),
                            $similarity
                        );

                        if ($similarity > $bestSimilarity && $similarity >= 80) {
                            $bestSimilarity = $similarity;
                            $bestMatch = $internalSubject;
                        }
                    }

                    if ($bestMatch) {
                        $matchedSubject = $bestMatch;
                        $matchMethod = 'name';
                    }
                }

                // If we found a match, create the convalidation
                if ($matchedSubject) {
                    try {
                        // Get the component type from the matched subject (now properly resolved)
                        $componentType = $matchedSubject['component_type'];

                        // Skip electives and free electives - these MUST be manually convalidated
                        $isElectiveOrFree = in_array($componentType, [
                            'free_elective',
                            'optional_fundamental',
                            'optional_professional'
                        ]);

                        if ($isElectiveOrFree) {
                            $result['status'] = 'skipped';
                            $result['message'] = 'Materia optativa/libre - Debe convalidarse manualmente';
                            $result['component_type'] = $componentType;
                            $result['internal_subject'] = [
                                'code' => $matchedSubject['code'],
                                'name' => $matchedSubject['name'],
                                'credits' => $matchedSubject['credits']
                            ];
                            $results[] = $result;
                            continue; // Skip to next subject
                        }

                        // Check if this is an elective or optional subject (this check is now redundant but kept for safety)
                        $isElectiveOrOptional = in_array($componentType, [
                            'free_elective',
                            'optional_fundamental',
                            'optional_professional'
                        ]);

                        // If it's an elective/optional, check if it's already used
                        if ($isElectiveOrOptional) {
                            $timesUsed = SubjectConvalidation::where('external_curriculum_id', $curriculum->id)
                                ->where('internal_subject_code', $matchedSubject['code'])
                                ->count();

                            // If already used, try to find the next available subject of the same type
                            if ($timesUsed > 0) {
                                $nextAvailable = $this->getNextAvailableSubject($componentType, $curriculum->id);
                                
                                if ($nextAvailable) {
                                    // Use the next available subject - convert model to array format
                                    $matchedSubject = [
                                        'code' => $nextAvailable->code,
                                        'name' => $nextAvailable->name,
                                        'credits' => $nextAvailable->credits,
                                        'component_type' => $componentType,
                                        'source_table' => 'subjects',
                                        'model' => $nextAvailable
                                    ];
                                } else {
                                    // No more subjects available, mark as not convalidated
                                    SubjectConvalidation::create([
                                        'external_curriculum_id' => $curriculum->id,
                                        'external_subject_id' => $externalSubject->id,
                                        'internal_subject_code' => null,
                                        'convalidation_type' => 'not_convalidated',
                                        'notes' => 'No hay más materias ' . $componentType . ' disponibles. Marcada como materia nueva.',
                                        'status' => 'pending'
                                    ]);

                                    // Create component assignment
                                    ExternalSubjectComponent::create([
                                        'external_curriculum_id' => $curriculum->id,
                                        'external_subject_id' => $externalSubject->id,
                                        'component_type' => $componentType,
                                        'notes' => 'Sin materias disponibles - Materia nueva'
                                    ]);

                                    $result['component_type'] = $componentType;
                                    $result['method'] = 'auto';
                                    $result['status'] = 'success';
                                    $result['message'] = 'Marcada como no convalidada (sin ' . $componentType . ' disponibles)';
                                    $results[] = $result;
                                    continue;
                                }
                            }
                        }

                        // Create convalidation
                        SubjectConvalidation::create([
                            'external_curriculum_id' => $curriculum->id,
                            'external_subject_id' => $externalSubject->id,
                            'internal_subject_code' => $matchedSubject['code'],
                            'convalidation_type' => 'direct',
                            'notes' => 'Convalidación masiva automática por ' . ($matchMethod === 'code' ? 'código exacto' : 'similitud de nombre'),
                            'status' => 'pending'
                        ]);

                        // Create component assignment
                        ExternalSubjectComponent::create([
                            'external_curriculum_id' => $curriculum->id,
                            'external_subject_id' => $externalSubject->id,
                            'component_type' => $componentType,
                            'notes' => 'Asignado automáticamente'
                        ]);

                        $result['internal_subject'] = [
                            'code' => $matchedSubject['code'],
                            'name' => $matchedSubject['name'],
                            'credits' => $matchedSubject['credits']
                        ];
                        $result['component_type'] = $componentType;
                        $result['method'] = $matchMethod;
                        $result['status'] = 'success';
                        $result['message'] = 'Convalidada exitosamente';

                    } catch (\Exception $e) {
                        $result['status'] = 'error';
                        $result['message'] = 'Error al crear convalidación: ' . $e->getMessage();
                    }
                }

                $results[] = $result;
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
                    'error' => count(array_filter($results, fn($r) => $r['status'] === 'error'))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en convalidación masiva: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate convalidated credits by component from configured convalidations
     */
    private function calculateConvalidatedCreditsByComponent(ExternalCurriculum $externalCurriculum)
    {
        $creditsByComponent = [
            'fundamental_required' => 0,
            'professional_required' => 0,
            'optional_fundamental' => 0,
            'optional_professional' => 0,
            'free_elective' => 0,
            'thesis' => 0,
            'leveling' => 0,
        ];

        // Get all convalidations for this curriculum
        $convalidations = $externalCurriculum->convalidations()
            ->with(['externalSubject', 'internalSubject', 'externalSubject.assignedComponent'])
            ->get();

        foreach ($convalidations as $convalidation) {
            $componentType = null;
            $credits = 0;

            if ($convalidation->convalidation_type === 'direct' && $convalidation->internalSubject) {
                // For direct convalidations, use internal subject credits and component
                $componentType = $convalidation->internalSubject->component ?? null;
                $credits = $convalidation->internalSubject->credits ?? 0;
            } elseif ($convalidation->convalidation_type === 'flexible_component' && $convalidation->externalSubject) {
                // For flexible components, use component_type from convalidation
                $componentType = $convalidation->component_type ?? null;
                $credits = $convalidation->externalSubject->credits ?? 0;
            }

            // Add credits to the corresponding component
            if ($componentType && isset($creditsByComponent[$componentType])) {
                $creditsByComponent[$componentType] += $credits;
            }
        }

        return $creditsByComponent;
    }

    /**
     * Generate a comprehensive PDF report of the impact analysis
     */
    public function generateImpactReportPdf(Request $request, ExternalCurriculum $externalCurriculum)
    {
        try {
            $data = $request->json()->all();
            $results = $data['results'] ?? [];
            $creditLimits = $data['credit_limits'] ?? [];

            // Get curriculum stats
            $stats = $externalCurriculum->getStats();
            
            // Get all convalidations for this curriculum
            $convalidations = $externalCurriculum->convalidations()
                ->with(['externalSubject', 'internalSubject'])
                ->where('convalidation_type', 'direct')
                ->get();
            
            // Get N:N convalidation groups
            $nnGroups = ConvalidationGroup::where('external_curriculum_id', $externalCurriculum->id)
                ->with(['externalSubject', 'internalSubjects'])
                ->get();
            
            // Prepare data for the view
            $reportData = [
                'curriculum' => $externalCurriculum,
                'results' => $results,
                'credit_limits' => $creditLimits,
                'stats' => $stats,
                'convalidations' => $convalidations,
                'nnGroups' => $nnGroups,
                'generated_at' => now()->format('d/m/Y H:i:s'),
            ];

            // Return HTML view optimized for PDF printing
            return view('convalidation.impact-report-pdf', $reportData);

        } catch (\Exception $e) {
            Log::error('Error generating impact report PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all convalidation groups for an external curriculum
     */
    public function getConvalidationGroups(ExternalCurriculum $externalCurriculum)
    {
        try {
            $groups = ConvalidationGroup::where('external_curriculum_id', $externalCurriculum->id)
                ->with(['externalSubject', 'internalSubjects'])
                ->get();

            return response()->json([
                'success' => true,
                'groups' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new convalidation group (N:N relationship)
     */
    public function storeConvalidationGroup(Request $request)
    {
        // Log incoming request data for debugging
        Log::info('Creating N:N group - Request data:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'external_curriculum_id' => 'required|exists:external_curriculums,id',
            'external_subject_id' => 'required|exists:external_subjects,id',
            'group_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'equivalence_type' => 'required|in:all,any,credits',
            'equivalence_percentage' => 'nullable|numeric|min:0|max:100',
            'component_type' => 'required|in:fundamental_required,professional_required,optional_fundamental,optional_professional,free_elective,thesis,leveling',
            'internal_subject_codes' => 'required|array|min:1',
            'internal_subject_codes.*' => 'required|exists:subjects,code'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for N:N group:', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the convalidation group
            $group = ConvalidationGroup::create([
                'external_curriculum_id' => $request->external_curriculum_id,
                'external_subject_id' => $request->external_subject_id,
                'group_name' => $request->group_name,
                'description' => $request->description,
                'equivalence_type' => $request->equivalence_type,
                'equivalence_percentage' => $request->equivalence_percentage ?? 100,
                'component_type' => $request->component_type,
                'metadata' => $request->metadata ?? []
            ]);

            // Attach internal subjects to the group
            foreach ($request->internal_subject_codes as $index => $subjectCode) {
                ConvalidationGroupSubject::create([
                    'convalidation_group_id' => $group->id,
                    'internal_subject_code' => $subjectCode,
                    'sort_order' => $index,
                    'weight' => 1.0,
                    'notes' => null
                ]);
            }

            // Load relationships for response
            $group->load(['externalSubject', 'internalSubjects']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Grupo de convalidación creado exitosamente',
                'group' => $group
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating convalidation group: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a convalidation group
     */
    public function updateConvalidationGroup(Request $request, $groupId)
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'equivalence_type' => 'nullable|in:all,any,credits',
            'equivalence_percentage' => 'nullable|numeric|min:0|max:100',
            'internal_subject_codes' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $group = ConvalidationGroup::findOrFail($groupId);

            // Update basic fields
            $group->update([
                'group_name' => $request->group_name ?? $group->group_name,
                'description' => $request->description ?? $group->description,
                'equivalence_type' => $request->equivalence_type ?? $group->equivalence_type,
                'equivalence_percentage' => $request->equivalence_percentage ?? $group->equivalence_percentage
            ]);

            // Update internal subjects if provided
            if ($request->has('internal_subject_codes')) {
                // Delete existing associations
                ConvalidationGroupSubject::where('convalidation_group_id', $group->id)->delete();

                // Create new associations
                foreach ($request->internal_subject_codes as $index => $subjectCode) {
                    ConvalidationGroupSubject::create([
                        'convalidation_group_id' => $group->id,
                        'internal_subject_code' => $subjectCode,
                        'sort_order' => $index,
                        'weight' => 1.0
                    ]);
                }
            }

            $group->load(['externalSubject', 'internalSubjects']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Grupo actualizado exitosamente',
                'group' => $group
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a convalidation group
     */
    public function destroyConvalidationGroup($groupId)
    {
        try {
            $group = ConvalidationGroup::findOrFail($groupId);
            $group->delete(); // Cascade will delete related records

            return response()->json([
                'success' => true,
                'message' => 'Grupo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el grupo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the original curriculum state (before changes)
     * This returns external subjects with change_type != 'removed'
     */
    public function getOriginalCurriculumState($curriculumId)
    {
        try {
            $curriculum = ExternalCurriculum::findOrFail($curriculumId);
            
            // Get all external subjects excluding removed ones
            $subjects = $curriculum->externalSubjects()
                ->where(function($query) {
                    $query->whereNull('change_type')
                          ->orWhere('change_type', '!=', 'removed');
                })
                ->get()
                ->map(function($subject) {
                    return [
                        'id' => $subject->id,
                        'code' => $subject->code,
                        'name' => $subject->name,
                        'credits' => $subject->credits,
                        'semester' => $subject->semester,
                        'change_type' => $subject->change_type
                    ];
                });

            return response()->json([
                'success' => true,
                'subjects' => $subjects
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado original: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all internal subjects for API (UNAL curriculum)
     * Used for selecting equivalences in N:N groups
     */
    public function getInternalSubjectsForApi()
    {
        try {
            $subjects = Subject::select('code', 'name', 'credits', 'semester', 'type')
                ->orderBy('semester')
                ->orderBy('code')
                ->get()
                ->map(function($subject) {
                    return [
                        'code' => $subject->code,
                        'name' => $subject->name,
                        'credits' => $subject->credits,
                        'semester' => $subject->semester,
                        'type' => $subject->type
                    ];
                });

            return response()->json([
                'success' => true,
                'subjects' => $subjects
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las materias internas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple external curriculums by their IDs
     * Used when resetting simulation to clean up convalidations
     */
    public function deleteMultipleConvalidations(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'curriculum_ids' => 'required|array',
                'curriculum_ids.*' => 'required|integer|exists:external_curriculums,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $curriculumIds = $request->input('curriculum_ids');
            $deletedCount = 0;

            DB::beginTransaction();

            foreach ($curriculumIds as $curriculumId) {
                $curriculum = ExternalCurriculum::find($curriculumId);
                
                if ($curriculum) {
                    // Delete related data using proper relationship names
                    $curriculum->externalSubjects()->delete();
                    $curriculum->convalidations()->delete();
                    $curriculum->convalidationGroups()->delete();
                    
                    // Delete the curriculum itself
                    $curriculum->delete();
                    $deletedCount++;
                    
                    Log::info("Deleted external curriculum: {$curriculumId}");
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} convalidación(es) eliminada(s) exitosamente",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting multiple convalidations: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar las convalidaciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
