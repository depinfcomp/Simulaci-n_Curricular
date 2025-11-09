<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExternalCurriculum;
use App\Models\ExternalSubject;
use App\Models\SubjectConvalidation;
use App\Models\Subject;
use App\Models\Student;
use App\Models\StudentConvalidation;
use App\Services\ExcelImportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            'pending_convalidations' => ExternalSubject::pendingConvalidation()->count(),
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
        $externalCurriculum->load(['externalSubjects.convalidation.internalSubject']);
        $subjectsBySemester = $externalCurriculum->getConvalidationsBySemester();
        $internalSubjects = Subject::orderBy('semester')->orderBy('name')->get();
        $stats = $externalCurriculum->getStats();

        return view('convalidation.show', compact('externalCurriculum', 'subjectsBySemester', 'internalSubjects', 'stats'));
    }

    /**
     * Create or update a convalidation mapping.
     */
    public function storeConvalidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'external_subject_id' => 'required|exists:external_subjects,id',
            'convalidation_type' => 'required|in:direct,free_elective,not_convalidated',
            'internal_subject_code' => 'nullable|exists:subjects,code',
            'notes' => 'nullable|string',
            'equivalence_percentage' => 'nullable|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Validate that direct convalidations have an internal subject
        if ($request->convalidation_type === 'direct' && !$request->internal_subject_code) {
            return response()->json(['error' => 'Las convalidaciones directas requieren una materia interna'], 422);
        }

        // Validate that not_convalidated type doesn't have an internal subject
        if ($request->convalidation_type === 'not_convalidated' && $request->internal_subject_code) {
            return response()->json(['error' => 'Las materias no convalidadas no deben tener una materia interna asignada'], 422);
        }

        try {
            $externalSubject = ExternalSubject::findOrFail($request->external_subject_id);

            // Delete existing convalidation if any
            SubjectConvalidation::where('external_subject_id', $externalSubject->id)->delete();

            // Create new convalidation
            $convalidation = SubjectConvalidation::create([
                'external_curriculum_id' => $externalSubject->external_curriculum_id,
                'external_subject_id' => $externalSubject->id,
                'internal_subject_code' => $request->convalidation_type === 'direct' ? $request->internal_subject_code : null,
                'convalidation_type' => $request->convalidation_type,
                'notes' => $request->notes,
                'equivalence_percentage' => $request->convalidation_type === 'not_convalidated' ? 0.00 : ($request->equivalence_percentage ?? 100.00),
                'status' => 'pending'
            ]);

            $convalidation->load('internalSubject');
            
            // Get updated statistics
            $curriculum = ExternalCurriculum::find($externalSubject->external_curriculum_id);
            $updatedStats = $curriculum->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Convalidación creada exitosamente',
                'convalidation' => $convalidation,
                'stats' => $updatedStats
            ]);

        } catch (\Exception $e) {
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
                'success' => true,
                'message' => 'Convalidación eliminada exitosamente'
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
        
        // Get internal subjects and calculate similarity
        $internalSubjects = Subject::all();
        $suggestions = [];

        foreach ($internalSubjects as $internal) {
            $similarity = $this->calculateSimilarity($externalSubject->name, $internal->name);
            
            if ($similarity > 0.3) { // 30% similarity threshold
                $suggestions[] = [
                    'subject' => $internal,
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
     * Delete an external curriculum and all its data.
     */
    public function destroy(ExternalCurriculum $externalCurriculum)
    {
        try {
            // Delete the uploaded file
            if ($externalCurriculum->uploaded_file) {
                Storage::disk('public')->delete($externalCurriculum->uploaded_file);
            }

            // Delete the curriculum (cascade will handle related records)
            $externalCurriculum->delete();

            return redirect()->route('convalidation.index')
                ->with('success', 'Malla externa eliminada exitosamente');

        } catch (\Exception $e) {
            return redirect()->route('convalidation.index')
                ->withErrors(['error' => 'Error al eliminar la malla: ' . $e->getMessage()]);
        }
    }

    /**
     * Analyze the impact of migrating students from original curriculum to external curriculum with convalidations
     */
    public function analyzeConvalidationImpact(ExternalCurriculum $externalCurriculum, Request $request)
    {
        try {
            // Log para debugging
            \Log::info('Iniciando análisis de impacto', [
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

            \Log::info('Límites de créditos configurados', $creditLimits);

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

            // Load external curriculum subjects if not already loaded
            if (!$externalCurriculum->relationLoaded('externalSubjects')) {
                $externalCurriculum->load('externalSubjects');
            }

            // Separate direct, free elective, and not convalidated convalidations
            $directConvalidations = $convalidations->where('convalidation_type', 'direct');
            $freeElectiveConvalidations = $convalidations->where('convalidation_type', 'free_elective');
            $notConvalidatedConvalidations = $convalidations->where('convalidation_type', 'not_convalidated');

            // Get all subjects from original curriculum
            $originalSubjects = Subject::with(['prerequisites', 'requiredFor'])->get()->keyBy('code');
            $totalOriginalSubjects = $originalSubjects->count();

            $results = [
                'total_students' => $students->count(),
                'affected_students' => 0,
                'students_with_improved_progress' => 0,
                'students_with_no_change' => 0,
                'students_with_reduced_progress' => 0,
                'affected_percentage' => 0,
                'average_progress_change' => 0,
                'total_convalidated_subjects' => $directConvalidations->count(),
                'direct_convalidations_count' => $directConvalidations->count(),
                'free_electives_count' => $freeElectiveConvalidations->count(),
                'additional_subjects_required' => $notConvalidatedConvalidations->count(),
                'total_credits_lost' => $notConvalidatedConvalidations->sum(function($conv) { 
                    return $conv->externalSubject->credits ?? 0; 
                }),
                'curriculum_size_change' => [
                    'original_subjects' => $totalOriginalSubjects ?? \App\Models\Subject::count(),
                    'new_subjects' => $externalCurriculum->externalSubjects->count(),
                    'size_difference' => $externalCurriculum->externalSubjects->count() - (\App\Models\Subject::count())
                ],
                'student_details' => [],
                'subject_impact' => [],
                'configuration' => $creditLimits,
                'credits_by_component' => [
                    'fundamental_required' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'fundamental_optional' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'professional_required' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'professional_optional' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'leveling' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'thesis' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                    'free_elective' => ['used' => 0, 'overflow' => 0, 'excess' => 0],
                ]
            ];

            $totalProgressChange = 0;
            $subjectImpactMap = [];

            foreach ($students as $student) {
                try {
                    $impact = $this->calculateStudentConvalidationImpactWithComponentLimits(
                        $student, 
                        $directConvalidations, 
                        $freeElectiveConvalidations,
                        $notConvalidatedConvalidations,
                        $externalCurriculum,
                        $creditLimits,
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
                        
                    $results['student_details'][] = [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'original_progress' => round($impact['original_progress'] ?? 0, 1),
                        'new_progress' => round($impact['new_progress'] ?? 0, 1),
                        'progress_change' => round($impact['progress_change'] ?? 0, 1),
                        'convalidated_subjects_count' => $impact['convalidated_subjects_count'] ?? 0,
                        'new_subjects_count' => $impact['new_subjects_count'] ?? 0,
                        'lost_credits_count' => $impact['lost_credits_count'] ?? 0,
                        'convalidation_details' => $impact['convalidation_details'] ?? [],
                        'progress_explanation' => $impact['progress_explanation'] ?? 'Sin explicación disponible'
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing student ' . $student->id . ': ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue; // Skip this student and continue with the next one
                }
            }

            // Calculate final statistics
            $results['affected_percentage'] = $results['total_students'] > 0 
                ? round(($results['affected_students'] / $results['total_students']) * 100, 1)
                : 0;

            $results['average_progress_change'] = $results['affected_students'] > 0 
                ? round($totalProgressChange / $results['affected_students'], 1)
                : 0;

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en análisis de impacto', [
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
            \Log::info('getTotalCredits called for curriculum ID: ' . $externalCurriculum->id);
            $startTime = microtime(true);
            
            $totalCredits = $externalCurriculum->externalSubjects()
                ->sum('credits');
            
            $totalSubjects = $externalCurriculum->externalSubjects()->count();
            
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            \Log::info('getTotalCredits completed', [
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
            \Log::error('getTotalCredits error: ' . $e->getMessage());
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
            $originalTotalSubjects = \App\Models\Subject::count(); // Original curriculum size
            
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
            \Log::error('Error in calculateStudentConvalidationImpactCorrect: ' . $e->getMessage());
            \Log::error('Student ID: ' . $student->id);
            \Log::error('External Curriculum ID: ' . $externalCurriculum->id);
            
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
        $freeElectiveConvalidations,
        $notConvalidatedConvalidations,
        $externalCurriculum,
        array $creditLimits,
        $originalSubjects,
        $totalOriginalSubjects
    ) {
        try {
            // Get student's passed subjects
            $passedSubjects = $student->subjects->where('pivot.status', 'passed')->keyBy('code');
            
            // Calculate original progress
            $originalProgress = ($passedSubjects->count() / max($totalOriginalSubjects, 1)) * 100;
            
            // Get new curriculum size
            $externalSubjects = $externalCurriculum->externalSubjects ?? collect();
            $newTotalSubjects = max($externalSubjects->count(), 1);
            
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
            $convalidatedCount = 0;
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
                        $convalidatedCount++;
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
                    $convalidatedCount++;
                    $convalidationDetails[] = [
                        'type' => 'direct',
                        'subject' => $internalSubject->name,
                        'component' => $component,
                        'credits' => $credits,
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
            
            // Process free elective convalidations
            foreach ($freeElectiveConvalidations as $convalidation) {
                if (!$convalidation->externalSubject) {
                    continue;
                }
                
                $externalSubjectCode = $convalidation->externalSubject->code;
                
                if (!$passedSubjects->has($externalSubjectCode)) {
                    continue;
                }
                
                $credits = $convalidation->externalSubject->credits ?? 3;
                
                // Check if fits in free elective limit
                if ($componentCredits['free_elective'] + $credits <= $freeElectiveLimit) {
                    $componentCredits['free_elective'] += $credits;
                    $convalidatedCount++;
                    $convalidationDetails[] = [
                        'type' => 'free_elective',
                        'subject' => $convalidation->externalSubject->name,
                        'credits' => $credits,
                    ];
                } else {
                    // Partial or excess
                    $remaining = $freeElectiveLimit - $componentCredits['free_elective'];
                    if ($remaining > 0) {
                        $componentCredits['free_elective'] += $remaining;
                        $convalidatedCount++;
                        
                        $excessCredits[] = [
                            'credits' => $credits - $remaining,
                            'subject' => $convalidation->externalSubject->name,
                        ];
                        
                        $convalidationDetails[] = [
                            'type' => 'free_elective_partial',
                            'subject' => $convalidation->externalSubject->name,
                            'credits_accepted' => $remaining,
                            'credits_excess' => $credits - $remaining,
                        ];
                    } else {
                        $excessCredits[] = [
                            'credits' => $credits,
                            'subject' => $convalidation->externalSubject->name,
                        ];
                        
                        $convalidationDetails[] = [
                            'type' => 'excess',
                            'subject' => $convalidation->externalSubject->name,
                            'credits' => $credits,
                        ];
                    }
                }
            }
            
            // Calculate total valid credits
            $totalValidCredits = array_sum($componentCredits);
            $totalExcessCredits = array_sum(array_column($excessCredits, 'credits'));
            
            // Calculate new progress (based on valid credits)
            $newProgress = ($convalidatedCount / $newTotalSubjects) * 100;
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
            \Log::error('Error in calculateStudentConvalidationImpactWithComponentLimits: ' . $e->getMessage());
            \Log::error('Student ID: ' . $student->id);
            
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
            'fundamental_required' => 'max_required_fundamental_credits',
            'fundamental_optional' => 'max_optional_fundamental_credits',
            'professional_required' => 'max_required_professional_credits',
            'professional_optional' => 'max_optional_professional_credits',
            'leveling' => 'max_leveling_credits',
            'thesis' => 'max_thesis_credits',
            'free_elective' => 'max_free_elective_credits',
        ];
        
        $key = $map[$component] ?? null;
        if (!$key) {
            return null;
        }
        
        $limit = $creditLimits[$key] ?? null;
        
        // null means no limit
        return $limit !== null && $limit !== '' ? (int)$limit : null;
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

            // Calculate total subjects
            $totalSubjects = 0;
            foreach ($curriculumData as $semester => $subjects) {
                $totalSubjects += count($subjects);
            }

            // Create external curriculum using the correct fields
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
            foreach ($curriculumData as $semester => $subjects) {
                foreach ($subjects as $subjectData) {
                    // Prepare additional_data with information about prerequisites and simulation details
                    $additionalData = [
                        'prerequisites' => $subjectData['prerequisites'] ?? [],
                        'is_added_in_simulation' => $subjectData['isAdded'] ?? false,
                        'original_description' => $subjectData['description'] ?? null,
                        'source' => 'simulation'
                    ];

                    ExternalSubject::create([
                        'external_curriculum_id' => $externalCurriculum->id,
                        'code' => $subjectData['code'],
                        'name' => $subjectData['name'],
                        'semester' => (int) $subjectData['semester'],
                        'credits' => $subjectData['credits'] ?? 3, // Default to 3 credits if not specified
                        'description' => $subjectData['description'] ?? $subjectData['name'],
                        'additional_data' => $additionalData
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Malla curricular guardada exitosamente para convalidación',
                'curriculum_id' => $externalCurriculum->id,
                'redirect_url' => route('convalidation.show', $externalCurriculum->id)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving modified curriculum: ' . $e->getMessage());
            
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
                    'name' => $student->name,
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
}
