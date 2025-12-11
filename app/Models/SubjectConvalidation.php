<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $external_curriculum_id
 * @property int $external_subject_id
 * @property string $internal_subject_code
 * @property string $convalidation_type
 * @property string|null $notes
 * @property string $status
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read ExternalCurriculum $externalCurriculum
 * @property-read ExternalSubject $externalSubject
 * @property-read Subject|null $internalSubject
 */
class SubjectConvalidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_curriculum_id',
        'external_subject_id',
        'internal_subject_code',
        'convalidation_type',
        'notes',
        'status',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime'
    ];

    /**
     * Get the external curriculum.
     */
    public function externalCurriculum()
    {
        return $this->belongsTo(ExternalCurriculum::class);
    }

    /**
     * Get the external subject.
     */
    public function externalSubject()
    {
        return $this->belongsTo(ExternalSubject::class);
    }

    /**
     * Get the internal subject.
     */
    public function internalSubject()
    {
        return $this->belongsTo(Subject::class, 'internal_subject_code', 'code');
    }

    /**
     * Get student convalidations using this mapping.
     */
    public function studentConvalidations()
    {
        return $this->hasMany(StudentConvalidation::class);
    }

    /**
     * Check if this is a direct convalidation.
     */
    public function isDirect()
    {
        return $this->convalidation_type === 'direct';
    }

    /**
     * Check if this is a flexible component convalidation (optativas/libre elección).
     */
    public function isFlexibleComponent()
    {
        return $this->convalidation_type === 'flexible_component';
    }

    /**
     * Check if this is a not convalidated subject (lost credit).
     */
    public function isNotConvalidated()
    {
        return $this->convalidation_type === 'not_convalidated';
    }

    /**
     * Get the display name for the convalidation.
     */
    public function getDisplayName()
    {
        if ($this->isDirect() && $this->internalSubject) {
            return $this->internalSubject->name;
        } elseif ($this->isFlexibleComponent()) {
            return 'Componente Electivo';
        } elseif ($this->isNotConvalidated()) {
            return 'No Convalidada (Crédito Perdido)';
        }
        return 'Sin convalidación';
    }

    /**
     * Get the display name for the convalidation type.
     */
    public function getConvalidationTypeDisplayAttribute()
    {
        return match($this->convalidation_type) {
            'direct' => 'Convalidación Directa',
            'flexible_component' => 'Componente Electivo',
            'not_convalidated' => 'Materia Nueva',
            default => $this->convalidation_type
        };
    }

    /**
     * Scope to filter by convalidation type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('convalidation_type', $type);
    }

    /**
     * Scope to filter approved convalidations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter pending convalidations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Approve this convalidation.
     */
    public function approve($approvedBy)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now()
        ]);
    }

    /**
     * Reject this convalidation.
     */
    public function reject($rejectedBy)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now()
        ]);
    }
}
