<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectiveSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'semester',
        'credits',
        'classroom_hours',
        'student_hours',
        'elective_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'semester' => 'integer',
        'credits' => 'integer',
        'classroom_hours' => 'integer',
        'student_hours' => 'integer',
    ];

    /**
     * Validation rules
     */
    public static function validationRules($id = null): array
    {
        $codeRule = 'required|string|max:10|unique:elective_subjects,code';
        if ($id) {
            $codeRule .= ',' . $id;
        }

        return [
            'code' => $codeRule,
            'name' => 'required|string|max:255',
            'semester' => 'nullable|integer|min:1|max:10',
            'credits' => 'required|integer|min:1|max:20',
            'classroom_hours' => 'nullable|integer|min:0|max:168',
            'student_hours' => 'nullable|integer|min:0|max:168',
            'elective_type' => 'required|in:optativa_fundamental,optativa_profesional',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if this is a fundamental elective
     */
    public function isFundamental(): bool
    {
        return $this->elective_type === 'optativa_fundamental';
    }

    /**
     * Check if this is a professional/disciplinary elective
     */
    public function isProfessional(): bool
    {
        return $this->elective_type === 'optativa_profesional';
    }

    /**
     * Get formatted type name
     */
    public function getTypeNameAttribute(): string
    {
        return $this->isFundamental() 
            ? 'Optativa Fundamental' 
            : 'Optativa Profesional';
    }

    /**
     * Get Bootstrap color class for the elective type badge
     */
    public function getTypeColorAttribute(): string
    {
        return $this->isFundamental() 
            ? 'warning'   // Orange (naranja) for Optativa Fundamental
            : 'success';  // Green (verde) for Optativa Profesional
    }

    /**
     * Get total hours per week
     */
    public function getTotalHoursAttribute(): int
    {
        return $this->classroom_hours + $this->student_hours;
    }

    /**
     * Scope: Only active subjects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only fundamental electives
     */
    public function scopeFundamental($query)
    {
        return $query->where('elective_type', 'optativa_fundamental');
    }

    /**
     * Scope: Only professional electives
     */
    public function scopeProfessional($query)
    {
        return $query->where('elective_type', 'optativa_profesional');
    }

    /**
     * Get statistics
     */
    public static function getStats(): array
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'fundamental' => self::fundamental()->count(),
            'professional' => self::professional()->count(),
            'fundamental_active' => self::fundamental()->active()->count(),
            'professional_active' => self::professional()->active()->count(),
        ];
    }
}
