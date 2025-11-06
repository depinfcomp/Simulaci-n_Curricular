<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumImport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'original_filename',
        'stored_path',
        'status',
        'header_row',
        'data_start_row',
        'total_rows',
        'column_mapping',
        'detected_columns',
        'required_fields_status',
        'preview_data',
        'validation_errors',
        'missing_data_rows',
        'subjects_imported',
        'import_summary',
        'error_message',
        'template_name',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'detected_columns' => 'array',
        'required_fields_status' => 'array',
        'preview_data' => 'array',
        'validation_errors' => 'array',
        'missing_data_rows' => 'array',
        'import_summary' => 'array',
    ];

    /**
     * Check if all required fields are mapped
     */
    public function hasAllRequiredFields(): bool
    {
        $required = ['code', 'name', 'semester', 'credits'];
        $mapped = array_values($this->column_mapping ?? []);
        
        foreach ($required as $field) {
            if (!in_array($field, $mapped)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get unmapped required fields
     */
    public function getMissingRequiredFields(): array
    {
        $required = ['code', 'name', 'semester', 'credits'];
        $mapped = array_values($this->column_mapping ?? []);
        
        return array_diff($required, $mapped);
    }

    /**
     * Update status with validation
     */
    public function updateStatus(string $newStatus): void
    {
        $validTransitions = [
            'uploaded' => ['analyzing', 'failed'],
            'analyzing' => ['mapping', 'confirmed', 'failed'],
            'mapping' => ['validating', 'failed'],
            'validating' => ['filling', 'confirmed', 'failed'],
            'filling' => ['confirmed', 'failed'],
            'confirmed' => ['importing', 'failed'],
            'importing' => ['completed', 'failed'],
        ];

        $current = $this->status;
        if (isset($validTransitions[$current]) && in_array($newStatus, $validTransitions[$current])) {
            $this->status = $newStatus;
            $this->save();
        }
    }
}

