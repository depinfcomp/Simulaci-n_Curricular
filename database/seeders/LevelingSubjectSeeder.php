<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelingSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Materias de nivelación de la UNAL
     * Mínimo requerido: 12 créditos
     * Pero los estudiantes pueden tener más según su nivel
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
