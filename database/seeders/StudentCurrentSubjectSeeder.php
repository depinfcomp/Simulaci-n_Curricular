<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentCurrentSubject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentCurrentSubjectSeeder extends Seeder
{
    /**
     * Seeds the student_current_subjects table with realistic current semester enrollments for
     * existing students in the database.
     * 
     * IMPORTANT: This seeder is commented out in DatabaseSeeder because current enrollments should
     * be created during CSV import or managed through the application interface. Use this seeder
     * only for testing and development purposes.
     * 
     * What this seeder does:
     * - Determines current academic period based on system date (YYYY-1 or YYYY-2)
     * - Calculates appropriate semester for each student based on their progress percentage
     * - Identifies subjects the student can take (hasn't passed, prerequisites met)
     * - Enrolls each student in 3-6 subjects for the current period
     * - Generates partial grades for active enrollments
     * - Respects prerequisite requirements when selecting subjects
     * 
     * The seeder intelligently places students in appropriate semesters:
     * - 0-10% progress → Semester 1
     * - 10-20% progress → Semester 2
     * - 20-30% progress → Semester 3
     * - And so on up to 90%+ → Semester 10
     * 
     * Requires StudentSeeder to run first to populate students table.
     */
    public function run(): void
    {
        $students = Student::all();
        $subjects = Subject::all()->keyBy('code');
        $currentPeriod = now()->year . '-' . (now()->month <= 6 ? '1' : '2');
        
        foreach ($students as $student) {
            // Get subjects the student has passed
            $passedSubjects = $student->passedSubjects()->pluck('code')->toArray();
            
            // Determine current semester based on progress
            $currentSemester = $this->getCurrentSemester($student->progress_percentage);
            
            // Get subjects for current semester that haven't been passed
            $availableSubjects = $subjects->filter(function($subject) use ($passedSubjects, $currentSemester, $subjects) {
                return $subject->semester <= $currentSemester && 
                       !in_array($subject->code, $passedSubjects) &&
                       $this->canTakeSubject($subject, $passedSubjects, $subjects);
            });
            
            // Select 3-6 subjects for the current semester
            $selectedSubjects = $availableSubjects->shuffle()->take(rand(3, 6));
            
            foreach ($selectedSubjects as $subject) {
                StudentCurrentSubject::create([
                    'student_id' => $student->id,
                    'subject_code' => $subject->code,
                    'semester_period' => $currentPeriod,
                    'status' => 'cursando',
                    'partial_grade' => $this->generatePartialGrade()
                ]);
            }
        }
    }
    
    /**
     * Determine current semester based on progress percentage
     */
    private function getCurrentSemester($progressPercentage)
    {
        if ($progressPercentage >= 90) return 10;
        if ($progressPercentage >= 80) return 9;
        if ($progressPercentage >= 70) return 8;
        if ($progressPercentage >= 60) return 7;
        if ($progressPercentage >= 50) return 6;
        if ($progressPercentage >= 40) return 5;
        if ($progressPercentage >= 30) return 4;
        if ($progressPercentage >= 20) return 3;
        if ($progressPercentage >= 10) return 2;
        return 1;
    }
    
    /**
     * Check if student can take a subject based on prerequisites
     */
    private function canTakeSubject($subject, $passedSubjects, $allSubjects)
    {
        $prerequisites = $subject->prerequisites()->pluck('code')->toArray();
        
        if (empty($prerequisites)) {
            return true;
        }
        
        // Check if all prerequisites are passed
        foreach ($prerequisites as $prereq) {
            if (!in_array($prereq, $passedSubjects)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate a realistic partial grade
     */
    private function generatePartialGrade()
    {
        $grades = [
            1.0, 1.5, 2.0, 2.5, 3.0, 3.2, 3.5, 3.8, 4.0, 4.2, 4.5, 4.8, 5.0
        ];
        
        // Weight towards passing grades
        $weights = [
            1.0 => 2,   // 2%
            1.5 => 3,   // 3%
            2.0 => 5,   // 5%
            2.5 => 8,   // 8%
            3.0 => 15,  // 15%
            3.2 => 15,  // 15%
            3.5 => 15,  // 15%
            3.8 => 12,  // 12%
            4.0 => 10,  // 10%
            4.2 => 8,   // 8%
            4.5 => 5,   // 5%
            4.8 => 2,   // 2%
            5.0 => 1    // 1%
        ];
        
        $weightedGrades = [];
        foreach ($weights as $grade => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $weightedGrades[] = $grade;
            }
        }
        
        return $weightedGrades[array_rand($weightedGrades)];
    }
}
