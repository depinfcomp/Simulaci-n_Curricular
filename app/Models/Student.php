<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'document',
        'progress_percentage',
        'average_grade',
        'total_credits_taken',
        'approved_credits',
    ];

    /**
     * Get the subjects that this student is enrolled in.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subject', 'student_id', 'subject_code')
                    ->withPivot(['grade', 'status'])
                    ->withTimestamps();
    }

    /**
     * Get the subjects that this student has passed.
     */
    public function passedSubjects()
    {
        return $this->subjects()->wherePivot('status', 'passed');
    }

    /**
     * Get the subjects that this student has failed.
     */
    public function failedSubjects()
    {
        return $this->subjects()->wherePivot('status', 'failed');
    }

    /**
     * Get the subjects that this student is currently enrolled in.
     */
    public function enrolledSubjects()
    {
        return $this->subjects()->wherePivot('status', 'enrolled');
    }

    /**
     * Get the subjects that this student is currently taking (this semester).
     */
    public function currentSubjects()
    {
        return $this->hasMany(StudentCurrentSubject::class);
    }

    /**
     * Get the subjects that this student is currently taking for current semester.
     */
    public function currentSemesterSubjects($period = null)
    {
        $period = $period ?? now()->year . '-' . (now()->month <= 6 ? '1' : '2');
        return $this->currentSubjects()->where('semester_period', $period);
    }

    /**
     * Get subjects the student can take next semester based on prerequisites.
     */
    public function getAvailableSubjects()
    {
        $passedCodes = $this->passedSubjects()->pluck('code')->toArray();
        $currentCodes = $this->currentSemesterSubjects()->pluck('subject_code')->toArray();
        
        return Subject::whereNotIn('code', array_merge($passedCodes, $currentCodes))
                     ->get()
                     ->filter(function($subject) use ($passedCodes) {
                         $prerequisites = $subject->prerequisites()->pluck('code')->toArray();
                         return empty($prerequisites) || empty(array_diff($prerequisites, $passedCodes));
                     });
    }

    /**
     * Get subjects that will be blocked if a prerequisite is moved to a later semester.
     */
    public function getBlockedSubjects($subjectCode, $newSemester)
    {
        $subject = Subject::where('code', $subjectCode)->first();
        if (!$subject) return collect();
        
        // Get subjects that require this subject as prerequisite
        $dependentSubjects = $subject->requiredFor()->get();
        
        $blocked = collect();
        foreach ($dependentSubjects as $dependent) {
            // If dependent subject is in an earlier semester than the new semester
            if ($dependent->semester <= $newSemester) {
                $blocked->push($dependent);
            }
        }
        
        return $blocked;
    }

    /**
     * Get the student's GPA (Grade Point Average).
     */
    public function getGpaAttribute()
    {
        $grades = $this->subjects()
                      ->wherePivot('status', 'passed')
                      ->get()
                      ->pluck('pivot.grade')
                      ->filter();

        return $grades->isEmpty() ? 0 : $grades->avg();
    }

    /**
     * Calculate and return the student's progress percentage.
     */
    public function calculateProgressPercentage()
    {
        $passedSubjects = $this->subjects()
                               ->wherePivot('status', 'passed')
                               ->get();
        
        $totalCredits = $passedSubjects->sum('credits');
        $totalPossibleCredits = 167; // Total credits for the program
        
        return round(($totalCredits / $totalPossibleCredits) * 100, 2);
    }

    /**
     * Update the student's progress percentage.
     */
    public function updateProgressPercentage()
    {
        $this->progress_percentage = $this->calculateProgressPercentage();
        $this->save();
    }

    /**
     * Recalculate progress for all students (useful after bulk import)
     */
    public static function recalculateAllProgress()
    {
        $count = 0;
        
        self::chunk(100, function ($students) use (&$count) {
            foreach ($students as $student) {
                $student->updateProgressPercentage();
                $count++;
            }
        });
        
        return $count;
    }

    /**
     * Get student statistics after import
     */
    public function getAcademicStats()
    {
        $passedSubjects = $this->subjects()->wherePivot('status', 'passed')->count();
        $failedSubjects = $this->subjects()->wherePivot('status', 'failed')->count();
        $currentSubjects = $this->currentSubjects()->count();
        
        return [
            'passed_subjects' => $passedSubjects,
            'failed_subjects' => $failedSubjects,
            'current_subjects' => $currentSubjects,
            'total_subjects' => $passedSubjects + $failedSubjects,
            'progress_percentage' => $this->progress_percentage,
            'gpa' => $this->gpa
        ];
    }

    /**
     * Get credits for a subject code (searches in both subjects and elective_subjects)
     */
    private static function getSubjectCredits(string $subjectCode): int
    {
        // Try regular subjects first
        $subject = \DB::table('subjects')->where('code', $subjectCode)->first();
        if ($subject) {
            return $subject->credits;
        }
        
        // Try elective subjects
        $electiveSubject = \DB::table('elective_subjects')->where('code', $subjectCode)->first();
        if ($electiveSubject) {
            return $electiveSubject->credits;
        }
        
        return 0;
    }

            /**
     * Calculate weighted average grade
     * Delegates to StudentMetricsService for consistent calculation
     */
    public function calculateAverageGrade(): float
    {
        $metricsService = app(\App\Services\StudentMetricsService::class);
        return $metricsService->calculateAverageGrade($this->document);
    }

    /**
     * Calculate progress percentage based on approved credits that count toward degree
     * Delegates to StudentMetricsService for consistent calculation
     */
    public function calculateProgressPercentageFromCredits(int $totalProgramCredits = 167)
    {
        $metricsService = app(\App\Services\StudentMetricsService::class);
        $approvedCredits = $metricsService->calculateApprovedCredits($this->document);
        return $metricsService->calculateProgressPercentage($approvedCredits);
    }

    /**
     * Update all academic metrics for this student
     * Delegates to StudentMetricsService which calculates and saves all metrics
     */
    public function updateAcademicMetrics(int $totalProgramCredits = 167)
    {
        $metricsService = app(\App\Services\StudentMetricsService::class);
        $metricsService->setTotalProgramCredits($totalProgramCredits);
        $metrics = $metricsService->calculateAndSaveMetrics($this->document);
        
        // Refresh the model from database to get updated values
        $this->refresh();
        
        return $metrics;
    }
}

