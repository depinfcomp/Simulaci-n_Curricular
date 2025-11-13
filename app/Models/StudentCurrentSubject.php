<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCurrentSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_code',
        'subject_name',
        'semester_period',
        'status',
        'partial_grade'
    ];

    protected $casts = [
        'partial_grade' => 'decimal:1'
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }

    // Scopes
    public function scopeCurrentSemester($query, $period = null)
    {
        $period = $period ?? now()->year . '-' . (now()->month <= 6 ? '1' : '2');
        return $query->where('semester_period', $period);
    }

    public function scopePassing($query)
    {
        return $query->where('status', 'cursando')->where('partial_grade', '>=', 3.0);
    }

    public function scopeFailing($query)
    {
        return $query->where('status', 'cursando')->where('partial_grade', '<', 3.0);
    }

    public function scopeAtRisk($query)
    {
        return $query->where('status', 'cursando')->where('partial_grade', '<', 2.5);
    }
}
