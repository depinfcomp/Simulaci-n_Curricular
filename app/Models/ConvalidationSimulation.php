<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvalidationSimulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'original_curriculum_id',
        'new_curriculum_id',
        'status',
        'configuration',
        'summary_stats',
        'created_by',
        'confirmed_by',
        'confirmed_at'
    ];

    protected $casts = [
        'configuration' => 'array',
        'summary_stats' => 'array',
        'confirmed_at' => 'datetime'
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CONFIRMED = 'confirmed';

    /**
     * Get the original curriculum (null means internal curriculum).
     */
    public function originalCurriculum()
    {
        return $this->belongsTo(ExternalCurriculum::class, 'original_curriculum_id');
    }

    /**
     * Get the new/imported curriculum.
     */
    public function newCurriculum()
    {
        return $this->belongsTo(ExternalCurriculum::class, 'new_curriculum_id');
    }

    /**
     * Get the equivalence rules for this simulation.
     */
    public function equivalenceRules()
    {
        return $this->hasMany(ConvalidationEquivalenceRule::class, 'simulation_id');
    }

    /**
     * Get the student results for this simulation.
     */
    public function studentResults()
    {
        return $this->hasMany(SimulationStudentResult::class, 'simulation_id');
    }

    /**
     * Check if simulation is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if simulation has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if simulation has been confirmed and applied.
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Mark simulation as completed.
     */
    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Mark simulation as confirmed.
     */
    public function markAsConfirmed(string $confirmedBy): void
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_by = $confirmedBy;
        $this->confirmed_at = now();
        $this->save();
    }

    /**
     * Calculate summary statistics from student results.
     */
    public function calculateSummaryStats(): array
    {
        $results = $this->studentResults;
        
        if ($results->isEmpty()) {
            return [
                'total_students' => 0,
                'students_affected' => 0,
                'students_improved' => 0,
                'students_declined' => 0,
                'students_neutral' => 0,
                'avg_progress_change' => 0,
                'total_credits_lost' => 0,
                'total_credits_gained' => 0,
            ];
        }

        $improved = $results->where('progress_change', '>', 0.1)->count();
        $declined = $results->where('progress_change', '<', -0.1)->count();
        $neutral = $results->count() - $improved - $declined;
        $affected = $improved + $declined;

        return [
            'total_students' => $results->count(),
            'students_affected' => $affected,
            'students_improved' => $improved,
            'students_declined' => $declined,
            'students_neutral' => $neutral,
            'avg_progress_change' => round($results->avg('progress_change'), 2),
            'total_credits_lost' => $results->sum('credits_lost'),
            'total_credits_gained' => $results->sum('credits_gained'),
        ];
    }

    /**
     * Update and save summary statistics.
     */
    public function updateSummaryStats(): void
    {
        $this->summary_stats = $this->calculateSummaryStats();
        $this->save();
    }
}
