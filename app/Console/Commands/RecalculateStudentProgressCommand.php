<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;

class RecalculateStudentProgressCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'students:recalculate-progress';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate progress percentage for all students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculating progress for all students...');
        
        $progressBar = $this->output->createProgressBar(Student::count());
        $progressBar->start();
        
        $updatedCount = 0;
        
        Student::chunk(100, function ($students) use ($progressBar, &$updatedCount) {
            foreach ($students as $student) {
                $oldProgress = $student->progress_percentage;
                $student->updateProgressPercentage();
                $newProgress = $student->progress_percentage;
                
                if ($oldProgress != $newProgress) {
                    $updatedCount++;
                }
                
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Progress recalculation completed!");
        $this->line("Students with updated progress: {$updatedCount}");
        
        return 0;
    }
}
