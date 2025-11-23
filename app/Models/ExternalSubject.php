<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_curriculum_id',
        'code',
        'name',
        'credits',
        'semester',
        'description',
        'additional_data'
    ];

    protected $casts = [
        'additional_data' => 'array'
    ];

    /**
     * Get the external curriculum that owns this subject.
     */
    public function externalCurriculum()
    {
        return $this->belongsTo(ExternalCurriculum::class);
    }

    /**
     * Get the convalidation for this external subject.
     */
    public function convalidation()
    {
        return $this->hasOne(SubjectConvalidation::class);
    }

    /**
     * Check if this subject has been convalidated.
     */
    public function isConvalidated()
    {
        return $this->convalidation !== null;
    }

    /**
     * Get the internal subject this external subject is convalidated to.
     */
    public function getInternalSubject()
    {
        if ($this->convalidation && $this->convalidation->internal_subject_code) {
            return Subject::where('code', $this->convalidation->internal_subject_code)->first();
        }
        return null;
    }

    /**
     * Get convalidation status with details.
     */
    public function getConvalidationStatus()
    {
        if (!$this->isConvalidated()) {
            return [
                'status' => 'pending',
                'type' => null,
                'internal_subject' => null,
                'notes' => null
            ];
        }

        $convalidation = $this->convalidation;
        return [
            'status' => $convalidation->status,
            'type' => $convalidation->convalidation_type,
            'internal_subject' => $this->getInternalSubject(),
            'notes' => $convalidation->notes
        ];
    }

    /**
     * Scope to filter by semester.
     */
    public function scopeSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope to filter convalidated subjects.
     */
    public function scopeConvalidated($query)
    {
        return $query->whereHas('convalidation');
    }

    /**
     * Scope to filter pending convalidation subjects.
     */
    public function scopePendingConvalidation($query)
    {
        return $query->whereDoesntHave('convalidation');
    }

    /**
     * Get the component assignment for this external subject.
     */
    public function component()
    {
        return $this->hasOne(ExternalSubjectComponent::class);
    }

    /**
     * Check if this subject has a component assigned.
     */
    public function hasComponent(): bool
    {
        return $this->component !== null;
    }

    /**
     * Get the component type or null if not assigned.
     */
    public function getComponentType(): ?string
    {
        return $this->component?->component_type;
    }
}
