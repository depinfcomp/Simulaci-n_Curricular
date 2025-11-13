<?php

namespace App\Console\Commands;

use App\Models\StudentCurrentSubject;
use App\Models\Subject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCurrentSubjectsNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subjects:sync-current-names
                            {--force : Force update even if name already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync subject names for student_current_subjects from multiple sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting synchronization of subject names for current subjects...');
        
        $force = $this->option('force');
        
        // Get all current subjects
        $query = StudentCurrentSubject::query();
        
        if (!$force) {
            $query->whereNull('subject_name');
        }
        
        $currentSubjects = $query->get();
        $total = $currentSubjects->count();
        
        if ($total === 0) {
            $this->info('No current subjects to update.');
            return 0;
        }
        
        $this->info("Found {$total} current subjects to process.");
        
        $updated = 0;
        $failed = 0;
        
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();
        
        foreach ($currentSubjects as $currentSubject) {
            $subjectName = $this->findSubjectName($currentSubject->subject_code);
            
            if ($subjectName) {
                $currentSubject->update(['subject_name' => $subjectName]);
                $updated++;
            } else {
                $failed++;
                Log::warning("Could not find name for subject code: {$currentSubject->subject_code}");
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Synchronization completed!");
        $this->table(
            ['Result', 'Count'],
            [
                ['Updated', $updated],
                ['Failed', $failed],
                ['Total', $total],
            ]
        );
        
        return 0;
    }
    
    /**
     * Find subject name from multiple sources
     */
    private function findSubjectName(string $subjectCode): ?string
    {
        // Try 1: subjects table
        $subject = Subject::where('code', $subjectCode)->first();
        if ($subject && $subject->name) {
            return $subject->name;
        }
        
        // Try 2: academic_histories table
        $historyRecord = DB::table('academic_histories')
            ->where('subject_code', $subjectCode)
            ->whereNotNull('subject_name')
            ->where('subject_name', '!=', '')
            ->select('subject_name')
            ->first();
        
        if ($historyRecord && $historyRecord->subject_name) {
            return $historyRecord->subject_name;
        }
        
        // Try 3: student_subject pivot table
        $pivotSubject = DB::table('student_subject as ss')
            ->join('subjects as s', 'ss.subject_code', '=', 's.code')
            ->where('ss.subject_code', $subjectCode)
            ->whereNotNull('s.name')
            ->select('s.name')
            ->first();
        
        if ($pivotSubject && $pivotSubject->name) {
            return $pivotSubject->name;
        }
        
        // Try 4: subject_aliases
        $alias = DB::table('subject_aliases')
            ->where('alias_code', $subjectCode)
            ->first();
        
        if ($alias) {
            $mainSubject = Subject::where('code', $alias->subject_code)->first();
            if ($mainSubject && $mainSubject->name) {
                return $mainSubject->name . ' (alias)';
            }
        }
        
        return null;
    }
}
