<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicHistory extends Model
{
    protected $fillable = [
        'import_id',
        'student_code',
        'subject_code',
        'subject_name',
        'grade',
        'numeric_grade',
        'credits',
        'period',
        'status',
        'notes',
        'counts_towards_degree',
        'assigned_component',
        'credits_counted'
    ];

    protected $casts = [
        'numeric_grade' => 'decimal:1',
        'credits' => 'integer',
        'credits_counted' => 'integer',
        'counts_towards_degree' => 'boolean',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(AcademicHistoryImport::class, 'import_id');
    }

    /**
     * Check if the subject was approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->numeric_grade >= 3.0;
    }

    /**
     * Check if credits are lost (don't count toward degree)
     */
    public function isLostCredit(): bool
    {
        return $this->counts_towards_degree === false;
    }

    /**
     * Get effective credits (what actually counts)
     */
    public function getEffectiveCredits(): int
    {
        return $this->credits_counted ?? $this->credits;
    }

    /**
     * Scope for credits counting toward degree
     */
    public function scopeCountingTowardsDegree($query)
    {
        return $query->where('counts_towards_degree', true);
    }

    /**
     * Scope for lost credits
     */
    public function scopeLostCredits($query)
    {
        return $query->where('counts_towards_degree', false);
    }
}
