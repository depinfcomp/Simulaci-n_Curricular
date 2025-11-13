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
        'display_order',
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

    /**
     * Get the component classification for this subject
     * Maps the 'type' field to component names used in the system
     */
    public function getComponentAttribute(): string
    {
        $mapping = [
            'fundamental' => 'fundamental_required',
            'profesional' => 'professional_required',
            'optativa_fundamentacion' => 'optional_fundamental',
            'optativa_profesional' => 'optional_professional',
            'trabajo_grado' => 'thesis',
            'libre_eleccion' => 'free_elective',
            'nivelacion' => 'leveling',
        ];
        
        return $mapping[$this->type] ?? 'free_elective';
    }

    /**
     * Get a human-readable name for the component
     */
    public function getComponentNameAttribute(): string
    {
        $names = [
            'fundamental_required' => 'Fundamentación Obligatoria',
            'professional_required' => 'Profesional Obligatoria',
            'optional_fundamental' => 'Optativa Fundamentación',
            'optional_professional' => 'Optativa Profesional',
            'thesis' => 'Trabajo de Grado',
            'free_elective' => 'Libre Elección',
            'leveling' => 'Nivelación',
        ];
        
        return $names[$this->component] ?? 'Desconocido';
    }
    
    /**
     * Get Bootstrap color class for the component badge
     */
    public function getComponentColorAttribute(): string
    {
        $colors = [
            'fundamental_required' => 'warning',      // Orange (naranja)
            'professional_required' => 'success',     // Green (verde)
            'optional_fundamental' => 'warning',      // Orange (naranja) - Optativas Fundamentales
            'optional_professional' => 'success',     // Green (verde) - Optativas Profesionales
            'thesis' => 'success',                    // Green (verde) - Trabajo de Grado
            'free_elective' => 'primary',             // Blue (azul)
            'leveling' => 'danger',                   // Red (rojo) - Nivelación
        ];
        
        return $colors[$this->component] ?? 'secondary';
    }
}
