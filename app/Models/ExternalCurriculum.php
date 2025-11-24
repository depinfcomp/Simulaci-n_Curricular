<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalCurriculum extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'external_curriculums';

    protected $fillable = [
        'name',
        'institution',
        'description',
        'uploaded_file',
        'metadata',
        'status'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the external subjects for this curriculum.
     */
    public function externalSubjects()
    {
        return $this->hasMany(ExternalSubject::class);
    }

    /**
     * Get the convalidations for this curriculum.
     */
    public function convalidations()
    {
        return $this->hasMany(SubjectConvalidation::class);
    }

    /**
     * Get the credit limits configuration for this curriculum.
     */
    public function creditLimitsConfig()
    {
        return $this->hasOne(CreditLimitsConfig::class);
    }

    /**
     * Get convalidations grouped by semester.
     */
    public function getConvalidationsBySemester()
    {
        $subjects = $this->externalSubjects()->with('convalidation')->get();
        $grouped = [];
        
        foreach ($subjects as $subject) {
            $semester = $subject->semester;
            if (!isset($grouped[$semester])) {
                $grouped[$semester] = [];
            }
            $grouped[$semester][] = $subject;
        }
        
        // Sort semesters numerically
        ksort($grouped, SORT_NUMERIC);
        
        // Sort subjects within each semester by name
        foreach ($grouped as $semester => $subjects) {
            usort($grouped[$semester], function($a, $b) {
                return strcmp($a->name, $b->name);
            });
        }
        
        return $grouped;
    }

    /**
     * Get statistics for this curriculum.
     */
    public function getStats()
    {
        $totalSubjects = $this->externalSubjects()->count();
        
        // Count subjects by convalidation type
        $directConvalidations = $this->convalidations()->where('convalidation_type', 'direct')->count();
        $flexibleComponents = $this->convalidations()->where('convalidation_type', 'flexible_component')->count();
        $notConvalidated = $this->convalidations()->where('convalidation_type', 'not_convalidated')->count();
        
        // Count subjects with assigned component (for not_convalidated with components)
        // These are subjects marked as not_convalidated BUT have a component assigned
        $subjectsWithComponent = $this->externalSubjects()
            ->whereHas('assignedComponent')
            ->whereHas('convalidation', function($query) {
                $query->where('convalidation_type', 'not_convalidated');
            })
            ->count();
        
        // Total convalidated subjects includes direct + flexible_component + subjects with component
        $convalidatedSubjects = $directConvalidations + $flexibleComponents + $subjectsWithComponent;
        
        // Calculate career completion percentage based on internal curriculum credits
        $careerStats = $this->getCareerCompletionStats();
        
        // Get credits by component
        $creditsByComponent = $this->getCreditsByComponent();
        
        return [
            'total_subjects' => $totalSubjects,
            'convalidated_subjects' => $convalidatedSubjects,
            'direct_convalidations' => $directConvalidations,
            'flexible_components' => $flexibleComponents,
            'not_convalidated' => $notConvalidated,
            'pending_subjects' => $totalSubjects - $convalidatedSubjects - $notConvalidated,
            'completion_percentage' => $totalSubjects > 0 ? round(($convalidatedSubjects / $totalSubjects) * 100, 2) : 0,
            'career_completion_percentage' => $careerStats['completion_percentage'],
            'convalidated_credits' => $careerStats['convalidated_credits'],
            'total_career_credits' => $careerStats['total_career_credits'],
            'credits_by_component' => $creditsByComponent,
            'original_curriculum_stats' => $this->getOriginalCurriculumStats(),
            'new_curriculum_stats' => $this->getNewCurriculumStats()
        ];
    }

    /**
     * Calculate career completion percentage based on internal curriculum credits.
     */
    public function getCareerCompletionStats()
    {
        // Get total credits from internal curriculum
        $totalCareerCredits = \App\Models\Subject::sum('credits');
        
        // Get convalidated credits from direct equivalences
        $convalidatedCredits = $this->convalidations()
            ->where('convalidation_type', 'direct')
            ->join('subjects', 'subject_convalidations.internal_subject_code', '=', 'subjects.code')
            ->selectRaw('SUM(subjects.credits) as total_credits')
            ->value('total_credits') ?? 0;
        
        // Add flexible component credits (using external subject credits)
        $flexibleComponentCredits = $this->convalidations()
            ->where('convalidation_type', 'flexible_component')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->selectRaw('SUM(external_subjects.credits) as total_credits')
            ->value('total_credits') ?? 0;
        
        $totalConvalidatedCredits = $convalidatedCredits + $flexibleComponentCredits;
        
        $completionPercentage = $totalCareerCredits > 0 
            ? round(($totalConvalidatedCredits / $totalCareerCredits) * 100, 2) 
            : 0;
        
        return [
            'convalidated_credits' => round($totalConvalidatedCredits, 2),
            'total_career_credits' => $totalCareerCredits,
            'completion_percentage' => $completionPercentage
        ];
    }

    /**
     * Get credits by component type for this curriculum.
     * Returns credits configured for each component type.
     */
    public function getCreditsByComponent()
    {
        $components = [
            'fundamental_required' => 0,
            'professional_required' => 0,
            'optional_fundamental' => 0,
            'optional_professional' => 0,
            'free_elective' => 0,
            'thesis' => 0,
            'leveling' => 0,
            'pending' => 0 // Credits from subjects not yet configured
        ];

        // Get all external subjects with their components and convalidations
        $subjects = $this->externalSubjects()
            ->with(['assignedComponent', 'convalidation'])
            ->get();

        foreach ($subjects as $subject) {
            $credits = $subject->credits ?? 0;
            
            // If subject has a component assigned
            if ($subject->assignedComponent && $subject->assignedComponent->component_type) {
                $componentType = $subject->assignedComponent->component_type;
                if (isset($components[$componentType])) {
                    $components[$componentType] += $credits;
                }
            } else {
                // Not configured yet
                $components['pending'] += $credits;
            }
        }

        return $components;
    }

    /**
     * Get statistics for the ORIGINAL curriculum (malla UNAL - /simulation).
     * Returns total credits in UNAL curriculum and how many have been assigned from convalidations.
     */
    public function getOriginalCurriculumStats()
    {
        // Total credits in the ORIGINAL (UNAL) curriculum from /simulation
        $totalOriginalCredits = \App\Models\Subject::sum('credits') ?? 0;
        
        // Credits assigned in ORIGINAL curriculum from direct convalidations
        // This includes ALL component types (fundamental_required, professional_required, 
        // optional_fundamental, optional_professional, etc.)
        $assignedOriginalCredits = $this->convalidations()
            ->where('convalidation_type', 'direct')
            ->join('subjects', 'subject_convalidations.internal_subject_code', '=', 'subjects.code')
            ->sum('subjects.credits') ?? 0;
        
        // Add flexible component credits (using external subject credits)
        $flexibleComponentCredits = $this->convalidations()
            ->where('convalidation_type', 'flexible_component')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->sum('external_subjects.credits') ?? 0;
        
        $totalAssignedOriginalCredits = $assignedOriginalCredits + $flexibleComponentCredits;
        
        $percentage = $totalOriginalCredits > 0 
            ? round(($totalAssignedOriginalCredits / $totalOriginalCredits) * 100, 2) 
            : 0;
        
        return [
            'total_credits' => $totalOriginalCredits,
            'assigned_credits' => $totalAssignedOriginalCredits,
            'percentage' => $percentage
        ];
    }

    /**
     * Get statistics for the NEW curriculum (malla externa importada).
     * Returns total credits and convalidated credits from the external/new curriculum.
     */
    public function getNewCurriculumStats()
    {
        // Total credits in the NEW (external/imported) curriculum
        $totalNewCredits = $this->externalSubjects()->sum('credits') ?? 0;
        
        // Convalidated credits from NEW curriculum includes:
        // 1. Direct convalidations
        // 2. Flexible components (optativas, libre elección)
        // 3. Subjects with assigned component (optativas, nivelación, etc.) marked as not_convalidated
        $directAndFlexibleCredits = $this->externalSubjects()
            ->whereHas('convalidation', function($query) {
                $query->whereIn('convalidation_type', ['direct', 'flexible_component']);
            })
            ->sum('credits') ?? 0;
        
        // Credits from subjects with assigned component (optativas, nivelación, etc.)
        $componentsCredits = $this->externalSubjects()
            ->whereHas('assignedComponent')
            ->whereHas('convalidation', function($query) {
                $query->where('convalidation_type', 'not_convalidated');
            })
            ->sum('credits') ?? 0;
        
        $convalidatedNewCredits = $directAndFlexibleCredits + $componentsCredits;
        
        $percentage = $totalNewCredits > 0 
            ? round(($convalidatedNewCredits / $totalNewCredits) * 100, 2) 
            : 0;
        
        return [
            'total_credits' => $totalNewCredits,
            'convalidated_credits' => $convalidatedNewCredits,
            'percentage' => $percentage
        ];
    }
}
