<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvalidationEquivalenceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'simulation_id',
        'original_subject_type',
        'original_subject_code',
        'new_subject_type',
        'new_subject_code',
        'equivalence_type',
        'notes',
        'created_by'
    ];

    protected $casts = [];

    /**
     * Equivalence type constants
     */
    const TYPE_DIRECT = 'direct';      // 1:1 equivalence
    const TYPE_GROUP = 'group';        // Multiple subjects needed for equivalence

    /**
     * Subject type constants
     */
    const SUBJECT_TYPE_INTERNAL = 'internal';
    const SUBJECT_TYPE_EXTERNAL = 'external';

    /**
     * Get the simulation this rule belongs to.
     */
    public function simulation()
    {
        return $this->belongsTo(ConvalidationSimulation::class);
    }

    /**
     * Get the original subject (polymorphic).
     */
    public function getOriginalSubject()
    {
        if ($this->original_subject_type === self::SUBJECT_TYPE_INTERNAL) {
            return Subject::where('code', $this->original_subject_code)->first();
        } else {
            return ExternalSubject::where('code', $this->original_subject_code)->first();
        }
    }

    /**
     * Get the new subject (polymorphic).
     */
    public function getNewSubject()
    {
        if ($this->new_subject_type === self::SUBJECT_TYPE_INTERNAL) {
            return Subject::where('code', $this->new_subject_code)->first();
        } else {
            return ExternalSubject::where('code', $this->new_subject_code)->first();
        }
    }

    /**
     * Check if this is a direct equivalence.
     */
    public function isDirect(): bool
    {
        return $this->equivalence_type === self::TYPE_DIRECT;
    }

    /**
     * Check if this is a group equivalence.
     */
    public function isGroup(): bool
    {
        return $this->equivalence_type === self::TYPE_GROUP;
    }

    /**
     * Get all rules for a specific original subject in a simulation.
     */
    public static function findEquivalencesForOriginal(int $simulationId, string $originalCode): \Illuminate\Support\Collection
    {
        return self::where('simulation_id', $simulationId)
                   ->where('original_subject_code', $originalCode)
                   ->get();
    }

    /**
     * Get all rules for a specific new subject in a simulation.
     */
    public static function findEquivalencesForNew(int $simulationId, string $newCode): \Illuminate\Support\Collection
    {
        return self::where('simulation_id', $simulationId)
                   ->where('new_subject_code', $newCode)
                   ->get();
    }
}
