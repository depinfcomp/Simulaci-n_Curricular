<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicHistoryImport extends Model
{
    protected $fillable = [
        'filename',
        'original_filename',
        'total_records',
        'successful_imports',
        'failed_imports',
        'column_mapping',
        'import_summary',
        'error_log',
        'status',
        'imported_by'
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'import_summary' => 'array',
        'total_records' => 'integer',
        'successful_imports' => 'integer',
        'failed_imports' => 'integer',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(AcademicHistory::class, 'import_id');
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_records == 0) {
            return 0;
        }
        return round(($this->successful_imports / $this->total_records) * 100, 2);
    }
}
