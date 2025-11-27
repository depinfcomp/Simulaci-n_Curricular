<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentCurrentSubject;
use App\Services\StudentMetricsService;
use App\Services\CreditDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimulationController extends Controller
{
    /**
     * @var StudentMetricsService
     */
    private StudentMetricsService $metricsService;

    /**
     * @var CreditDistributionService
     */
    private CreditDistributionService $creditService;

    public function __construct(StudentMetricsService $metricsService = null, CreditDistributionService $creditService = null)
    {
        $this->metricsService = $metricsService ?? new StudentMetricsService();
        $this->creditService = $creditService ?? new CreditDistributionService();
    }

    /**
     * Analyze the impact of curriculum changes on students
     */
    public function analyzeImpact(Request $request)
    {
        $changes = $request->input('changes', []);
        
        // Debug: Log received changes
        \Log::info('Analyzing impact with changes:', ['changes' => $changes]);
        
        // Get all students with their current progress and current subjects
        $students = Student::with([
            'subjects' => function($query) {
                $query->wherePivot('status', 'passed');
            },
            'currentSubjects.subject'
        ])->get();
        
        $impactAnalysis = [
            'total_students' => $students->count(),
            'affected_students' => 0,
            'students_with_delays' => 0,
            'students_with_gaps' => 0,
            'students_with_prerequisites_issues' => 0,
            'students_with_papa_impact' => 0,
            'students_with_progress_impact' => 0,
            'students_with_insignificant_papa_impact' => 0,  // NEW: Non-significant PAPA changes
            'students_with_insignificant_progress_impact' => 0,  // NEW: Non-significant progress changes
            'affected_percentage' => 0,
            'average_papa_change' => 0,
            'average_progress_change' => 0,
            'details' => []
        ];
        
        $totalPapaChange = 0;
        $totalProgressChange = 0;
        $studentsWithPapaImpact = 0;
        $studentsWithProgressImpact = 0;
        
        foreach ($students as $student) {
            $impact = $this->analyzeStudentImpact($student, $changes);
            
            if ($impact['has_impact']) {
                $impactAnalysis['affected_students']++;
                
                if ($impact['has_delay']) {
                    $impactAnalysis['students_with_delays']++;
                }
                
                if ($impact['has_gaps']) {
                    $impactAnalysis['students_with_gaps']++;
                }
                
                if ($impact['has_prerequisite_issues']) {
                    $impactAnalysis['students_with_prerequisites_issues']++;
                }
                
                // Track PAPA impact
                if (abs($impact['papa_change']) > 0.01) {
                    $impactAnalysis['students_with_papa_impact']++;
                    $studentsWithPapaImpact++;
                    $totalPapaChange += $impact['papa_change'];
                } elseif (abs($impact['papa_change']) > 0.001) {
                    // Non-significant but measurable PAPA change
                    $impactAnalysis['students_with_insignificant_papa_impact']++;
                }
                
                // Track progress impact
                if (abs($impact['progress_change']) > 0.1) {
                    $impactAnalysis['students_with_progress_impact']++;
                    $studentsWithProgressImpact++;
                    $totalProgressChange += $impact['progress_change'];
                } elseif (abs($impact['progress_change']) > 0.001) {
                    // Non-significant but measurable progress change
                    $impactAnalysis['students_with_insignificant_progress_impact']++;
                }
                
                // Get current subjects with names - search in multiple places
                $currentSubjectsWithNames = $student->currentSubjects->map(function($currentSubject) {
                    $subjectName = null;
                    $subjectCode = $currentSubject->subject_code;
                    
                    // Try 0: Get from student_current_subjects.subject_name (NEW - from import)
                    if ($currentSubject->subject_name) {
                        $subjectName = $currentSubject->subject_name;
                    }
                    
                    // Try 1: Get from direct relationship (subjects table)
                    if (!$subjectName && $currentSubject->subject && $currentSubject->subject->name) {
                        $subjectName = $currentSubject->subject->name;
                    }
                    
                    // Try 2: Look up subject by code directly in subjects table
                    if (!$subjectName) {
                        $subject = Subject::where('code', $subjectCode)->first();
                        if ($subject && $subject->name) {
                            $subjectName = $subject->name;
                        }
                    }
                    
                    // Try 3: Check in academic_histories table
                    if (!$subjectName) {
                        $historyRecord = DB::table('academic_histories')
                            ->where('subject_code', $subjectCode)
                            ->whereNotNull('subject_name')
                            ->where('subject_name', '!=', '')
                            ->select('subject_name')
                            ->first();
                        
                        if ($historyRecord && $historyRecord->subject_name) {
                            $subjectName = $historyRecord->subject_name;
                        }
                    }
                    
                    // Try 4: Check in student_subject pivot table (historical records)
                    if (!$subjectName) {
                        $pivotSubject = DB::table('student_subject as ss')
                            ->join('subjects as s', 'ss.subject_code', '=', 's.code')
                            ->where('ss.subject_code', $subjectCode)
                            ->whereNotNull('s.name')
                            ->select('s.name')
                            ->first();
                        
                        if ($pivotSubject && $pivotSubject->name) {
                            $subjectName = $pivotSubject->name;
                        }
                    }
                    
                    // Try 5: Check if it's an alias
                    if (!$subjectName) {
                        $alias = DB::table('subject_aliases')
                            ->where('alias_code', $subjectCode)
                            ->first();
                        
                        if ($alias) {
                            $mainSubject = Subject::where('code', $alias->subject_code)->first();
                            if ($mainSubject && $mainSubject->name) {
                                $subjectName = $mainSubject->name . ' (alias)';
                            }
                        }
                    }
                    
                    // If still no name found, use a descriptive fallback
                    if (!$subjectName) {
                        $subjectName = 'Materia ' . $subjectCode;
                    }
                    
                    return [
                        'code' => $subjectCode,
                        'name' => $subjectName
                    ];
                })->toArray();
                
                $impactAnalysis['details'][] = [
                    'student_id' => $student->id,
                    'student_document' => $student->document,
                    'current_semester' => $this->getCurrentSemester($student->progress_percentage),
                    'current_subjects' => $currentSubjectsWithNames,
                    'current_papa' => $impact['current_papa'],
                    'projected_papa' => $impact['projected_papa'],
                    'papa_change' => $impact['papa_change'],
                    'current_progress' => $impact['current_progress'],
                    'projected_progress' => $impact['projected_progress'],
                    'progress_change' => $impact['progress_change'],
                    'credit_impact' => $impact['credit_impact'],
                    'issues' => $impact['issues']
                ];
            }
        }
        
        $impactAnalysis['affected_percentage'] = $impactAnalysis['total_students'] > 0 
            ? round(($impactAnalysis['affected_students'] / $impactAnalysis['total_students']) * 100, 1)
            : 0;
        
        // Calculate average changes
        $impactAnalysis['average_papa_change'] = $studentsWithPapaImpact > 0
            ? round($totalPapaChange / $studentsWithPapaImpact, 2)
            : 0;
        
        $impactAnalysis['average_progress_change'] = $studentsWithProgressImpact > 0
            ? round($totalProgressChange / $studentsWithProgressImpact, 2)
            : 0;
        
        return response()->json($impactAnalysis);
    }
    
    /**
     * Analyze impact on a specific student
     */
    private function analyzeStudentImpact(Student $student, array $changes)
    {
        $impact = [
            'has_impact' => false,
            'has_delay' => false,
            'has_gaps' => false,
            'has_prerequisite_issues' => false,
            'current_papa' => round($student->average_grade ?? 0, 2),
            'projected_papa' => 0,
            'papa_change' => 0,
            'current_progress' => round($student->progress_percentage ?? 0, 2),
            'projected_progress' => 0,
            'progress_change' => 0,
            'credit_impact' => [
                'affected_credits' => 0,
                'credits_details' => []
            ],
            'issues' => []
        ];
        
        $passedSubjects = $student->subjects->keyBy('code');
        $currentSubjects = $student->currentSubjects->keyBy('subject_code');
        $allSubjects = Subject::with('prerequisites', 'requiredFor')->get()->keyBy('code');
        $studentCurrentSemester = $this->getCurrentSemester($student->progress_percentage);
        
        // Check if there are any 'added' or 'removed' changes that affect career credits
        $hasCareerCreditsChange = false;
        foreach ($changes as $change) {
            if ($change['type'] === 'added') {
                $addedType = $change['new_value']['type'] ?? 'profesional';
                if ($addedType !== 'nivelacion') {
                    $hasCareerCreditsChange = true;
                    break;
                }
            } elseif ($change['type'] === 'removed') {
                $subjectCode = $change['subject_code'];
                if (isset($allSubjects[$subjectCode])) {
                    $removedSubject = $allSubjects[$subjectCode];
                    if ($removedSubject->type !== 'nivelacion') {
                        $hasCareerCreditsChange = true;
                        break;
                    }
                }
            }
        }
        
        // If there are career credit changes, recalculate progress for this student
        if ($hasCareerCreditsChange) {
            $impact['has_impact'] = true;
            
            // Current career credits (total in the curriculum, excluding leveling)
            $currentCareerCredits = $allSubjects->where('type', '!=', 'nivelacion')->sum('credits');
            
            // Student's passed credits (from DB, already calculated correctly)
            // We can derive this from the current_progress
            $studentPassedCredits = ($impact['current_progress'] / 100) * $currentCareerCredits;
            
            // Projected career credits = current + added - removed (only non-leveling)
            $projectedCareerCredits = $currentCareerCredits;
            $projectedPassedCredits = $studentPassedCredits;
            
            // Apply all changes
            foreach ($changes as $change) {
                if ($change['type'] === 'added') {
                    $addedType = $change['new_value']['type'] ?? 'profesional';
                    $addedCredits = intval($change['new_value']['credits'] ?? 0);
                    if ($addedType !== 'nivelacion') {
                        // Adding a subject: total credits increase, passed credits stay the same
                        $projectedCareerCredits += $addedCredits;
                    }
                } elseif ($change['type'] === 'removed') {
                    $subjectCode = $change['subject_code'];
                    if (isset($allSubjects[$subjectCode])) {
                        $removedSubject = $allSubjects[$subjectCode];
                        if ($removedSubject->type !== 'nivelacion') {
                            $removedCredits = $removedSubject->credits ?? 0;
                            // Removing a subject: total credits decrease, but passed credits stay
                            // Students keep their approved credits in their academic history
                            $projectedCareerCredits -= $removedCredits;
                            
                            // NOTE: We do NOT decrease projectedPassedCredits
                            // Students who passed this subject keep their credits
                            // The university cannot remove credits from their academic record
                        }
                    }
                }
            }
            
            // Calculate projected progress: passed / projected_total
            if ($projectedCareerCredits > 0) {
                $projectedProgress = ($projectedPassedCredits / $projectedCareerCredits) * 100;
                
                $impact['projected_progress'] = round($projectedProgress, 2);
                $impact['progress_change'] = round($projectedProgress - $impact['current_progress'], 2);
                
                // Debug info
                \Log::info("Progress calculation for student {$student->document}", [
                    'current_progress_from_db' => $impact['current_progress'],
                    'student_passed_credits' => round($studentPassedCredits, 2),
                    'current_career_credits' => $currentCareerCredits,
                    'projected_passed_credits' => round($projectedPassedCredits, 2),
                    'projected_career_credits' => $projectedCareerCredits,
                    'projected_progress' => $impact['projected_progress'],
                    'progress_change' => $impact['progress_change']
                ]);
            }
        }
        
        // Track subjects affected by changes
        $affectedSubjectCodes = [];
        
        foreach ($changes as $change) {
            $subjectCode = $change['subject_code'];
            
            // For 'added' changes, the subject won't exist in $allSubjects yet
            // For 'removed' and other changes, the subject should exist
            if (!isset($allSubjects[$subjectCode]) && $change['type'] !== 'added') {
                continue;
            }
            
            $subject = isset($allSubjects[$subjectCode]) ? $allSubjects[$subjectCode] : null;
            
            // Check if student has taken this subject
            $studentHasSubject = $subject ? isset($passedSubjects[$subjectCode]) : false;
            $studentTakingSubject = isset($currentSubjects[$subjectCode]);
            
            if ($change['type'] === 'semester' && $subject) {
                $newSemester = intval($change['new_value']);
                $oldSemester = intval($change['old_value']);
                
                // Check if student is currently taking this subject
                if ($studentTakingSubject) {
                    $impact['has_impact'] = true;
                    $impact['has_delay'] = true;
                    $impact['issues'][] = "Está cursando {$subject->name} actualmente. Moverla al semestre {$newSemester} causaría retraso inmediato.";
                }
                
                // Check if student passed this subject and it affects their sequence
                if ($studentHasSubject) {
                    $dependentSubjects = $subject->requiredFor;
                    
                    foreach ($dependentSubjects as $dependent) {
                        if (isset($currentSubjects[$dependent->code])) {
                            $impact['has_impact'] = true;
                            $impact['has_prerequisite_issues'] = true;
                            $impact['issues'][] = "Está cursando {$dependent->name} que requiere {$subject->name}. El cambio afectaría la validez de su inscripción actual.";
                        }
                        
                        // Check if moving prerequisite to later semester blocks planned progression
                        if ($dependent->semester <= $newSemester && $dependent->semester > $oldSemester) {
                            $impact['has_impact'] = true;
                            $impact['has_delay'] = true;
                            $impact['issues'][] = "Mover {$subject->name} al semestre {$newSemester} bloquearía {$dependent->name} (semestre {$dependent->semester}) en su progresión normal.";
                        }
                    }
                }
                
                // Check if student needs this subject for next semester
                if (!$studentHasSubject && !$studentTakingSubject) {
                    $canTakeNow = $this->canStudentTakeSubject($student, $subject, $passedSubjects);
                    $shouldTakeThisSemester = $subject->semester <= $studentCurrentSemester + 1;
                    
                    if ($canTakeNow && $shouldTakeThisSemester && $newSemester > $studentCurrentSemester + 1) {
                        $impact['has_impact'] = true;
                        $impact['has_delay'] = true;
                        $impact['issues'][] = "Debería tomar {$subject->name} pronto (semestre {$subject->semester}). Moverla al semestre {$newSemester} retrasaría su graduación.";
                    }
                }
            }
            
            if ($change['type'] === 'prerequisites' && $subject) {
                $newPrereqs = explode(',', $change['new_value']);
                $oldPrereqs = explode(',', $change['old_value']);
                $newPrereqs = array_map('trim', array_filter($newPrereqs));
                $oldPrereqs = array_map('trim', array_filter($oldPrereqs));
                
                // Check if student is currently taking this subject
                if ($studentTakingSubject) {
                    $missingPrereqs = array_diff($newPrereqs, $passedSubjects->keys()->toArray());
                    
                    if (!empty($missingPrereqs)) {
                        $impact['has_impact'] = true;
                        $impact['has_prerequisite_issues'] = true;
                        $missingNames = collect($missingPrereqs)->map(function($code) use ($allSubjects) {
                            return $allSubjects[$code]->name ?? $code;
                        })->implode(', ');
                        $impact['issues'][] = "Está cursando {$subject->name} pero le faltarían prerrequisitos: {$missingNames}";
                    }
                }
                
                // Check if student was planning to take this subject next semester
                $canTakeWithOldPrereqs = $this->canTakeWithPrerequisites($passedSubjects->keys()->toArray(), $oldPrereqs);
                $canTakeWithNewPrereqs = $this->canTakeWithPrerequisites($passedSubjects->keys()->toArray(), $newPrereqs);
                $shouldTakeSoon = $subject->semester <= $studentCurrentSemester + 1;
                
                if ($canTakeWithOldPrereqs && !$canTakeWithNewPrereqs && $shouldTakeSoon) {
                    $impact['has_impact'] = true;
                    $impact['has_prerequisite_issues'] = true;
                    $missingFromNew = array_diff($newPrereqs, $passedSubjects->keys()->toArray());
                    $missingNames = collect($missingFromNew)->map(function($code) use ($allSubjects) {
                        return $allSubjects[$code]->name ?? $code;
                    })->implode(', ');
                    $impact['issues'][] = "Podría tomar {$subject->name} próximamente, pero los nuevos prerrequisitos ({$missingNames}) lo bloquearían";
                }
                
                // Check if additional prerequisites block future progression
                $addedPrereqs = array_diff($newPrereqs, $oldPrereqs);
                if (!empty($addedPrereqs)) {
                    $missingFromAdded = array_diff($addedPrereqs, $passedSubjects->keys()->toArray());
                    
                    if (!empty($missingFromAdded) && $subject->semester <= $studentCurrentSemester + 2) {
                        $impact['has_impact'] = true;
                        $impact['has_delay'] = true;
                        $missingNames = collect($missingFromAdded)->map(function($code) use ($allSubjects) {
                            return $allSubjects[$code]->name ?? $code;
                        })->implode(', ');
                        $impact['issues'][] = "Los nuevos prerrequisitos para {$subject->name} ({$missingNames}) retrasarían su progresión";
                    }
                }
                
                // Check if removing prerequisites creates new opportunities
                $removedPrereqs = array_diff($oldPrereqs, $newPrereqs);
                if (!empty($removedPrereqs) && !$studentTakingSubject && !$studentHasSubject) {
                    $couldTakeEarlier = $subject->semester > $studentCurrentSemester && 
                                       $this->canTakeWithPrerequisites($passedSubjects->keys()->toArray(), $newPrereqs);
                    
                    if ($couldTakeEarlier) {
                        $impact['has_impact'] = true;
                        $impact['issues'][] = "Podría tomar {$subject->name} antes de lo planeado debido a menores prerrequisitos";
                    }
                }
            }
            
            // Handle subject additions - add descriptive message
            if ($change['type'] === 'added') {
                $addedType = isset($change['new_value']['type']) ? $change['new_value']['type'] : 'profesional';
                $addedCredits = isset($change['new_value']['credits']) ? intval($change['new_value']['credits']) : 0;
                $subjectName = isset($change['new_value']['name']) ? $change['new_value']['name'] : $subjectCode;
                
                if ($addedType !== 'nivelacion') {
                    $impact['issues'][] = "✨ Nueva materia: {$subjectName} ({$addedCredits} créditos) agregada a la malla.";
                } else {
                    $impact['issues'][] = "✨ Nueva materia de nivelación: {$subjectName} ({$addedCredits} créditos) agregada.";
                }
            }
            
            // Handle subject removals - add descriptive message
            if ($change['type'] === 'removed' && $subject) {
                $removedType = $subject->type ?? 'profesional';
                $removedCredits = $subject->credits ?? 0;
                
                if ($removedType !== 'nivelacion') {
                    if ($studentHasSubject) {
                        $impact['issues'][] = "✅ Ya aprobó {$subject->name} ({$removedCredits} créditos) que será eliminada. Sus créditos permanecen en su historial académico, aumentando su porcentaje de avance.";
                    } else {
                        $impact['issues'][] = "� Materia {$subject->name} ({$removedCredits} créditos) eliminada de la malla. Al reducir créditos totales, su porcentaje de avance aumenta.";
                    }
                } else {
                    $impact['issues'][] = "🗑️ Materia de nivelación {$subject->name} eliminada (no afecta avance de carrera).";
                }
            }
            
            // Track if this change affects subjects the student has taken
            if ($studentHasSubject && $subject) {
                $affectedSubjectCodes[] = $subjectCode;
            }
        }
        
        // Calculate PAPA and Progress impact if student has affected subjects
        if (!empty($affectedSubjectCodes) || $impact['has_impact']) {
            // For simulation purposes, we'll recalculate assuming changes are applied
            // In reality, we'd need to simulate the modified curriculum
            // For now, we note that credits from affected subjects might change distribution
            
            // Get student's current distribution
            $currentDistribution = $this->creditService->calculateDistribution($student->document);
            
            // Only set projected values if they weren't already calculated
            // (e.g., by 'added' or 'removed' handlers)
            if ($impact['projected_papa'] === 0) {
                $impact['projected_papa'] = $impact['current_papa'];
            }
            if ($impact['projected_progress'] === 0 && $impact['current_progress'] > 0) {
                $impact['projected_progress'] = $impact['current_progress'];
            }
            
            // Calculate changes
            $impact['papa_change'] = round($impact['projected_papa'] - $impact['current_papa'], 2);
            $impact['progress_change'] = round($impact['projected_progress'] - $impact['current_progress'], 2);
            
            // Add credit impact details
            $impact['credit_impact'] = [
                'affected_credits' => count($affectedSubjectCodes) > 0 
                    ? DB::table('student_subject')
                        ->where('student_document', $student->document)
                        ->whereIn('subject_code', $affectedSubjectCodes)
                        ->sum('effective_credits')
                    : 0,
                'credits_details' => $currentDistribution['component_usage'] ?? []
            ];
        }
        
        return $impact;
    }
    
    /**
     * Get current semester based on progress percentage
     */
    private function getCurrentSemester($progressPercentage)
    {
        if ($progressPercentage >= 90) return 10;
        if ($progressPercentage >= 80) return 9;
        if ($progressPercentage >= 70) return 8;
        if ($progressPercentage >= 60) return 7;
        if ($progressPercentage >= 50) return 6;
        if ($progressPercentage >= 40) return 5;
        if ($progressPercentage >= 30) return 4;
        if ($progressPercentage >= 20) return 3;
        if ($progressPercentage >= 10) return 2;
        return 1;
    }
    
    /**
     * Check if student can take a subject with given prerequisites
     */
    private function canTakeWithPrerequisites($passedSubjects, $prerequisites)
    {
        if (empty($prerequisites)) {
            return true;
        }
        
        foreach ($prerequisites as $prereq) {
            if (!in_array($prereq, $passedSubjects)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Apply simulated changes to subjects without modifying database
     */
    private function applySimulatedChanges($subjects, array $changes)
    {
        $simulatedSubjects = $subjects->toArray();
        
        foreach ($changes as $change) {
            $subjectCode = $change['subject_code'];
            
            if (isset($simulatedSubjects[$subjectCode])) {
                // Change semester if specified
                if (isset($change['new_semester'])) {
                    $simulatedSubjects[$subjectCode]['semester'] = $change['new_semester'];
                }
                
                // Change prerequisites if specified
                if (isset($change['new_prerequisites'])) {
                    $simulatedSubjects[$subjectCode]['prerequisites'] = $change['new_prerequisites'];
                }
            }
        }
        
        return collect($simulatedSubjects);
    }
    
    /**
     * Check if student can take a subject with current prerequisites
     */
    private function canStudentTakeSubject(Student $student, $subject, $passedSubjects)
    {
        $prerequisites = $subject->prerequisites ?? [];
        
        if (empty($prerequisites)) {
            return true;
        }
        
        foreach ($prerequisites as $prereq) {
            if (!isset($passedSubjects[$prereq['code']])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check for semester-related issues (gaps and delays)
     */
    private function checkSemesterIssues(Student $student, $simulatedSubjects, $passedSubjects)
    {
        $issues = [
            'has_gaps' => false,
            'has_delays' => false,
            'gap_issues' => [],
            'delay_issues' => []
        ];
        
        // Analyze student's progression through semesters
        $studentProgress = $this->analyzeStudentProgression($student, $simulatedSubjects);
        
        // Check for gaps (semesters where student can't take any subject)
        $gaps = $this->findProgressionGaps($studentProgress, $passedSubjects);
        if (!empty($gaps)) {
            $issues['has_gaps'] = true;
            $issues['gap_issues'] = $gaps;
        }
        
        // Check for delays (additional semesters needed)
        $delays = $this->findProgressionDelays($studentProgress, $passedSubjects);
        if (!empty($delays)) {
            $issues['has_delays'] = true;
            $issues['delay_issues'] = $delays;
        }
        
        return $issues;
    }
    
    /**
     * Analyze student progression through simulated curriculum
     */
    private function analyzeStudentProgression(Student $student, $simulatedSubjects)
    {
        // This would contain complex logic to simulate student progression
        // For now, return a simplified analysis
        return [
            'can_graduate_on_time' => true,
            'additional_semesters_needed' => 0,
            'blocked_subjects' => []
        ];
    }
    
    /**
     * Find gaps in student progression
     */
    private function findProgressionGaps($progression, $passedSubjects)
    {
        $gaps = [];
        
        // Simplified gap detection logic
        if (!empty($progression['blocked_subjects'])) {
            $gaps[] = "Materias bloqueadas que podrían crear huecos en la progresión";
        }
        
        return $gaps;
    }
    
    /**
     * Find delays in student progression
     */
    private function findProgressionDelays($progression, $passedSubjects)
    {
        $delays = [];
        
        if ($progression['additional_semesters_needed'] > 0) {
            $delays[] = "Necesita {$progression['additional_semesters_needed']} semestre(s) adicional(es)";
        }
        
        return $delays;
    }

    /**
     * Get all curriculum versions
     */
    public function getVersions()
    {
        $versions = \App\Models\CurriculumVersion::orderBy('version_number', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'versions' => $versions
        ]);
    }

    /**
     * Get a specific curriculum version
     */
    public function getVersion($id)
    {
        $version = \App\Models\CurriculumVersion::with('subjects')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'version' => $version
        ]);
    }

    /**
     * Save current curriculum as a new version
     * LOGIC: Saves the PREVIOUS state as a historical version,
     * and keeps the CURRENT modified state as active
     */
    public function saveVersion(Request $request)
    {
        $request->validate([
            'curriculum_data' => 'required|array',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // STEP 1: Capture the CURRENT state from database (before changes)
            $previousState = [
                'subjects' => []
            ];
            
            $currentSubjects = Subject::with(['prerequisites', 'requiredFor'])->get();
            foreach ($currentSubjects as $subject) {
                $previousState['subjects'][] = [
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'semester' => $subject->semester,
                    'credits' => $subject->credits,
                    'classroom_hours' => $subject->classroom_hours,
                    'student_hours' => $subject->student_hours,
                    'type' => $subject->type,
                    'is_required' => $subject->is_required,
                    'description' => $subject->description,
                    'display_order' => $subject->display_order,
                    'prerequisites' => $subject->prerequisites->pluck('code')->toArray(),
                ];
            }

            // Get next version number
            $versionNumber = \App\Models\CurriculumVersion::getNextVersionNumber();

            // STEP 2: Save the PREVIOUS state as a historical version
            $version = \App\Models\CurriculumVersion::create([
                'version_number' => $versionNumber,
                'user_id' => auth()->id(),
                'description' => $request->description ?: 'Versión histórica antes de cambios',
                'is_current' => false, // Historical version
                'curriculum_data' => $previousState,
            ]);

            // Save subjects for this historical version
            foreach ($previousState['subjects'] as $subjectData) {
                \App\Models\CurriculumVersionSubject::create([
                    'curriculum_version_id' => $version->id,
                    'code' => $subjectData['code'],
                    'name' => $subjectData['name'],
                    'semester' => $subjectData['semester'],
                    'credits' => $subjectData['credits'] ?? 3,
                    'classroom_hours' => $subjectData['classroom_hours'] ?? 3,
                    'student_hours' => $subjectData['student_hours'] ?? 6,
                    'type' => $subjectData['type'] ?? 'profesional',
                    'is_required' => $subjectData['is_required'] ?? true,
                    'description' => $subjectData['description'] ?? null,
                    'display_order' => $subjectData['display_order'] ?? 0,
                    'prerequisites' => $subjectData['prerequisites'] ?? [],
                ]);
            }

            // STEP 3: Apply the NEW changes to the main curriculum (this becomes active)
            $this->applyChangesToMainCurriculum($request->curriculum_data);

            DB::commit();

            return response()->json([
                'success' => true,
                'version' => $version,
                'message' => "Cambios aplicados. Versión anterior guardada como {$versionNumber}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la versión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply changes from simulation to main curriculum
     */
    private function applyChangesToMainCurriculum($curriculumData)
    {
        $changes = $curriculumData['changes'] ?? [];
        
        \Log::info("📦 Aplicando cambios al currículum principal", [
            'total_changes' => count($changes),
            'changes' => $changes
        ]);
        
        // Process changes - the frontend sends an array with 'type' field
        foreach ($changes as $change) {
            $subjectCode = $change['subject_code'] ?? null;
            $changeType = $change['type'] ?? null;
            
            if (!$subjectCode || !$changeType) {
                \Log::warning("⚠️ Cambio inválido (sin código o tipo)", ['change' => $change]);
                continue;
            }
            
            if ($changeType === 'added') {
                // Find the subject data in the subjects array
                $subjectInfo = collect($curriculumData['subjects'] ?? [])->firstWhere('code', $subjectCode);
                
                if ($subjectInfo) {
                    $subjectData = [
                        'name' => $subjectInfo['name'] ?? 'Nueva Materia',
                        'semester' => $subjectInfo['semester'] ?? 1,
                        'credits' => $subjectInfo['credits'] ?? 3,
                        'classroom_hours' => $subjectInfo['classroom_hours'] ?? 3,
                        'student_hours' => $subjectInfo['student_hours'] ?? 6,
                        'type' => $subjectInfo['type'] ?? 'profesional',
                        'is_required' => $subjectInfo['is_required'] ?? true,
                        'description' => $subjectInfo['description'] ?? null,
                        'display_order' => $subjectInfo['display_order'] ?? 0,
                    ];
                    
                    // Add new subject
                    Subject::updateOrCreate(
                        ['code' => $subjectCode],
                        $subjectData
                    );
                    
                    // If it's a leveling subject, also add to leveling_subjects table
                    if (($subjectInfo['type'] ?? 'profesional') === 'nivelacion') {
                        \App\Models\LevelingSubject::updateOrCreate(
                            ['code' => $subjectCode],
                            [
                                'name' => $subjectData['name'],
                                'credits' => $subjectData['credits'],
                                'classroom_hours' => $subjectData['classroom_hours'],
                                'student_hours' => $subjectData['student_hours'],
                                'description' => $subjectData['description'],
                            ]
                        );
                    }
                    
                    \Log::info("✅ Materia agregada: {$subjectCode} - {$subjectData['name']}");
                } else {
                    \Log::warning("⚠️ Materia agregada no encontrada en subjects array: {$subjectCode}");
                }
            } elseif ($changeType === 'removed') {
                // Delete subject from database
                $subject = Subject::where('code', $subjectCode)->first();
                
                if ($subject) {
                    \Log::info("🗑️ Eliminando materia: {$subjectCode} - {$subject->name}");
                    
                    // Delete prerequisites relationships
                    $subject->prerequisites()->detach();
                    $subject->requiredFor()->detach();
                    
                    // Delete the subject
                    $deletedCount = $subject->delete();
                    
                    if ($deletedCount > 0) {
                        \Log::info("✅ Materia eliminada exitosamente: {$subjectCode}");
                    }
                } else {
                    \Log::warning("⚠️ Materia no encontrada para eliminar: {$subjectCode}");
                }
                
                // Note: We don't delete from leveling_subjects - they remain for historical records
            } elseif ($changeType === 'semester') {
                // Update subject semester
                $subject = Subject::where('code', $subjectCode)->first();
                $subjectInfo = collect($curriculumData['subjects'] ?? [])->firstWhere('code', $subjectCode);
                
                if ($subject && $subjectInfo) {
                    $oldSemester = $subject->semester;
                    $newSemester = $subjectInfo['semester'];
                    
                    $subject->update([
                        'semester' => $newSemester,
                        'display_order' => $subjectInfo['display_order'] ?? $subject->display_order,
                    ]);
                    
                    \Log::info("🔄 Semestre cambiado: {$subjectCode} - {$oldSemester} → {$newSemester}");
                } else {
                    \Log::warning("⚠️ Materia no encontrada para cambio de semestre: {$subjectCode}");
                }
            } elseif ($changeType === 'prerequisites') {
                // Update prerequisites
                $subject = Subject::where('code', $subjectCode)->first();
                $subjectInfo = collect($curriculumData['subjects'] ?? [])->firstWhere('code', $subjectCode);
                
                if ($subject && $subjectInfo) {
                    $newPrereqs = $subjectInfo['prerequisites'] ?? [];
                    $prereqIds = Subject::whereIn('code', $newPrereqs)->pluck('id')->toArray();
                    
                    $subject->prerequisites()->sync($prereqIds);
                    
                    \Log::info("🔗 Prerrequisitos actualizados: {$subjectCode} - " . implode(', ', $newPrereqs));
                } else {
                    \Log::warning("⚠️ Materia no encontrada para cambio de prerrequisitos: {$subjectCode}");
                }
            } elseif ($changeType === 'modified') {
                // Update subject data
                $subject = Subject::where('code', $subjectCode)->first();
                $subjectInfo = collect($curriculumData['subjects'] ?? [])->firstWhere('code', $subjectCode);
                
                if ($subject && $subjectInfo) {
                    $updateData = [];
                    
                    // Only update fields that are present in subjectInfo
                    if (isset($subjectInfo['name'])) $updateData['name'] = $subjectInfo['name'];
                    if (isset($subjectInfo['credits'])) $updateData['credits'] = $subjectInfo['credits'];
                    if (isset($subjectInfo['semester'])) $updateData['semester'] = $subjectInfo['semester'];
                    if (isset($subjectInfo['type'])) $updateData['type'] = $subjectInfo['type'];
                    if (isset($subjectInfo['description'])) $updateData['description'] = $subjectInfo['description'];
                    if (isset($subjectInfo['display_order'])) $updateData['display_order'] = $subjectInfo['display_order'];
                    
                    $subject->update($updateData);
                    
                    \Log::info("✏️ Materia modificada: {$subjectCode} - " . json_encode($updateData));
                } else {
                    \Log::warning("⚠️ Materia no encontrada para modificación: {$subjectCode}");
                }
            }
        }

        // Update display_order and semester for all subjects based on curriculum data
        $subjects = $curriculumData['subjects'] ?? [];
        foreach ($subjects as $subjectData) {
            $code = $subjectData['code'] ?? null;
            if (!$code) continue;
            
            Subject::where('code', $code)
                ->update([
                    'display_order' => $subjectData['display_order'] ?? 0,
                    'semester' => $subjectData['semester']
                ]);
        }
        
        \Log::info("✅ Cambios aplicados exitosamente");
    }

    /**
     * Delete a curriculum version
     */
    public function deleteVersion($id)
    {
        try {
            $version = \App\Models\CurriculumVersion::findOrFail($id);
            
            // Prevent deleting the current version if it's marked as current
            if ($version->is_current) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar la versión actual activa.'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Delete related curriculum_version_subjects
            \App\Models\CurriculumVersionSubject::where('curriculum_version_id', $version->id)->delete();
            
            // Delete the version
            $version->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Versión {$version->version_number} eliminada correctamente"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la versión: ' . $e->getMessage()
            ], 500);
        }
    }
}

