<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectAlias extends Model
{
    protected $fillable = [
        'subject_code',
        'alias_code',
        'description'
    ];

    /**
     * Get the subject (can be from subjects or elective_subjects)
     */
    public function getSubjectAttribute()
    {
        // Try regular subjects first
        $subject = \DB::table('subjects')->where('code', $this->subject_code)->first();
        
        if ($subject) {
            return $subject;
        }
        
        // Try elective subjects
        return \DB::table('elective_subjects')->where('code', $this->subject_code)->first();
    }

    /**
     * Get the main subject code from an alias
     * 
     * @param string $aliasCode
     * @return string|null Returns the main subject code or null if not found
     */
    public static function resolveAlias(string $aliasCode): ?string
    {
        $alias = self::where('alias_code', $aliasCode)->first();
        return $alias ? $alias->subject_code : null;
    }

    /**
     * Get all aliases for a subject code
     * 
     * @param string $subjectCode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAliasesForSubject(string $subjectCode)
    {
        return self::where('subject_code', $subjectCode)->get();
    }

    /**
     * Check if a code is an alias
     * 
     * @param string $code
     * @return bool
     */
    public static function isAlias(string $code): bool
    {
        return self::where('alias_code', $code)->exists();
    }
    
    /**
     * Get all subject codes including their aliases
     * Returns array where keys are all codes (main + aliases) and values are main codes
     * 
     * @return array
     */
    public static function getAllCodesWithAliases(): array
    {
        $result = [];
        
        $aliases = self::all();
        
        foreach ($aliases as $alias) {
            // Map alias to main code
            $result[$alias->alias_code] = $alias->subject_code;
        }
        
        return $result;
    }
}
