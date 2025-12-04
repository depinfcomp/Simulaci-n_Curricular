<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelingSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'credits',
        'classroom_hours',
        'student_hours',
        'description',
    ];

    protected $casts = [
        'credits' => 'integer',
        'classroom_hours' => 'integer',
        'student_hours' => 'integer',
    ];

    /**
     * Validation rules
     */
    public static function validationRules($id = null): array
    {
        $codeRule = 'required|string|max:10|unique:leveling_subjects,code';
        if ($id) {
            $codeRule .= ',' . $id;
        }

        return [
            'code' => $codeRule,
            'name' => 'required|string|max:255',
            'credits' => 'required|integer|min:1|max:20',
            'classroom_hours' => 'nullable|integer|min:0|max:168',
            'student_hours' => 'nullable|integer|min:0|max:168',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get validation messages
     */
    public static function validationMessages(): array
    {
        return [
            'code.required' => 'El código de la materia es obligatorio',
            'code.unique' => 'Ya existe una materia de nivelación con este código',
            'code.max' => 'El código no puede tener más de 10 caracteres',
            'name.required' => 'El nombre de la materia es obligatorio',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'credits.required' => 'Los créditos son obligatorios',
            'credits.integer' => 'Los créditos deben ser un número entero',
            'credits.min' => 'Los créditos deben ser al menos 1',
            'credits.max' => 'Los créditos no pueden ser más de 20',
            'classroom_hours.integer' => 'Las horas de clase deben ser un número entero',
            'classroom_hours.min' => 'Las horas de clase no pueden ser negativas',
            'classroom_hours.max' => 'Las horas de clase no pueden superar 168 (horas en una semana)',
            'student_hours.integer' => 'Las horas de estudiante deben ser un número entero',
            'student_hours.min' => 'Las horas de estudiante no pueden ser negativas',
            'student_hours.max' => 'Las horas de estudiante no pueden superar 168 (horas en una semana)',
            'description.max' => 'La descripción no puede tener más de 1000 caracteres',
        ];
    }

    /**
     * Get total workload (classroom + student hours)
     */
    public function getTotalHoursAttribute(): int
    {
        return $this->classroom_hours + $this->student_hours;
    }

    /**
     * Get total credits from all leveling subjects
     */
    public static function getTotalCredits(): int
    {
        return self::sum('credits');
    }
}
