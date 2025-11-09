<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentCurrentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimulationController extends Controller
{
    /**
     * Analyze the impact of curriculum changes on students
     */
    public function analyzeImpact(Request $request)
    {
        $changes = $request->input('changes', []);
        
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
            'affected_percentage' => 0,
            'details' => []
        ];
        
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
                
                $impactAnalysis['details'][] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'current_semester' => $this->getCurrentSemester($student->progress_percentage),
                    'current_subjects' => $student->currentSubjects->pluck('subject_code')->toArray(),
                    'issues' => $impact['issues']
                ];
            }
        }
        
        $impactAnalysis['affected_percentage'] = $impactAnalysis['total_students'] > 0 
            ? round(($impactAnalysis['affected_students'] / $impactAnalysis['total_students']) * 100, 1)
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
            'issues' => []
        ];
        
        $passedSubjects = $student->subjects->keyBy('code');
        $currentSubjects = $student->currentSubjects->keyBy('subject_code');
        $allSubjects = Subject::with('prerequisites', 'requiredFor')->get()->keyBy('code');
        $studentCurrentSemester = $this->getCurrentSemester($student->progress_percentage);
        
        foreach ($changes as $change) {
            $subjectCode = $change['subject_code'];
            
            if (!isset($allSubjects[$subjectCode])) {
                continue;
            }
            
            $subject = $allSubjects[$subjectCode];
            
            if ($change['type'] === 'semester') {
                $newSemester = intval($change['new_value']);
                $oldSemester = intval($change['old_value']);
                
                // Check if student is currently taking this subject
                if (isset($currentSubjects[$subjectCode])) {
                    $impact['has_impact'] = true;
                    $impact['has_delay'] = true;
                    $impact['issues'][] = "Está cursando {$subject->name} actualmente. Moverla al semestre {$newSemester} causaría retraso inmediato.";
                }
                
                // Check if student passed this subject and it affects their sequence
                if (isset($passedSubjects[$subjectCode])) {
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
                if (!isset($passedSubjects[$subjectCode]) && !isset($currentSubjects[$subjectCode])) {
                    $canTakeNow = $this->canStudentTakeSubject($student, $subject, $passedSubjects);
                    $shouldTakeThisSemester = $subject->semester <= $studentCurrentSemester + 1;
                    
                    if ($canTakeNow && $shouldTakeThisSemester && $newSemester > $studentCurrentSemester + 1) {
                        $impact['has_impact'] = true;
                        $impact['has_delay'] = true;
                        $impact['issues'][] = "Debería tomar {$subject->name} pronto (semestre {$subject->semester}). Moverla al semestre {$newSemester} retrasaría su graduación.";
                    }
                }
            }
            
            if ($change['type'] === 'prerequisites') {
                $newPrereqs = explode(',', $change['new_value']);
                $oldPrereqs = explode(',', $change['old_value']);
                $newPrereqs = array_map('trim', array_filter($newPrereqs));
                $oldPrereqs = array_map('trim', array_filter($oldPrereqs));
                
                // Check if student is currently taking this subject
                if (isset($currentSubjects[$subjectCode])) {
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
                if (!empty($removedPrereqs) && !isset($currentSubjects[$subjectCode]) && !isset($passedSubjects[$subjectCode])) {
                    $couldTakeEarlier = $subject->semester > $studentCurrentSemester && 
                                       $this->canTakeWithPrerequisites($passedSubjects->keys()->toArray(), $newPrereqs);
                    
                    if ($couldTakeEarlier) {
                        $impact['has_impact'] = true;
                        $impact['issues'][] = "Podría tomar {$subject->name} antes de lo planeado debido a menores prerrequisitos";
                    }
                }
            }
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
     */
    public function saveVersion(Request $request)
    {
        $request->validate([
            'curriculum_data' => 'required|array',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Get next version number
            $versionNumber = \App\Models\CurriculumVersion::getNextVersionNumber();

            // Create new version
            $version = \App\Models\CurriculumVersion::create([
                'version_number' => $versionNumber,
                'user_id' => auth()->id(),
                'description' => $request->description,
                'is_current' => true,
                'curriculum_data' => $request->curriculum_data,
            ]);

            // Save subjects for this version
            $subjects = $request->curriculum_data['subjects'] ?? [];
            foreach ($subjects as $subjectData) {
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

            // Update main subjects table with changes if needed
            $this->applyChangesToMainCurriculum($request->curriculum_data);

            DB::commit();

            return response()->json([
                'success' => true,
                'version' => $version,
                'message' => "Malla guardada como versión {$versionNumber}"
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
        
        foreach ($changes as $subjectCode => $changeData) {
            if ($changeData['action'] === 'added') {
                // Add new subject
                Subject::updateOrCreate(
                    ['code' => $subjectCode],
                    [
                        'name' => $changeData['data']['name'] ?? 'Nueva Materia',
                        'semester' => $changeData['data']['semester'] ?? 1,
                        'credits' => $changeData['data']['credits'] ?? 3,
                        'classroom_hours' => $changeData['data']['classroom_hours'] ?? 3,
                        'student_hours' => $changeData['data']['student_hours'] ?? 6,
                        'type' => $changeData['data']['type'] ?? 'profesional',
                        'is_required' => $changeData['data']['is_required'] ?? true,
                        'description' => $changeData['data']['description'] ?? null,
                    ]
                );
            } elseif ($changeData['action'] === 'removed') {
                // Mark as removed or delete
                Subject::where('code', $subjectCode)->delete();
            } elseif ($changeData['action'] === 'modified') {
                // Update subject
                $subject = Subject::where('code', $subjectCode)->first();
                if ($subject) {
                    $subject->update($changeData['data']);
                }
            }
        }

        // Update display_order for all subjects based on curriculum data
        $subjects = $curriculumData['subjects'] ?? [];
        foreach ($subjects as $subjectData) {
            Subject::where('code', $subjectData['code'])
                ->update([
                    'display_order' => $subjectData['display_order'] ?? 0,
                    'semester' => $subjectData['semester']
                ]);
        }
    }
}
