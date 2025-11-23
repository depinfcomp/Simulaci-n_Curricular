<?php

namespace App\Services;

use App\Models\ConvalidationSimulation;
use App\Models\ConvalidationEquivalenceRule;
use App\Models\SimulationStudentResult;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ExternalSubject;
use App\Models\ExternalSubjectComponent;
use App\Services\CreditDistributionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ConvalidationSimulationService
 * 
 * Handles the core logic for simulating student transfers between curricula.
 * Calculates how students' progress would change based on N:N subject equivalences
 * and component-based credit distribution.
 */
class ConvalidationSimulationService
{
    private CreditDistributionService $creditService;
    private int $totalProgramCredits = 167; // Default program credits

    public function __construct(CreditDistributionService $creditService = null)
    {
        $this->creditService = $creditService ?? new CreditDistributionService();
    }

    /**
     * Create a new simulation.
     * 
     * @param array $data Simulation configuration
     * @return ConvalidationSimulation
     */
    public function createSimulation(array $data): ConvalidationSimulation
    {
        return ConvalidationSimulation::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'original_curriculum_id' => $data['original_curriculum_id'] ?? null,
            'new_curriculum_id' => $data['new_curriculum_id'],
            'status' => ConvalidationSimulation::STATUS_DRAFT,
            'configuration' => $data['configuration'] ?? [],
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Add equivalence rules to a simulation.
     * 
     * @param ConvalidationSimulation $simulation
     * @param array $rules Array of equivalence rules
     * @return int Number of rules created
     */
    public function addEquivalenceRules(ConvalidationSimulation $simulation, array $rules): int
    {
        $created = 0;

        foreach ($rules as $rule) {
            ConvalidationEquivalenceRule::create([
                'simulation_id' => $simulation->id,
                'original_subject_type' => $rule['original_subject_type'],
                'original_subject_code' => $rule['original_subject_code'],
                'new_subject_type' => $rule['new_subject_type'],
                'new_subject_code' => $rule['new_subject_code'],
                'equivalence_type' => $rule['equivalence_type'] ?? ConvalidationEquivalenceRule::TYPE_DIRECT,
                'notes' => $rule['notes'] ?? null,
                'created_by' => $rule['created_by'] ?? null,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Run the simulation for all students.
     * 
     * @param ConvalidationSimulation $simulation
     * @return array Summary of results
     */
    public function runSimulation(ConvalidationSimulation $simulation): array
    {
        Log::info("Starting simulation #{$simulation->id}: {$simulation->name}");

        // Clear previous results if any
        $simulation->studentResults()->delete();

        // Get all students with their approved subjects
        $students = Student::with(['subjects' => function($query) {
            $query->wherePivot('status', 'passed');
        }])->get();

        $processedCount = 0;
        $errors = [];

        foreach ($students as $student) {
            try {
                $this->processStudentForSimulation($simulation, $student);
                $processedCount++;
            } catch (\Exception $e) {
                Log::error("Error processing student {$student->document} in simulation #{$simulation->id}: " . $e->getMessage());
                $errors[] = [
                    'student_document' => $student->document,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Update simulation status and summary stats
        $simulation->markAsCompleted();
        $simulation->updateSummaryStats();

        Log::info("Simulation #{$simulation->id} completed. Processed {$processedCount} students.");

        return [
            'processed' => $processedCount,
            'errors' => $errors,
            'summary' => $simulation->summary_stats
        ];
    }

    /**
     * Process a single student for the simulation.
     * 
     * @param ConvalidationSimulation $simulation
     * @param Student $student
     * @return SimulationStudentResult
     */
    private function processStudentForSimulation(ConvalidationSimulation $simulation, Student $student): SimulationStudentResult
    {
        // Get student's current metrics
        $originalProgress = (float)$student->progress_percentage;
        $originalApprovedCredits = (int)$student->approved_credits;
        $originalDistribution = $this->creditService->calculateDistribution($student->document);

        // Get student's passed subjects
        $passedSubjects = $student->subjects()
            ->wherePivot('status', 'passed')
            ->get();

        // Calculate equivalences in new curriculum
        $equivalenceResult = $this->calculateEquivalences($simulation, $passedSubjects);

        // Calculate new progress based on equivalences
        $newMetrics = $this->calculateNewProgress(
            $simulation,
            $equivalenceResult,
            $originalDistribution
        );

        // Create result record
        return SimulationStudentResult::create([
            'simulation_id' => $simulation->id,
            'student_document' => $student->document,
            'original_progress_percentage' => $originalProgress,
            'original_approved_credits' => $originalApprovedCredits,
            'original_component_distribution' => $originalDistribution['component_usage'] ?? [],
            'new_progress_percentage' => $newMetrics['new_progress'],
            'new_approved_credits' => $newMetrics['new_credits'],
            'new_component_distribution' => $newMetrics['new_distribution'],
            'progress_change' => $newMetrics['new_progress'] - $originalProgress,
            'credits_lost' => $newMetrics['credits_lost'],
            'credits_gained' => $newMetrics['credits_gained'],
            'convalidated_subjects' => $equivalenceResult['convalidated'],
            'non_convalidated_subjects' => $equivalenceResult['non_convalidated'],
            'impact_details' => $newMetrics['impact_details'],
        ]);
    }

    /**
     * Calculate which subjects from original curriculum have equivalences in new curriculum.
     * 
     * @param ConvalidationSimulation $simulation
     * @param \Illuminate\Support\Collection $passedSubjects
     * @return array
     */
    private function calculateEquivalences(ConvalidationSimulation $simulation, $passedSubjects): array
    {
        $convalidated = [];
        $nonConvalidated = [];

        foreach ($passedSubjects as $subject) {
            // Find equivalence rules for this subject
            $equivalences = ConvalidationEquivalenceRule::findEquivalencesForOriginal(
                $simulation->id,
                $subject->code
            );

            if ($equivalences->isEmpty()) {
                // No equivalence found
                $nonConvalidated[] = [
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'credits' => $subject->credits,
                    'component' => $subject->type,
                ];
            } else {
                // Has equivalence(s)
                $convalidated[] = [
                    'code' => $subject->code,
                    'name' => $subject->name,
                    'credits' => $subject->credits,
                    'component' => $subject->type,
                    'equivalences' => $equivalences->map(function($eq) {
                        return [
                            'new_code' => $eq->new_subject_code,
                            'equivalence_type' => $eq->equivalence_type,
                        ];
                    })->toArray()
                ];
            }
        }

        return [
            'convalidated' => $convalidated,
            'non_convalidated' => $nonConvalidated,
        ];
    }

    /**
     * Calculate new progress percentage based on equivalences.
     * 
     * @param ConvalidationSimulation $simulation
     * @param array $equivalenceResult
     * @param array $originalDistribution
     * @return array
     */
    private function calculateNewProgress(
        ConvalidationSimulation $simulation,
        array $equivalenceResult,
        array $originalDistribution
    ): array {
        $newDistribution = [];
        $totalNewCredits = 0;
        $creditsLost = 0;
        $creditsGained = 0;

        // Get credit limits for the new curriculum
        $creditLimits = $this->getCreditLimitsForCurriculum($simulation->new_curriculum_id);

        // Process convalidated subjects
        foreach ($equivalenceResult['convalidated'] as $subject) {
            foreach ($subject['equivalences'] as $equiv) {
                // Get the new subject to determine its component
                $newSubject = ExternalSubject::where('code', $equiv['new_code'])
                    ->where('external_curriculum_id', $simulation->new_curriculum_id)
                    ->first();

                if (!$newSubject || !$newSubject->component) {
                    continue; // Skip if component not assigned
                }

                $componentType = $newSubject->component->component_type;
                $effectiveCredits = $subject['credits']; // Full credits, no percentage

                // Apply credit limits
                if (!isset($newDistribution[$componentType])) {
                    $newDistribution[$componentType] = 0;
                }

                $limit = $creditLimits[$componentType] ?? null;
                if ($limit !== null && ($newDistribution[$componentType] + $effectiveCredits) > $limit) {
                    // Would exceed limit, calculate overflow
                    $allowedCredits = max(0, $limit - $newDistribution[$componentType]);
                    $overflow = $effectiveCredits - $allowedCredits;
                    $newDistribution[$componentType] += $allowedCredits;
                    $creditsLost += $overflow;
                } else {
                    $newDistribution[$componentType] += $effectiveCredits;
                }

                $totalNewCredits += min($effectiveCredits, $limit !== null ? ($limit - ($newDistribution[$componentType] - $effectiveCredits)) : $effectiveCredits);
            }
        }

        // Calculate credits lost from non-convalidated subjects
        foreach ($equivalenceResult['non_convalidated'] as $subject) {
            $creditsLost += $subject['credits'];
        }

        // Calculate new progress percentage
        $newProgress = ($totalNewCredits / $this->totalProgramCredits) * 100;

        return [
            'new_progress' => round($newProgress, 2),
            'new_credits' => (int)round($totalNewCredits),
            'new_distribution' => $newDistribution,
            'credits_lost' => (int)round($creditsLost),
            'credits_gained' => (int)round($creditsGained),
            'impact_details' => [
                'credit_limits' => $creditLimits,
                'total_program_credits' => $this->totalProgramCredits,
            ],
        ];
    }

    /**
     * Get credit limits for a curriculum.
     * 
     * @param int $curriculumId
     * @return array
     */
    private function getCreditLimitsForCurriculum(int $curriculumId): array
    {
        // Try to get specific limits for this curriculum
        $config = DB::table('credit_limits_config')
            ->where('external_curriculum_id', $curriculumId)
            ->first();

        if (!$config) {
            // Fall back to global limits
            $config = DB::table('credit_limits_config')
                ->whereNull('external_curriculum_id')
                ->first();
        }

        if ($config) {
            return [
                'fundamental_required' => $config->max_required_fundamental_credits ?? null,
                'professional_required' => $config->max_required_professional_credits ?? null,
                'optional_professional' => $config->max_optional_professional_credits ?? null,
                'optional_fundamental' => $config->max_optional_fundamental_credits ?? null,
                'free_elective' => $config->max_free_elective_credits ?? null,
                'thesis' => $config->max_thesis_credits ?? null,
                'leveling' => null, // Leveling typically doesn't count toward degree
            ];
        }

        // Default limits
        return [
            'fundamental_required' => 39,
            'professional_required' => 79,
            'optional_professional' => 9,
            'optional_fundamental' => 6,
            'free_elective' => 28,
            'thesis' => 6,
            'leveling' => null,
        ];
    }

    /**
     * Confirm and apply simulation results (convert to real data).
     * 
     * @param ConvalidationSimulation $simulation
     * @param string $confirmedBy
     * @return array Result summary
     */
    public function confirmSimulation(ConvalidationSimulation $simulation, string $confirmedBy): array
    {
        if ($simulation->isConfirmed()) {
            throw new \Exception('Simulation has already been confirmed');
        }

        if (!$simulation->isCompleted()) {
            throw new \Exception('Simulation must be completed before confirmation');
        }

        DB::beginTransaction();

        try {
            // Mark simulation as confirmed
            $simulation->markAsConfirmed($confirmedBy);

            // Here you would implement the logic to actually apply the changes
            // For example:
            // - Create StudentConvalidation records
            // - Update student progress metrics
            // - Create audit logs
            // This is intentionally left as a placeholder for future implementation

            DB::commit();

            return [
                'success' => true,
                'message' => 'Simulation confirmed successfully',
                'simulation_id' => $simulation->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error confirming simulation #{$simulation->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
