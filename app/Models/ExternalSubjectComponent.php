<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalSubjectComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_subject_id',
        'component_type',
        'assigned_by',
        'notes'
    ];

    /**
     * Component type options
     */
    const COMPONENT_TYPES = [
        'fundamental_required' => 'Fundamentación Obligatoria',
        'professional_required' => 'Profesional Obligatoria (Disciplinar)',
        'optional_fundamental' => 'Fundamentación Optativa',
        'optional_professional' => 'Profesional Optativa (Disciplinar)',
        'free_elective' => 'Libre Elección',
        'thesis' => 'Trabajo de Grado',
        'leveling' => 'Nivelación',
    ];

    /**
     * Get the external subject that owns this component assignment.
     */
    public function externalSubject()
    {
        return $this->belongsTo(ExternalSubject::class);
    }

    /**
     * Get human-readable component name.
     */
    public function getComponentNameAttribute(): string
    {
        return self::COMPONENT_TYPES[$this->component_type] ?? 'Desconocido';
    }

    /**
     * Get Bootstrap color class for the component badge.
     */
    public function getComponentColorAttribute(): string
    {
        $colors = [
            'fundamental_required' => 'warning',
            'professional_required' => 'success',
            'optional_fundamental' => 'warning',
            'optional_professional' => 'success',
            'free_elective' => 'primary',
            'thesis' => 'success',
            'leveling' => 'danger',
        ];
        
        return $colors[$this->component_type] ?? 'secondary';
    }
}
