<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulationStudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'student_document',
        'original_progress_percentage',
        'original_approved_credits',
        'original_component_distribution',
        'new_progress_percentage',
        'new_approved_credits',
        'new_component_distribution',
        'progress_change',
        'credits_lost',
        'credits_gained',
        'convalidated_subjects',
        'non_convalidated_subjects',
        'impact_details'
    ];

    protected $casts = [
        'original_progress_percentage' => 'decimal:2',
        'new_progress_percentage' => 'decimal:2',
        'progress_change' => 'decimal:2',
        'original_component_distribution' => 'array',
        'new_component_distribution' => 'array',
        'convalidated_subjects' => 'array',
        'non_convalidated_subjects' => 'array',
        'impact_details' => 'array'
    ];

    /**
     * Get the simulation this result belongs to.
     */
    public function simulation()
    {
        return $this->belongsTo(ConvalidationSimulation::class);
    }

    /**
     * Get the student.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_document', 'document');
    }

    /**
     * Check if student's progress improved.
     */
    public function hasImprovedProgress(): bool
    {
        return $this->progress_change > 0.1;
    }

    /**
     * Check if student's progress declined.
     */
    public function hasDeclinedProgress(): bool
    {
        return $this->progress_change < -0.1;
    }

    /**
     * Check if student's progress stayed neutral.
     */
    public function hasNeutralProgress(): bool
    {
        return abs((float)$this->progress_change) <= 0.1;
    }

    /**
     * Get impact level as a string.
     */
    public function getImpactLevel(): string
    {
        if ($this->hasImprovedProgress()) {
            return 'positive';
        } elseif ($this->hasDeclinedProgress()) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    /**
     * Get impact color for UI.
     */
    public function getImpactColor(): string
    {
        $level = $this->getImpactLevel();
        
        return match($level) {
            'positive' => 'success',
            'negative' => 'danger',
            'neutral' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get summary text for this result.
     */
    public function getSummaryText(): string
    {
        $change = (float)$this->progress_change;
        
        if ($this->hasImprovedProgress()) {
            return sprintf('Mejora del %.1f%% en avance', $change);
        } elseif ($this->hasDeclinedProgress()) {
            return sprintf('Reducción del %.1f%% en avance', abs($change));
        } else {
            return 'Sin cambios significativos';
        }
    }
}
