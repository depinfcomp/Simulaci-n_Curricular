<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConvalidationGroupSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'convalidation_group_id',
        'internal_subject_code',
        'sort_order',
        'weight',
        'notes'
    ];

    protected $casts = [
        'weight' => 'decimal:2'
    ];

    /**
     * Get the convalidation group this subject belongs to.
     */
    public function convalidationGroup()
    {
        return $this->belongsTo(ConvalidationGroup::class);
    }

    /**
     * Get the internal subject.
     */
    public function internalSubject()
    {
        return $this->belongsTo(Subject::class, 'internal_subject_code', 'code');
    }
}
