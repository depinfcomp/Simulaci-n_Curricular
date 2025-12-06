<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property int $id
 * @property int $external_curriculum_id
 * @property int|null $external_subject_id
 * @property string $group_name
 * @property string|null $description
 * @property string $equivalence_type
 * @property float|null $equivalence_percentage
 * @property string|null $component_type
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read ExternalCurriculum $externalCurriculum
 * @property-read ExternalSubject|null $externalSubject
 * @property-read Collection|ConvalidationGroupSubject[] $groupSubjects
 */
class ConvalidationGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_curriculum_id',
        'external_subject_id',
        'group_name',
        'description',
        'equivalence_type',
        'equivalence_percentage',
        'component_type',
        'metadata'
    ];

    protected $casts = [
        'equivalence_percentage' => 'decimal:2',
        'metadata' => 'array'
    ];

    /**
     * Equivalence type constants
     */
    const TYPE_ALL = 'all';       // Student must have ALL internal subjects
    const TYPE_ANY = 'any';       // Student needs ANY ONE of the internal subjects
    const TYPE_CREDITS = 'credits'; // Based on accumulated credits

    /**
     * Get the external curriculum this group belongs to.
     */
    public function externalCurriculum()
    {
        return $this->belongsTo(ExternalCurriculum::class);
    }

    /**
     * Get the external subject that represents this group.
     */
    public function externalSubject()
    {
        return $this->belongsTo(ExternalSubject::class);
    }

    /**
     * Get the internal subjects in this group.
     */
    public function internalSubjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'convalidation_group_subjects',
            'convalidation_group_id',
            'internal_subject_code',
            'id',
            'code'
        )->withPivot(['sort_order', 'weight', 'notes'])
          ->withTimestamps()
          ->orderBy('convalidation_group_subjects.sort_order');
    }

    /**
     * Get group subjects pivot records.
     */
    public function groupSubjects()
    {
        return $this->hasMany(ConvalidationGroupSubject::class);
    }

    /**
     * Check if a student qualifies for this convalidation group.
     *
     * @param \Illuminate\Support\Collection $studentPassedSubjects Collection of subject codes
     * @return array ['qualifies' => bool, 'matched_subjects' => array, 'progress' => float]
     */
    public function checkStudentQualification($studentPassedSubjects)
    {
        $internalSubjectCodes = $this->internalSubjects->pluck('code')->toArray();
        $matchedSubjects = [];

        foreach ($internalSubjectCodes as $code) {
            if ($studentPassedSubjects->contains($code)) {
                $matchedSubjects[] = $code;
            }
        }

        $qualifies = false;
        $progress = 0;

        switch ($this->equivalence_type) {
            case self::TYPE_ALL:
                // Student must have ALL subjects
                $qualifies = count($matchedSubjects) === count($internalSubjectCodes);
                $progress = count($internalSubjectCodes) > 0 
                    ? (count($matchedSubjects) / count($internalSubjectCodes)) * 100 
                    : 0;
                break;

            case self::TYPE_ANY:
                // Student needs just ONE subject
                $qualifies = count($matchedSubjects) > 0;
                $progress = $qualifies ? 100 : 0;
                break;

            case self::TYPE_CREDITS:
                // Based on accumulated credits
                $totalCredits = $this->internalSubjects->sum('credits');
                $matchedCredits = $this->internalSubjects
                    ->whereIn('code', $matchedSubjects)
                    ->sum('credits');
                
                $progress = $totalCredits > 0 ? ($matchedCredits / $totalCredits) * 100 : 0;
                
                // Qualifies if progress >= equivalence_percentage
                $qualifies = $progress >= $this->equivalence_percentage;
                break;
        }

        return [
            'qualifies' => $qualifies,
            'matched_subjects' => $matchedSubjects,
            'total_required' => count($internalSubjectCodes),
            'progress' => round($progress, 2),
            'equivalence_type' => $this->equivalence_type,
            'required_percentage' => $this->equivalence_percentage
        ];
    }

    /**
     * Get display name for equivalence type.
     */
    public function getEquivalenceTypeNameAttribute(): string
    {
        return match($this->equivalence_type) {
            self::TYPE_ALL => 'Todas las materias',
            self::TYPE_ANY => 'Cualquier materia',
            self::TYPE_CREDITS => 'Por créditos',
            default => 'Desconocido'
        };
    }

    /**
     * Get a human-readable description of the group.
     */
    public function getFullDescriptionAttribute(): string
    {
        $subjectNames = $this->internalSubjects->pluck('name')->toArray();
        $count = count($subjectNames);

        if ($count === 0) {
            return 'Grupo sin materias asignadas';
        }

        $prefix = match($this->equivalence_type) {
            self::TYPE_ALL => "Debe haber cursado TODAS: ",
            self::TYPE_ANY => "Debe haber cursado CUALQUIERA: ",
            self::TYPE_CREDITS => "Créditos acumulados de: ",
            default => ""
        };

        return $prefix . implode(', ', $subjectNames);
    }
}
