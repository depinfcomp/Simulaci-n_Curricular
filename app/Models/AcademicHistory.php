<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicHistory extends Model
{
    protected $fillable = [
        'import_id',
        'student_code',
        'student_name',
        'subject_code',
        'subject_name',
        'grade',
        'numeric_grade',
        'credits',
        'period',
        'status',
        'notes'
    ];

    protected $casts = [
        'numeric_grade' => 'decimal:1',
        'credits' => 'integer',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(AcademicHistoryImport::class, 'import_id');
    }

    /**
     * Check if the subject was approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->numeric_grade >= 3.0;
    }
}
