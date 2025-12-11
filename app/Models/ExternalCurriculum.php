<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string|null $institution
 * @property string|null $description
 * @property string|null $uploaded_file
 * @property array|null $metadata
 * @property string $status
 * @property string|null $pdf_report_path
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Collection|ExternalSubject[] $externalSubjects
 * @property-read Collection|SubjectConvalidation[] $convalidations
 * @property-read Collection|ConvalidationGroup[] $convalidationGroups
 */
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
        'status',
        'pdf_report_path'
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
     * Get the N:N convalidation groups for this curriculum.
     */
    public function convalidationGroups()
    {
        return $this->hasMany(\App\Models\ConvalidationGroup::class, 'external_curriculum_id');
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
        // Exclude removed subjects from all calculations
        $totalSubjects = $this->externalSubjects()
            ->where(function($query) {
                $query->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
            })
            ->count();
        
        // Count subjects by convalidation type (excluding removed)
        $directConvalidations = $this->convalidations()
            ->where('convalidation_type', 'direct')
            ->whereHas('externalSubject', function($query) {
                $query->where(function($q) {
                    $q->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
                });
            })
            ->count();
            
        $flexibleComponents = $this->convalidations()
            ->where('convalidation_type', 'flexible_component')
            ->whereHas('externalSubject', function($query) {
                $query->where(function($q) {
                    $q->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
                });
            })
            ->count();
            
        $notConvalidated = $this->convalidations()
            ->where('convalidation_type', 'not_convalidated')
            ->whereHas('externalSubject', function($query) {
                $query->where(function($q) {
                    $q->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
                });
            })
            ->count();
        
        // Count subjects with assigned component (for not_convalidated with components)
        // These are subjects marked as not_convalidated BUT have a component assigned
        $subjectsWithComponent = $this->externalSubjects()
            ->whereHas('assignedComponent')
            ->whereHas('convalidation', function($query) {
                $query->where('convalidation_type', 'not_convalidated');
            })
            ->where(function($query) {
                $query->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
            })
            ->count();
        
        // Count subjects with N:N convalidation groups (excluding removed subjects)
        $nnGroupConvalidations = $this->convalidationGroups()
            ->whereHas('externalSubject', function($query) {
                $query->where(function($q) {
                    $q->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
                });
            })
            ->count();
        
        // Total convalidated subjects includes direct + flexible_component + subjects with component + N:N groups
        $convalidatedSubjects = $directConvalidations + $flexibleComponents + $subjectsWithComponent + $nnGroupConvalidations;
        
        // Track removed subjects separately (they deduct credits but don't count in totals)
        $removedSubjects = $this->externalSubjects()
            ->where('change_type', 'removed')
            ->count();
            
        $removedCredits = $this->externalSubjects()
            ->where('change_type', 'removed')
            ->sum('credits');
        
        // Calculate career completion percentage based on internal curriculum credits
        $careerStats = $this->getCareerCompletionStats();
        
        // Get credits by component
        $creditsByComponent = $this->getCreditsByComponent();
        
        return [
            'total_subjects' => $totalSubjects,
            'convalidated_subjects' => $convalidatedSubjects,
            'direct_convalidations' => $directConvalidations,
            'flexible_components' => $flexibleComponents,
            'nn_group_convalidations' => $nnGroupConvalidations,
            'not_convalidated' => $notConvalidated,
            'pending_subjects' => $totalSubjects - $convalidatedSubjects - $notConvalidated,
            'removed_subjects' => $removedSubjects,
            'removed_credits' => $removedCredits,
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
        
        // Get convalidated credits from direct equivalences (excluding removed subjects)
        $convalidatedCredits = $this->convalidations()
            ->where('convalidation_type', 'direct')
            ->join('subjects', 'subject_convalidations.internal_subject_code', '=', 'subjects.code')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->where(function($query) {
                $query->whereNull('external_subjects.change_type')
                      ->orWhere('external_subjects.change_type', '!=', 'removed');
            })
            ->selectRaw('SUM(subjects.credits) as total_credits')
            ->value('total_credits') ?? 0;
        
        // Add flexible component credits (using external subject credits, excluding removed)
        $flexibleComponentCredits = $this->convalidations()
            ->where('convalidation_type', 'flexible_component')
            ->join('external_subjects', 'subject_convalidations.external_subject_id', '=', 'external_subjects.id')
            ->where(function($query) {
                $query->whereNull('external_subjects.change_type')
                      ->orWhere('external_subjects.change_type', '!=', 'removed');
            })
            ->selectRaw('SUM(external_subjects.credits) as total_credits')
            ->value('total_credits') ?? 0;
        
        // Calculate credits lost from removed subjects
        $removedCredits = $this->externalSubjects()
            ->where('change_type', 'removed')
            ->sum('credits');
        
        $totalConvalidatedCredits = $convalidatedCredits + $flexibleComponentCredits;
        $netConvalidatedCredits = $totalConvalidatedCredits - $removedCredits;
        
        $completionPercentage = $totalCareerCredits > 0 
            ? round(($netConvalidatedCredits / $totalCareerCredits) * 100, 2) 
            : 0;
        
        return [
            'convalidated_credits' => round($totalConvalidatedCredits, 2),
            'removed_credits' => round($removedCredits, 2),
            'net_convalidated_credits' => round($netConvalidatedCredits, 2),
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

        // Get N:N groups with their component types and subject IDs (excluding removed subjects)
        $nnGroupsData = $this->convalidationGroups()
            ->whereHas('externalSubject', function($query) {
                $query->where(function($q) {
                    $q->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
                });
            })
            ->with('externalSubject')
            ->get()
            ->mapWithKeys(function($group) {
                return [$group->external_subject_id => $group->component_type];
            });

        // Get all external subjects with their components and convalidations (excluding removed subjects)
        $subjects = $this->externalSubjects()
            ->with(['assignedComponent', 'convalidation'])
            ->where(function($query) {
                $query->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
            })
            ->get();

        foreach ($subjects as $subject) {
            $credits = $subject->credits ?? 0;
            
            // Check if subject has N:N group with component type
            if (isset($nnGroupsData[$subject->id]) && $nnGroupsData[$subject->id]) {
                $componentType = $nnGroupsData[$subject->id];
                if (isset($components[$componentType])) {
                    $components[$componentType] += $credits;
                }
            }
            // If subject has a component assigned via assignedComponent
            else if ($subject->assignedComponent && $subject->assignedComponent->component_type) {
                $componentType = $subject->assignedComponent->component_type;
                if (isset($components[$componentType])) {
                    $components[$componentType] += $credits;
                }
            } else {
                // Not configured yet (excluding removed subjects)
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
        // Count all subjects for debugging
        $allSubjectsCount = $this->externalSubjects()->count();
        $removedSubjectsCount = $this->externalSubjects()->where('change_type', 'removed')->count();
        
        \Log::info('getNewCurriculumStats called:', [
            'curriculum_id' => $this->id,
            'total_subjects' => $allSubjectsCount,
            'removed_subjects' => $removedSubjectsCount
        ]);
        
        // Total credits in the NEW (external/imported) curriculum
        // EXCLUDING removed subjects (they reduce credits)
        $totalNewCredits = $this->externalSubjects()
            ->where(function($query) {
                $query->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
            })
            ->sum('credits') ?? 0;
        
        // Credits from removed subjects (to track reduction)
        $removedCredits = $this->externalSubjects()
            ->where('change_type', 'removed')
            ->sum('credits') ?? 0;
        
        \Log::info('Credit calculation:', [
            'total_credits_excluding_removed' => $totalNewCredits,
            'removed_credits' => $removedCredits
        ]);
        
        // Convalidated credits from NEW curriculum includes:
        // 1. Direct convalidations
        // 2. Flexible components (optativas, libre elección)
        // 3. Subjects with assigned component (optativas, nivelación, etc.) marked as not_convalidated
        $directAndFlexibleCredits = $this->externalSubjects()
            ->whereHas('convalidation', function($query) {
                $query->whereIn('convalidation_type', ['direct', 'flexible_component']);
            })
            ->where(function($query) {
                // Exclude removed subjects from convalidated count
                $query->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
            })
            ->sum('credits') ?? 0;
        
        // Credits from subjects with assigned component (optativas, nivelación, etc.)
        $componentsCredits = $this->externalSubjects()
            ->whereHas('assignedComponent')
            ->whereHas('convalidation', function($query) {
                $query->where('convalidation_type', 'not_convalidated');
            })
            ->where(function($query) {
                // Exclude removed subjects
                $query->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
            })
            ->sum('credits') ?? 0;
        
        // Credits from N:N convalidation groups (excluding removed subjects)
        $nnGroupCredits = $this->convalidationGroups()
            ->whereHas('externalSubject', function($query) {
                $query->where(function($q) {
                    $q->whereNull('change_type')
                      ->orWhere('change_type', '!=', 'removed');
                });
            })
            ->join('external_subjects', 'convalidation_groups.external_subject_id', '=', 'external_subjects.id')
            ->sum('external_subjects.credits') ?? 0;
        
        $convalidatedNewCredits = $directAndFlexibleCredits + $componentsCredits + $nnGroupCredits;
        
        $percentage = $totalNewCredits > 0 
            ? round(($convalidatedNewCredits / $totalNewCredits) * 100, 2) 
            : 0;
        
        return [
            'total_credits' => $totalNewCredits,
            'convalidated_credits' => $convalidatedNewCredits,
            'removed_credits' => $removedCredits,
            'percentage' => $percentage
        ];
    }
}
