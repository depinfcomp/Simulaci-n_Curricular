<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Subject;
use App\Models\ElectiveSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CreditDistributionService
 * 
 * Calcula dinámicamente cómo se distribuyen los créditos de un estudiante
 * entre los diferentes componentes curriculares, aplicando límites y overflow.
 * 
 * Este servicio NO almacena datos, solo calcula en tiempo real.
 */
class CreditDistributionService
{
    /**
     * @var array Credit limits configuration
     */
    private array $creditLimits;

    /**
     * @var array Map of subject codes to their info (type, credits, name)
     */
    private array $subjectsMap;

    /**
     * @var array Map of elective subject codes to their info
     */
    private array $electivesMap;

    public function __construct()
    {
        $this->loadCreditLimits();
        $this->loadSubjectsMaps();
    }

    /**
     * Load credit limits from configuration
     */
    private function loadCreditLimits(): void
    {
        // Get credit limits from database (prefer global config where external_curriculum_id is null)
        $config = DB::table('credit_limits_config')
            ->whereNull('external_curriculum_id')
            ->first();
        
        if (!$config) {
            // If no global config, get any available config
            $config = DB::table('credit_limits_config')->first();
        }

        if ($config) {
            $this->creditLimits = [
                'fundamental_required' => $config->max_required_fundamental_credits ?? 39,
                'professional_required' => $config->max_required_professional_credits ?? 79,
                'optional_professional' => $config->max_optional_professional_credits ?? 9,
                'optional_fundamental' => $config->max_optional_fundamental_credits ?? 6,
                'free_elective' => $config->max_free_elective_credits ?? 28,
                'thesis' => $config->max_thesis_credits ?? 6,
            ];
        } else {
            // Default limits if no configuration found
            $this->creditLimits = [
                'fundamental_required' => 39,
                'professional_required' => 79,
                'optional_professional' => 9,
                'optional_fundamental' => 6,
                'free_elective' => 28,
                'thesis' => 6,
            ];
        }
        
        Log::info('Credit limits loaded: ' . json_encode($this->creditLimits));
    }

    /**
     * Load all subjects and electives into memory for fast lookup
     */
    private function loadSubjectsMaps(): void
    {
        // Load obligatory subjects
        $subjects = Subject::select('code', 'name', 'credits', 'type', 'is_leveling')
            ->get()
            ->keyBy('code')
            ->toArray();

        // Load elective subjects
        $electives = ElectiveSubject::select('code', 'name', 'credits', 'elective_type')
            ->get()
            ->keyBy('code')
            ->toArray();

        $this->subjectsMap = $subjects;
        $this->electivesMap = $electives;
    }

    /**
     * Calculate credit distribution for a student by document
     * 
     * Uses pre-calculated fields from import: effective_credits, overflow_credits,
     * actual_component_type, counts_for_percentage
     * 
     * IMPORTANT: Leveling subjects DON'T count towards degree (167 credits)
     * 
     * @param string $studentDocument Student document number
     * @return array Distribution data
     */
    public function calculateDistribution(string $studentDocument): array
    {
        // Get all approved subjects for the student with pre-calculated distribution
        // Include subjects with grade >= 3.0 OR status = 'passed' (for qualitative AP grades)
        $approvedSubjects = DB::table('student_subject')
            ->where('student_document', $studentDocument)
            ->where('status', 'passed')
            ->orderBy('id')
            ->get([
                'subject_code',
                'subject_credits',
                'subject_type',
                'grade',
                'effective_credits',
                'overflow_credits',
                'actual_component_type',
                'counts_for_percentage',
                'is_duplicate'
            ]);

        // Initialize component usage counters
        $componentUsage = [
            'fundamental_required' => 0,
            'professional_required' => 0,
            'optional_professional' => 0,
            'optional_fundamental' => 0,
            'free_elective' => 0,
            'thesis' => 0,
            'leveling' => 0,
            'practice' => 0,
            'na' => 0, // Credits that don't count
        ];

        // Track individual subject assignments
        $subjectDistributions = [];
        $totalCreditsCounted = 0;
        $totalCreditsLost = 0;

        foreach ($approvedSubjects as $record) {
            $subjectCode = $record->subject_code;
            $totalCredits = $record->subject_credits;
            $effectiveCredits = $record->effective_credits ?? $totalCredits;
            $overflowCredits = $record->overflow_credits ?? 0;
            $actualComponent = $record->actual_component_type ?? $record->subject_type;
            $countsForPercentage = $record->counts_for_percentage ?? true;
            
            // Map subject type to component
            $originalComponent = $this->mapSubjectTypeToComponent($record->subject_type);
            
            // Update component usage based on actual assignment
            if ($actualComponent && $actualComponent !== 'na') {
                $componentUsage[$actualComponent] += $effectiveCredits;
            }
            
            // If there's overflow, it went to free_elective (if it counted)
            if ($overflowCredits > 0 && $countsForPercentage) {
                $componentUsage['free_elective'] += $overflowCredits;
            }
            
            // Calculate credits that count vs lost
            $creditsCounted = $countsForPercentage ? ($effectiveCredits + $overflowCredits) : 0;
            $creditsLost = $totalCredits - $creditsCounted;
            
            $totalCreditsCounted += $creditsCounted;
            $totalCreditsLost += $creditsLost;

            $subjectDistributions[] = [
                'subject_code' => $subjectCode,
                'subject_name' => $this->getSubjectName($subjectCode),
                'credits' => $totalCredits,
                'original_component' => $originalComponent,
                'assigned_component' => $actualComponent,
                'credits_to_component' => $effectiveCredits,
                'credits_to_free_elective' => $overflowCredits,
                'credits_lost' => $creditsLost,
                'credits_counted' => $creditsCounted,
                'counts_towards_degree' => $countsForPercentage,
                'is_duplicate' => $record->is_duplicate ?? false,
                'grade' => $record->grade,
            ];
        }

        return [
            'component_usage' => $this->formatComponentUsage($componentUsage),
            'component_limits' => $this->creditLimits,
            'subject_distributions' => $subjectDistributions,
            'total_credits_counted' => $totalCreditsCounted,
            'total_credits_lost' => $totalCreditsLost,
            'total_approved_subjects' => count($approvedSubjects),
        ];
    }

