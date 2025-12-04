<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelingSubjectSeeder extends Seeder
{
    /**
     * Seeds the leveling_subjects table with UNAL leveling (nivelación) subjects. These are
     * remedial or preparatory courses that students must complete based on their entrance exam
     * results or academic background deficiencies.
     * 
     * Leveling subjects include:
     * - English I-IV: Foreign language requirement (minimum 12 credits required, progressive levels)
     * - Matemáticas Básicas: Basic mathematics for students needing reinforcement
     * - Lecto-escritura: Reading and writing skills for students needing reinforcement
     * - Suficiencia en Lengua Extranjera: Foreign language proficiency certificate (12 credits)
     * 
     * Important notes:
     * - Minimum required: 12 credits (typically fulfilled by English I-IV)
     * - Students may need additional leveling credits based on their entrance evaluation
     * - Leveling credits do NOT count toward the main curriculum credit requirements
     * - These courses must be completed before students can graduate
     * 
     * The seeder uses updateOrInsert to handle re-seeding safely.
     */
    public function run(): void
    {
        $levelingSubjects = [
            // Inglés (nivelación obligatoria - 12 créditos mínimo)
            [
                'code' => '1000044',
                'name' => 'INGLÉS I',
                'credits' => 3,
                'classroom_hours' => 4,
                'student_hours' => 5,
                'description' => 'Primer nivel de nivelación de inglés',
            ],
            [
                'code' => '1000045',
                'name' => 'INGLÉS II',
                'credits' => 3,
                'classroom_hours' => 4,
                'student_hours' => 5,
                'description' => 'Segundo nivel de nivelación de inglés',
            ],
            [
                'code' => '1000046',
                'name' => 'INGLÉS III',
                'credits' => 3,
                'classroom_hours' => 4,
                'student_hours' => 5,
                'description' => 'Tercer nivel de nivelación de inglés',
            ],
            [
                'code' => '1000047',
                'name' => 'INGLÉS IV',
                'credits' => 3,
                'classroom_hours' => 4,
                'student_hours' => 5,
                'description' => 'Cuarto nivel de nivelación de inglés',
            ],
            [
                'code' => '1000001',
                'name' => 'MATEMÁTICAS BÁSICAS',
                'credits' => 4,
                'classroom_hours' => 4,
                'student_hours' => 8,
                'description' => 'Nivelación en matemáticas básicas para estudiantes que lo requieran',
            ],
            [
                'code' => '1000002',
                'name' => 'LECTO-ESCRITURA',
                'credits' => 4,
                'classroom_hours' => 4,
                'student_hours' => 8,
                'description' => 'Nivelación en lectura y escritura para estudiantes que lo requieran',
            ],
            [
                'code' => '1000074',
                'name' => 'SUFICIENCIA EN LENGUA EXTRANJERA ',
                'credits' => 12,
                'classroom_hours' => 2,
                'student_hours' => 4,
                'description' => 'Suficiencia en lengua extranjera',
            ],
        ];

        foreach ($levelingSubjects as $subject) {
            DB::table('leveling_subjects')->updateOrInsert(
                ['code' => $subject['code']],
                array_merge($subject, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('' . count($levelingSubjects) . ' materias de nivelación creadas exitosamente');
        $this->command->info('Total créditos disponibles: ' . collect($levelingSubjects)->sum('credits'));
        $this->command->info('aMínimo requerido de nivelación: 12 créditos');
    }
}
