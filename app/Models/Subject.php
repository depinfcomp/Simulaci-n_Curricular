<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'semester',
        'credits',
        'classroom_hours',
        'student_hours',
        'type',
        'is_required',
        'is_leveling',
    ];

    /**
     * Get the students that are enrolled in this subject.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subject', 'subject_code', 'student_id')
                    ->withPivot(['grade', 'status'])
                    ->withTimestamps();
    }

    /**
     * Get the prerequisites for this subject.
     */
    public function prerequisites()
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'subject_code', 'prerequisite_code');
    }

    /**
     * Get the subjects that have this subject as a prerequisite.
     */
    public function requiredFor()
    {
        return $this->belongsToMany(Subject::class, 'subject_prerequisites', 'prerequisite_code', 'subject_code');
    }

    /**
     * Scope a query to only include subjects from a specific semester.
     */
    public function scopeSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }
}