    /**
     * Format component usage for output
     */
    private function formatComponentUsage(array $componentUsage): array
    {
        $formatted = [];
        foreach ($componentUsage as $component => $used) {
            $formatted[$component] = [
                'used' => $used,
                'limit' => $this->creditLimits[$component] ?? 0,
            ];
        }
        return $formatted;
    }

    /**
     * Get subject name from cache or database
     */
    private function getSubjectName(string $code): string
    {
        if (isset($this->subjectsMap[$code])) {
            return $this->subjectsMap[$code]['name'];
        }
        if (isset($this->electivesMap[$code])) {
            return $this->electivesMap[$code]['name'];
        }
        return 'Unknown';
    }

    /**
     * Get subject information by code
     * 
     * @param string $code Subject code
     * @return array|null Subject info
     */
    private function getSubjectInfo(string $code): ?array
    {
        // Check in obligatory subjects
        if (isset($this->subjectsMap[$code])) {
            $subject = $this->subjectsMap[$code];
            return [
                'code' => $code,
                'name' => $subject['name'],
                'credits' => $subject['credits'],
                'component' => $this->mapSubjectTypeToComponent($subject['type']),
                'is_leveling' => $subject['is_leveling'],
            ];
        }

        // Check in elective subjects
        if (isset($this->electivesMap[$code])) {
            $elective = $this->electivesMap[$code];
            return [
                'code' => $code,
                'name' => $elective['name'],
                'credits' => $elective['credits'],
                'component' => $this->mapElectiveTypeToComponent($elective['elective_type']),
                'is_leveling' => false,
            ];
        }

        return null;
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
     * Get credits that count towards degree for a student
     * 
     * @param string $studentDocument Student document
     * @return int Credits counted
     */
    public function getCountedCredits(string $studentDocument): int
    {
        $distribution = $this->calculateDistribution($studentDocument);
        return $distribution['total_credits_counted'];
    }

    /**
     * Get component usage summary for a student
     * 
     * @param string $studentDocument Student document
     * @return array Component usage with limits
     */
    public function getComponentSummary(string $studentDocument): array
    {
        $distribution = $this->calculateDistribution($studentDocument);
        
        $summary = [];
        foreach ($distribution['component_usage'] as $component => $used) {
            $limit = $distribution['component_limits'][$component] ?? null;
            $summary[] = [
                'component' => $component,
                'used' => $used,
                'limit' => $limit,
                'available' => $limit ? max(0, $limit - $used) : null,
                'percentage' => $limit ? round(($used / $limit) * 100, 2) : null,
            ];
        }

        return $summary;
    }

    /**
     * Check if a student has exceeded any component limits
     * 
     * @param string $studentDocument Student document
     * @return array List of exceeded components
     */
    public function getExceededComponents(string $studentDocument): array
    {
        $distribution = $this->calculateDistribution($studentDocument);
        $exceeded = [];

        foreach ($distribution['component_usage'] as $component => $used) {
            if ($component === 'leveling') continue; // Leveling has no limit
            
            $limit = $distribution['component_limits'][$component] ?? 0;
            if ($used > $limit) {
                $exceeded[] = [
                    'component' => $component,
                    'used' => $used,
                    'limit' => $limit,
                    'excess' => $used - $limit,
                ];
            }
        }

        return $exceeded;
    }

    /**
     * Get detailed report for a specific subject
     * 
     * @param string $studentDocument Student document
     * @param string $subjectCode Subject code
     * @return array|null Distribution details for the subject
     */
    public function getSubjectDistribution(string $studentDocument, string $subjectCode): ?array
    {
        $distribution = $this->calculateDistribution($studentDocument);
        
        foreach ($distribution['subject_distributions'] as $dist) {
            if ($dist['subject_code'] === $subjectCode) {
                return $dist;
            }
        }

        return null;
    }
}
