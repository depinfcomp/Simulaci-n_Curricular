<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumVersion extends Model
{
    protected $fillable = [
        'version_number',
        'user_id',
        'description',
        'is_current',
        'curriculum_data',
    ];

    protected $casts = [
        'curriculum_data' => 'array',
        'is_current' => 'boolean',
    ];

    /**
     * Get the user that created this version
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subjects for this version
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(CurriculumVersionSubject::class);
    }

    /**
     * Get the current version
     */
    public static function current()
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Calculate the next version number
     */
    public static function getNextVersionNumber(): string
    {
        $latest = static::orderBy('version_number', 'desc')->first();
        
        if (!$latest) {
            return '1.0';
        }

        [$major, $minor] = explode('.', $latest->version_number);
        $major = (int) $major;
        $minor = (int) $minor;

        // Increment minor version, reset to next major after 10
        if ($minor >= 10) {
            $major++;
            $minor = 0;
        } else {
            $minor++;
        }

        return "{$major}.{$minor}";
    }

    /**
     * Set this version as current
     */
    public function setCurrent(): void
    {
        // Unset all other versions
        static::where('is_current', true)->update(['is_current' => false]);
        
        // Set this as current
        $this->is_current = true;
        $this->save();
    }
}
