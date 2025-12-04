<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the application's database with initial data required for the curriculum simulation system.
     * 
     * This seeder runs the core data seeders needed for the system to function. Student data is
     * intentionally NOT seeded here as it should be imported from CSV files using the import
     * functionality to reflect real academic records.
     * 
     * Execution order:
     * 1. SubjectSeeder - Creates the complete curriculum subject catalog
     * 2. PrerequisitesSeeder - Establishes prerequisite relationships between subjects
     * 
     * Note: StudentSeeder and StudentCurrentSubjectSeeder are commented out because student data
     * should be imported via the CSV import feature to maintain data integrity with actual records.
     */
    public function run(): void
    {
        // Seed basic data - students will be imported from CSV
        $this->call([
            SubjectSeeder::class,
            PrerequisitesSeeder::class,
            // StudentSeeder::class, // Commented out - students are imported from CSV
            // StudentCurrentSubjectSeeder::class, // Commented out - will be created during CSV import
        ]);
    }
}
