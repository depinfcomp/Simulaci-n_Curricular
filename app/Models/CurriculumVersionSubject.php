<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumVersionSubject extends Model
{
    protected $fillable = [
        'curriculum_version_id',
        'code',
        'name',
        'semester',
        'credits',
        'classroom_hours',
        'student_hours',
        'type',
        'is_required',
        'description',
        'display_order',
        'prerequisites',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'is_required' => 'boolean',
    ];

    /**
     * Get the curriculum version this subject belongs to
     */
    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class);
    }
}
