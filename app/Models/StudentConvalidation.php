<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentConvalidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_convalidation_id',
        'external_grade',
        'internal_grade',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'external_grade' => 'decimal:2',
        'internal_grade' => 'decimal:2'
    ];

    /**
     * Get the student.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the subject convalidation.
     */
    public function subjectConvalidation()
    {
        return $this->belongsTo(SubjectConvalidation::class);
    }

    /**
     * Get the external subject through convalidation.
     */
    public function getExternalSubject()
    {
        return $this->subjectConvalidation->externalSubject ?? null;
    }

    /**
     * Get the internal subject through convalidation.
     */
    public function getInternalSubject()
    {
        return $this->subjectConvalidation->internalSubject ?? null;
    }

    /**
     * Convert external grade to internal system.
     */
    public function convertGrade()
    {
        // Simple conversion logic - can be made more complex
        // Assuming external grades are on 0-5 scale and internal on 0-5 scale
        $convertedGrade = $this->external_grade;
        
        // Ensure minimum passing grade if original was passing
        if ($this->external_grade >= 3.0 && $convertedGrade < 3.0) {
            $convertedGrade = 3.0;
        }
        
        return round($convertedGrade, 2);
    }

    /**
     * Approve this student convalidation.
     */
    public function approve($processedBy, $adminNotes = null)
    {
        $this->update([
            'status' => 'approved',
            'internal_grade' => $this->convertGrade(),
            'admin_notes' => $adminNotes,
            'processed_by' => $processedBy,
            'processed_at' => now()
        ]);
    }

    /**
     * Reject this student convalidation.
     */
    public function reject($processedBy, $adminNotes)
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
            'processed_by' => $processedBy,
            'processed_at' => now()
        ]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter approved convalidations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Check if this convalidation is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }
}
