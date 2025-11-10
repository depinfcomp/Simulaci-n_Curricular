<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * StudentSubject Model
 * 
 * Almacena información de todas las materias cursadas por estudiantes.
 * Esta tabla almacena datos desnormalizados para facilitar consultas y reportes.
 * 
 * @property string $student_document Documento del estudiante
 * @property string $subject_code Código de la asignatura
 * @property string $subject_name Nombre de la asignatura
 * @property int $subject_credits Créditos de la asignatura
 * @property string $subject_type Tipo de materia (fundamental, profesional, etc.)
 * @property float $grade Nota numérica
 * @property string $alphabetic_grade Nota alfabética (AP/RE)
 * @property string $status Estado (passed, failed, enrolled, withdrawn)
 * @property string $period Período de inscripción
 */
class StudentSubject extends Model
{
    protected $table = 'student_subject';

    protected $fillable = [
        'student_document',
        'subject_code',
        'subject_name',
        'subject_credits',
        'subject_type',
        'grade',
        'alphabetic_grade',
        'status',
        'period',
    ];

    protected $casts = [
        'grade' => 'float',
        'subject_credits' => 'integer',
    ];

    /**
     * Relación con Student (opcional, por documento)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_document', 'document');
    }

    /**
     * Scope para materias aprobadas
     */
    public function scopePassed($query)
    {
        return $query->where('status', 'passed')->where('grade', '>=', 3.0);
    }

    /**
     * Scope para materias reprobadas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para materias cursando
     */
    public function scopeEnrolled($query)
    {
        return $query->where('status', 'enrolled');
    }

    /**
     * Scope por estudiante
     */
    public function scopeForStudent($query, string $document)
    {
        return $query->where('student_document', $document);
    }

    /**
     * Scope por tipo de materia
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('subject_type', $type);
    }
}
