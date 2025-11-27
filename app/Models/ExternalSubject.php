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
        'additional_data',
        'change_type',
        'original_semester',
        'change_details'
    ];

    protected $casts = [
        'additional_data' => 'array',
        'change_details' => 'array'
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
     * Get the N:N convalidation group this subject belongs to (as the NEW subject).
     */
    public function convalidationGroup()
    {
        return $this->hasOne(ConvalidationGroup::class);
    }

    /**
     * Get the assigned component for this external subject.
     */
    public function assignedComponent()
    {
        return $this->hasOne(ExternalSubjectComponent::class);
    }

    /**
     * Check if this subject has been convalidated.
     */
    public function isConvalidated()
    {
        return $this->convalidation !== null || $this->convalidationGroup !== null;
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
        // Check if subject is part of an N:N group
        if ($this->convalidationGroup) {
            $group = $this->convalidationGroup;
            $group->load('internalSubjects'); // Eager load internal subjects
            
            return [
                'status' => 'approved',
                'type' => 'nn_group',
                'internal_subject' => null,
                'notes' => null,
                'group_name' => $group->group_name,
                'equivalence_type' => $group->equivalence_type,
                'equivalence_percentage' => $group->equivalence_percentage,
                'component_type' => $group->component_type,
                'internal_subjects' => $group->internalSubjects->map(function($subject) {
                    return [
                        'id' => $subject->id,
                        'code' => $subject->code,
                        'name' => $subject->name,
                        'credits' => $subject->credits
                    ];
                })->toArray()
            ];
        }
        
        // Check if subject has a direct convalidation
        if (!$this->convalidation) {
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
