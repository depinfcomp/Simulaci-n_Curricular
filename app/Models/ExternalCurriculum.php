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
        $convalidatedSubjects = $this->convalidations()->count();
        $directConvalidations = $this->convalidations()->where('convalidation_type', 'direct')->count();
        $freeElectives = $this->convalidations()->where('convalidation_type', 'free_elective')->count();
        $notConvalidated = $this->convalidations()->where('convalidation_type', 'not_convalidated')->count();
        
        // Calculate career completion percentage based on internal curriculum credits
        $careerStats = $this->getCareerCompletionStats();
        
        return [
            'total_subjects' => $totalSubjects,
            'convalidated_subjects' => $convalidatedSubjects,
            'direct_convalidations' => $directConvalidations,
            'free_electives' => $freeElectives,
            'not_convalidated' => $notConvalidated,
            'pending_subjects' => $totalSubjects - $convalidatedSubjects,
            'completion_percentage' => $totalSubjects > 0 ? round(($convalidatedSubjects / $totalSubjects) * 100, 2) : 0,
            'career_completion_percentage' => $careerStats['completion_percentage'],
            'convalidated_credits' => $careerStats['convalidated_credits'],
            'total_career_credits' => $careerStats['total_career_credits']
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
            ->selectRaw('SUM(subjects.credits * (subject_convalidations.equivalence_percentage / 100)) as total_credits')
            ->value('total_credits') ?? 0;
        
        // Add free electives (assuming 3 credits each as default)
        $freeElectiveCredits = $this->convalidations()
            ->where('convalidation_type', 'free_elective')
            ->count() * 3; // Default 3 credits per free elective
        
        $totalConvalidatedCredits = $convalidatedCredits + $freeElectiveCredits;
        
        $completionPercentage = $totalCareerCredits > 0 
            ? round(($totalConvalidatedCredits / $totalCareerCredits) * 100, 2) 
            : 0;
        
        return [
            'convalidated_credits' => round($totalConvalidatedCredits, 2),
            'total_career_credits' => $totalCareerCredits,
            'completion_percentage' => $completionPercentage
        ];
    }
}
