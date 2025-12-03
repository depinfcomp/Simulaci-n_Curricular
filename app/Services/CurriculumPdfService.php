<?php

namespace App\Services;

use App\Models\ExternalCurriculum;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CurriculumPdfService
{
    /**
     * Generate PDF from HTML content (sent from frontend)
     * This captures the EXACT same report the user sees with impact analysis
     * 
     * @param int $externalCurriculumId The external curriculum ID
     * @param string $htmlContent The HTML content of the report
     * @return string Path to the generated PDF file
     */
    public function generatePdfFromHtml($externalCurriculumId, $htmlContent)
    {
        // Generate PDF from HTML
        $pdf = Pdf::loadHTML($htmlContent)
            ->setPaper('a4', 'portrait');
        
        // Save PDF to storage
        $filename = 'convalidation_report_' . $externalCurriculumId . '_' . now()->format('Y-m-d_His') . '.pdf';
        $path = 'pdf/reports/' . $filename;
        $fullPath = storage_path('app/' . $path);
        
        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        
        // Generate PDF output
        $pdfOutput = $pdf->output();
        
        // Save directly to file system
        $bytesWritten = file_put_contents($fullPath, $pdfOutput);
        
        if ($bytesWritten === false) {
            throw new \Exception("Failed to write PDF file: {$fullPath}");
        }
        
        // Verify file was created
        if (!file_exists($fullPath)) {
            throw new \Exception("PDF file was not created at: {$fullPath}");
        }
        
        \Log::info("PDF de convalidación guardado desde HTML", [
            'path' => $path,
            'fullPath' => $fullPath,
            'size' => filesize($fullPath),
            'bytes_written' => $bytesWritten
        ]);
        
        return $path;
    }

    /**
     * Generate the SAME PDF report that is shown in the convalidation view
     * This is a simplified version showing convalidations without student impact analysis
     * 
     * @param int $externalCurriculumId The external curriculum ID from convalidation
     * @param array $newCurriculumData The new curriculum data from simulation (not used, kept for compatibility)
     * @return string Path to the generated PDF file
     */
    public function generateComparisonReport($externalCurriculumId, $newCurriculumData)
    {
        // Load external curriculum with relationships
        $externalCurriculum = ExternalCurriculum::with([
            'externalSubjects',
            'convalidations.internalSubject',
            'convalidations.externalSubject'
        ])->findOrFail($externalCurriculumId);

        // Get curriculum stats
        $stats = $externalCurriculum->getStats();
        
        // Get all direct convalidations for this curriculum
        $convalidations = $externalCurriculum->convalidations()
            ->with(['externalSubject', 'internalSubject'])
            ->where('convalidation_type', 'direct')
            ->get();
        
        // Get N:N convalidation groups
        $nnGroups = \App\Models\ConvalidationGroup::where('external_curriculum_id', $externalCurriculum->id)
            ->with(['externalSubject', 'internalSubjects'])
            ->get();
        
        // Get credit limits
        $creditLimits = $externalCurriculum->creditLimitsConfig;
        
        // Prepare empty results structure (no student analysis for saved PDF)
        $results = [
            'total_students' => 0,
            'affected_students' => 0,
            'students_with_improved_progress' => 0,
            'students_with_no_change' => 0,
            'students_with_reduced_progress' => 0,
            'affected_percentage' => 0,
            'average_progress_change' => 0,
            'min_progress_change' => null,
            'max_progress_change' => null,
            'total_convalidated_subjects' => $convalidations->count() + $nnGroups->count(),
            'direct_convalidations_count' => $convalidations->count(),
            'nn_groups_count' => $nnGroups->count(),
            'flexible_convalidations_count' => $externalCurriculum->convalidations()
                ->where('convalidation_type', 'flexible_component')->count(),
            'additional_subjects_required' => $externalCurriculum->convalidations()
                ->where('convalidation_type', 'not_convalidated')->count(),
            'student_details' => [],
            'subject_impact' => [],
            'original_assigned_credits' => $stats['original_curriculum_stats']['assigned_credits'] ?? 0,
            'new_convalidated_credits' => $stats['new_curriculum_stats']['convalidated_credits'] ?? 0,
            'original_curriculum_credits' => \App\Models\Subject::getCreditsByComponent(),
            'convalidated_credits_by_component' => $externalCurriculum->getCreditsByComponent(),
        ];
        
        // Prepare data for the view (SAME structure as generateImpactReportPdf)
        $reportData = [
            'curriculum' => $externalCurriculum,
            'results' => $results,
            'credit_limits' => $creditLimits,
            'stats' => $stats,
            'convalidations' => $convalidations,
            'nnGroups' => $nnGroups,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        // Generate PDF using the SAME view as the convalidation controller
        $pdf = Pdf::loadView('convalidation.impact-report-pdf', $reportData)
            ->setPaper('a4', 'portrait');
        
        // Save PDF to storage
        $filename = 'convalidation_report_' . $externalCurriculumId . '_' . now()->format('Y-m-d_His') . '.pdf';
        $path = 'pdf/reports/' . $filename;
        $fullPath = storage_path('app/' . $path);
        
        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        
        // Generate PDF output
        $pdfOutput = $pdf->output();
        
        // Save directly to file system
        $bytesWritten = file_put_contents($fullPath, $pdfOutput);
        
        if ($bytesWritten === false) {
            throw new \Exception("Failed to write PDF file: {$fullPath}");
        }
        
        // Verify file was created
        if (!file_exists($fullPath)) {
            throw new \Exception("PDF file was not created at: {$fullPath}");
        }
        
        \Log::info("PDF de convalidación guardado correctamente", [
            'path' => $path,
            'fullPath' => $fullPath,
            'size' => filesize($fullPath),
            'bytes_written' => $bytesWritten
        ]);
        
        return $path;
    }

    /**
     * Capture the current curriculum state from database
     */
    private function captureCurrentState()
    {
        $subjects = Subject::with(['prerequisites', 'requiredFor'])
            ->orderBy('semester')
            ->orderBy('display_order')
            ->get();

        $state = [
            'subjects' => [],
            'totalCredits' => 0,
            'subjectCount' => $subjects->count(),
        ];

        foreach ($subjects as $subject) {
            $state['subjects'][] = [
                'code' => $subject->code,
                'name' => $subject->name,
                'semester' => $subject->semester,
                'credits' => $subject->credits,
                'type' => $subject->type,
                'is_required' => $subject->is_required,
                'prerequisites' => $subject->prerequisites->pluck('code')->toArray(),
            ];
            $state['totalCredits'] += $subject->credits;
        }

        return $state;
    }

    /**
     * Prepare new state from simulation data
     */
    private function prepareNewState($curriculumData)
    {
        $changes = $curriculumData['changes'] ?? [];
        $subjects = $curriculumData['subjects'] ?? [];

        $state = [
            'subjects' => [],
            'totalCredits' => 0,
            'subjectCount' => count($subjects),
            'changes' => $changes,
        ];

        foreach ($subjects as $subject) {
            // Skip removed subjects
            if (isset($subject['isRemoved']) && $subject['isRemoved']) {
                continue;
            }

            $state['subjects'][] = [
                'code' => $subject['code'],
                'name' => $subject['name'],
                'semester' => $subject['semester'],
                'credits' => $subject['credits'] ?? 3,
                'type' => $subject['type'] ?? 'profesional',
                'is_required' => $subject['is_required'] ?? true,
                'prerequisites' => $subject['prerequisites'] ?? [],
                'isAdded' => $subject['isAdded'] ?? false,
            ];
            $state['totalCredits'] += ($subject['credits'] ?? 3);
        }

        return $state;
    }

    /**
     * Calculate differences between old and new states
     */
    private function calculateDifferences($oldState, $newState)
    {
        $differences = [
            'added' => [],
            'removed' => [],
            'modified' => [],
            'unchanged' => [],
        ];

        // Index old subjects by code
        $oldSubjects = collect($oldState['subjects'])->keyBy('code');
        $newSubjects = collect($newState['subjects'])->keyBy('code');

        // Find added subjects
        foreach ($newSubjects as $code => $subject) {
            if (!$oldSubjects->has($code)) {
                $differences['added'][] = $subject;
            }
        }

        // Find removed and modified subjects
        foreach ($oldSubjects as $code => $oldSubject) {
            if (!$newSubjects->has($code)) {
                $differences['removed'][] = $oldSubject;
            } else {
                $newSubject = $newSubjects->get($code);
                
                // Check if any field changed
                $changed = false;
                $changes = [];
                
                foreach (['name', 'semester', 'credits', 'type'] as $field) {
                    if ($oldSubject[$field] != $newSubject[$field]) {
                        $changed = true;
                        $changes[$field] = [
                            'old' => $oldSubject[$field],
                            'new' => $newSubject[$field],
                        ];
                    }
                }

                if ($changed) {
                    $differences['modified'][] = [
                        'subject' => $newSubject,
                        'changes' => $changes,
                    ];
                } else {
                    $differences['unchanged'][] = $newSubject;
                }
            }
        }

        return $differences;
    }
}
