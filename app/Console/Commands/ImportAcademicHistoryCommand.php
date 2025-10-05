<?php

namespace App\Console\Commands;

use App\Services\AcademicHistoryImportService;
use Illuminate\Console\Command;

class ImportAcademicHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:academic-history {file} {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     */
    protected $description = 'Import academic history from CSV file';

    protected $importService;

    public function __construct(AcademicHistoryImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting academic history import...");
        $this->info("File: {$filePath}");
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No data will be imported");
        }

        try {
            $result = $this->importService->importFromCSV($filePath, $dryRun);
            
            $this->displayResults($result);
            
            if (!$dryRun) {
                $this->info("Import completed successfully!");
            } else {
                $this->info("Dry run completed - no data was imported");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }

    private function displayResults(array $result)
    {
        $this->newLine();
        $this->info("IMPORT RESULTS:");
        $this->line("─────────────────────────────────────");
        
        // Students
        $this->info("STUDENTS:");
        $this->line("  • Total students processed: " . $result['students']['total']);
        $this->line("  • New students created: " . $result['students']['created']);
        $this->line("  • Existing students found: " . $result['students']['existing']);
        
        $this->newLine();
        
        // Subjects
        $this->info("SUBJECTS:");
        $this->line("  • Total subject records: " . $result['subjects']['total_records']);
        $this->line("  • Valid subjects (in system): " . $result['subjects']['valid']);
        $this->line("  • Invalid subjects (discarded): " . $result['subjects']['invalid']);
        
        if (!empty($result['subjects']['invalid_codes'])) {
            $this->newLine();
            $this->warn("DISCARDED SUBJECT CODES:");
            foreach (array_slice($result['subjects']['invalid_codes'], 0, 10) as $code) {
                $this->line("    • {$code}");
            }
            if (count($result['subjects']['invalid_codes']) > 10) {
                $remaining = count($result['subjects']['invalid_codes']) - 10;
                $this->line("    ... and {$remaining} more");
            }
        }
        
        $this->newLine();
        
        // Academic History
        $this->info("ACADEMIC HISTORY:");
        $this->line("  • Historical records (with grades): " . $result['history']['created']);
        $this->line("  • Current subjects (no grades): " . $result['current']['created']);
        $this->line("  • Duplicate records skipped: " . $result['duplicates']);
        
        $this->newLine();
        
        // Performance
        $this->info("PERFORMANCE:");
        $this->line("  • Processing time: " . number_format($result['processing_time'], 2) . " seconds");
        $this->line("  • Records per second: " . number_format($result['records_per_second'], 0));
    }
}
