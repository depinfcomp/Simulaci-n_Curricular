<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StudentMetricsService
 * 
 * Calcula y actualiza las métricas académicas de un estudiante:
 * - Promedio ponderado (average_grade)
 * - Porcentaje de avance (progress_percentage)
 * - Créditos aprobados (approved_credits)
 * 
 * Estas métricas SE GUARDAN en la tabla students para performance.
 */
class StudentMetricsService
{
    /**
     * @var CreditDistributionService
     */
    private CreditDistributionService $creditService;

    /**
     * @var int Total credits required for the program
     */
    private int $totalProgramCredits;

    public function __construct(CreditDistributionService $creditService = null)
    {
        $this->creditService = $creditService ?? new CreditDistributionService();
        $this->totalProgramCredits = 167; // Default for the program
    }

    /**
     * Calculate all metrics for a student and save them to database
     * 
     * @param string $studentDocument Student document number
     * @return array Calculated metrics
     */
    public function calculateAndSaveMetrics(string $studentDocument): array
    {
        $student = Student::where('document', $studentDocument)->first();
        
        if (!$student) {
            Log::error("Student not found for metrics calculation: {$studentDocument}");
            return [
                'average_grade' => 0,
                'progress_percentage' => 0,
                'approved_credits' => 0,
                'total_credits_taken' => 0,
            ];
        }

        // Calculate metrics
        $averageGrade = $this->calculateAverageGrade($studentDocument);
        $approvedCredits = $this->calculateApprovedCredits($studentDocument);
        $totalCreditsTaken = $this->calculateTotalCreditsTaken($studentDocument);
        $progressPercentage = $this->calculateProgressPercentage($approvedCredits);

        // Save to database
        $student->average_grade = $averageGrade;
        $student->progress_percentage = $progressPercentage;
        $student->approved_credits = $approvedCredits;
        $student->total_credits_taken = $totalCreditsTaken;
        $student->save();

        return [
            'average_grade' => $averageGrade,
            'progress_percentage' => $progressPercentage,
            'approved_credits' => $approvedCredits,
            'total_credits_taken' => $totalCreditsTaken,
        ];
    }

    /**
     * Calculate weighted average grade
     * Formula: Σ(grade × credits) / Σ(credits)
     * Only includes passed subjects (grade >= 3.0)
     * 
     * @param string $studentDocument Student document
     * @return float Average grade
     */
    private function calculateAverageGrade(string $studentDocument): float
    {
        // Get all subjects with numeric grades from the new table
        $subjects = DB::table('student_subject')
            ->where('student_document', $studentDocument)
            ->whereNotNull('grade')
            ->where('grade', '>', 0)
            ->get(['grade', 'subject_credits']);

        if ($subjects->isEmpty()) {
            return 0.0;
        }

        $totalWeightedGrade = 0;
        $totalCredits = 0;

        foreach ($subjects as $subject) {
            // Weight by the original subject credits
            $totalWeightedGrade += $subject->grade * $subject->subject_credits;
            $totalCredits += $subject->subject_credits;
        }

        return $totalCredits > 0 
            ? round($totalWeightedGrade / $totalCredits, 2) 
            : 0.0;
    }

    /**
     * Calculate approved credits (only those that count toward degree)
     * Uses CreditDistributionService to determine which credits count
     * 
     * @param string $studentDocument Student document
     * @return int Approved credits
     */
    public function calculateApprovedCredits(string $studentDocument): int
    {
        $distribution = $this->creditService->calculateDistribution($studentDocument);
        return $distribution['total_credits_counted'];
    }

    /**
     * Calculate total credits taken (all passed and failed subjects)
     * 
     * @param string $studentDocument Student document
     * @return int Total credits taken
     */
    private function calculateTotalCreditsTaken(string $studentDocument): int
    {
        // Sum all credits from attempted courses (regardless of pass/fail) from new table
        $total = DB::table('student_subject')
            ->where('student_document', $studentDocument)
            ->sum('subject_credits');

        return (int) $total;
    }

    /**
     * Calculate progress percentage
     * Formula: (Approved Credits / Total Program Credits) × 100
     * 
     * @param int $approvedCredits Already calculated approved credits
     * @return float Progress percentage
     */
    public function calculateProgressPercentage(int $approvedCredits): float
    {
        if ($approvedCredits <= 0) {
            return 0.0;
        }

        return round(($approvedCredits / $this->totalProgramCredits) * 100, 2);
    }

    /**
     * Get detailed metrics report for a student
     * 
     * @param string $studentDocument Student document
     * @return array Complete metrics report
     */
    public function getMetricsReport(string $studentDocument): array
    {
        $distribution = $this->creditService->calculateDistribution($studentDocument);
        $metrics = $this->calculateAndSaveMetrics($studentDocument);

        return [
            'student_document' => $studentDocument,
            'metrics' => $metrics,
            'component_usage' => $distribution['component_usage'],
            'component_limits' => $distribution['component_limits'],
            'total_credits_counted' => $distribution['total_credits_counted'],
            'total_credits_lost' => $distribution['total_credits_lost'],
            'total_approved_subjects' => $distribution['total_approved_subjects'],
            'credits_remaining' => max(0, $this->totalProgramCredits - $metrics['approved_credits']),
        ];
    }

    /**
     * Update metrics for multiple students (batch operation)
     * 
     * @param array $studentDocuments Array of student documents
     * @return array Summary of updates
     */
    public function batchUpdateMetrics(array $studentDocuments): array
    {
        $updated = 0;
        $failed = 0;

        foreach ($studentDocuments as $document) {
            try {
                $this->calculateAndSaveMetrics($document);
                $updated++;
            } catch (\Exception $e) {
                Log::error("Failed to update metrics for student {$document}: " . $e->getMessage());
                $failed++;
            }
        }

        return [
            'total' => count($studentDocuments),
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * Set custom total program credits
     * 
     * @param int $credits Total credits
     * @return self
     */
    public function setTotalProgramCredits(int $credits): self
    {
        $this->totalProgramCredits = $credits;
        return $this;
    }

    /**
     * Check if student has met graduation requirements
     * 
     * @param string $studentDocument Student document
     * @return array Graduation status
     */
    public function checkGraduationStatus(string $studentDocument): array
    {
        $distribution = $this->creditService->calculateDistribution($studentDocument);
        $componentUsage = $distribution['component_usage'];
        $componentLimits = $distribution['component_limits'];

        $requirements = [];
        $allMet = true;

        // Check each component (except leveling)
        foreach ($componentLimits as $component => $limit) {
            if ($component === 'leveling') continue;

            $used = $componentUsage[$component] ?? 0;
            $met = $used >= $limit;
            
            if (!$met) {
                $allMet = false;
            }

            $requirements[$component] = [
                'required' => $limit,
                'completed' => $used,
                'remaining' => max(0, $limit - $used),
                'met' => $met,
                'percentage' => round(($used / $limit) * 100, 2),
            ];
        }

        return [
            'eligible_for_graduation' => $allMet,
            'total_credits_required' => $this->totalProgramCredits,
            'total_credits_completed' => $distribution['total_credits_counted'],
            'requirements' => $requirements,
        ];
    }
}
