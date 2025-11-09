<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditLimitsConfig extends Model
{
    protected $table = 'credit_limits_config';
    
    protected $fillable = [
        'external_curriculum_id',
        'max_free_elective_credits',
        'max_optional_professional_credits',
        'max_required_fundamental_credits',
        'max_optional_fundamental_credits',
        'max_required_professional_credits',
        'max_leveling_credits',
        'max_thesis_credits',
    ];

    protected $casts = [
        'max_free_elective_credits' => 'integer',
        'max_optional_professional_credits' => 'integer',
        'max_required_fundamental_credits' => 'integer',
        'max_optional_fundamental_credits' => 'integer',
        'max_required_professional_credits' => 'integer',
        'max_leveling_credits' => 'integer',
        'max_thesis_credits' => 'integer',
    ];

    /**
     * Relación con ExternalCurriculum
     */
    public function externalCurriculum(): BelongsTo
    {
        return $this->belongsTo(ExternalCurriculum::class);
    }

    /**
     * Obtener configuración por defecto
     */
    public static function getDefaults(): array
    {
        return [
            'max_free_elective_credits' => 36,
            'max_optional_professional_credits' => 9,
            'max_required_fundamental_credits' => 60,
            'max_optional_fundamental_credits' => 6,
            'max_required_professional_credits' => 80,
            'max_leveling_credits' => 12,
            'max_thesis_credits' => 6, // Solo trabajo de grado
        ];
    }

    /**
     * Obtener configuración para un curriculum o defaults
     */
    public static function getForCurriculum(?int $curriculumId): self
    {
        if ($curriculumId) {
            $config = self::where('external_curriculum_id', $curriculumId)->first();
            if ($config) {
                return $config;
            }
        }

        // Retornar instancia con valores por defecto
        return new self(self::getDefaults());
    }

    /**
     * Mapeo de tipos de materia a sus límites correspondientes
     */
    public function getLimitForSubjectType(string $type, bool $isRequired): ?int
    {
        // Componente fundamental obligatorio
        if ($type === 'fundamental' && $isRequired) {
            return $this->max_required_fundamental_credits;
        }
        
        // Componente fundamental optativo
        if ($type === 'optativa_fundamentacion' || ($type === 'fundamental' && !$isRequired)) {
            return $this->max_optional_fundamental_credits;
        }
        
        // Componente disciplinar obligatorio (profesional)
        if ($type === 'profesional' && $isRequired) {
            return $this->max_required_professional_credits;
        }
        
        // Componente disciplinar optativo
        if ($type === 'optativa_profesional' || ($type === 'profesional' && !$isRequired)) {
            return $this->max_optional_professional_credits;
        }
        
        // Libre elección
        if ($type === 'libre_eleccion') {
            return $this->max_free_elective_credits;
        }
        
        // Nivelación (lengua extranjera)
        if ($type === 'lengua_extranjera') {
            return $this->max_leveling_credits;
        }
        
        // Trabajo de grado (identificado por código o nombre)
        // Este caso se maneja de forma especial en el controlador
        
        return null; // Sin límite
    }

    /**
     * Obtener nombre legible del componente
     */
    public static function getComponentName(string $type, bool $isRequired): string
    {
        if ($type === 'fundamental' && $isRequired) {
            return 'Fundamental Obligatorio';
        }
        if ($type === 'optativa_fundamentacion' || ($type === 'fundamental' && !$isRequired)) {
            return 'Fundamental Optativo';
        }
        if ($type === 'profesional' && $isRequired) {
            return 'Disciplinar Obligatorio';
        }
        if ($type === 'optativa_profesional' || ($type === 'profesional' && !$isRequired)) {
            return 'Disciplinar Optativo';
        }
        if ($type === 'libre_eleccion') {
            return 'Libre Elección';
        }
        if ($type === 'lengua_extranjera') {
            return 'Nivelación';
        }
        
        return 'Otro';
    }
}
