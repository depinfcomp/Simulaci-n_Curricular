<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExternalCurriculum;
use App\Models\ExternalSubject;
use App\Models\SubjectConvalidation;
use App\Models\Subject;

class ConvalidationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an external curriculum
        $externalCurriculum = ExternalCurriculum::create([
            'name' => 'Ingeniería en Sistemas - Universidad Ejemplo',
            'institution' => 'Universidad Ejemplo',
            'description' => 'Malla curricular de prueba para demostrar las funcionalidades de convalidación',
            'status' => 'active',
            'metadata' => [
                'uploaded_at' => now(),
                'file_size' => 1024,
                'original_filename' => 'test_curriculum.csv'
            ]
        ]);

        // Create external subjects that could be convalidated
        $externalSubjects = [
            ['code' => 'MAT101E', 'name' => 'Matemáticas I', 'semester' => 1, 'credits' => 4],
            ['code' => 'MAT102E', 'name' => 'Matemáticas II', 'semester' => 2, 'credits' => 4],
            ['code' => 'PROG101E', 'name' => 'Programación Básica', 'semester' => 1, 'credits' => 3],
            ['code' => 'PROG201E', 'name' => 'Programación Avanzada', 'semester' => 2, 'credits' => 3],
            ['code' => 'BD101E', 'name' => 'Bases de Datos', 'semester' => 3, 'credits' => 3],
            ['code' => 'FIS101E', 'name' => 'Física I', 'semester' => 1, 'credits' => 4],
            // FREE ELECTIVES - To demonstrate the limits system
            ['code' => 'QUI101E', 'name' => 'Química General', 'semester' => 1, 'credits' => 3],
            ['code' => 'BIO101E', 'name' => 'Biología Básica', 'semester' => 2, 'credits' => 4],
            ['code' => 'PSI101E', 'name' => 'Psicología General', 'semester' => 3, 'credits' => 3],
            ['code' => 'ECO101E', 'name' => 'Economía Básica', 'semester' => 4, 'credits' => 2],
            ['code' => 'EST101E', 'name' => 'Estadística', 'semester' => 5, 'credits' => 4],
            ['code' => 'FIL101E', 'name' => 'Filosofía', 'semester' => 6, 'credits' => 2],
        ];

        foreach ($externalSubjects as $subjectData) {
            ExternalSubject::create([
                'external_curriculum_id' => $externalCurriculum->id,
                'code' => $subjectData['code'],
                'name' => $subjectData['name'],
                'semester' => $subjectData['semester'],
                'credits' => $subjectData['credits'],
                'description' => 'Materia de prueba para demostrar convalidaciones'
            ]);
        }

        // Create some convalidations (mapping external subjects to internal subjects)
        $this->createConvalidations($externalCurriculum->id);
    }

    private function createConvalidations($externalCurriculumId)
    {
        // Get some internal subjects that exist
        $internalSubjects = Subject::whereIn('code', [
            'MAT100', 'MAT200', 'PRG100', 'PRG200', 'BDA100', 'FIS100', 'QUI100'
        ])->get()->keyBy('code');

        // Get external subjects
        $externalSubjects = ExternalSubject::where('external_curriculum_id', $externalCurriculumId)
            ->get()->keyBy('code');

        // Create convalidation mappings
        $convalidationMappings = [
            'MAT101E' => 'MAT100', // External Math I -> Internal Math I
            'MAT102E' => 'MAT200', // External Math II -> Internal Math II
            'PROG101E' => 'PRG100', // External Basic Programming -> Internal Programming I
            'PROG201E' => 'PRG200', // External Advanced Programming -> Internal Programming II
            'BD101E' => 'BDA100', // External Database -> Internal Database
            'FIS101E' => 'FIS100', // External Physics -> Internal Physics
            // QUI101E will be left as free elective
        ];

        foreach ($convalidationMappings as $externalCode => $internalCode) {
            if (isset($externalSubjects[$externalCode]) && isset($internalSubjects[$internalCode])) {
                SubjectConvalidation::create([
                    'external_curriculum_id' => $externalCurriculumId,
                    'external_subject_id' => $externalSubjects[$externalCode]->id,
                    'internal_subject_code' => $internalCode,
                    'convalidation_type' => 'direct',
                    'equivalence_percentage' => 100.0,
                    'status' => 'approved',
                    'notes' => 'Convalidación directa por equivalencia de contenidos'
                ]);
            }
        }

        // Create a free elective convalidation for Chemistry
        if (isset($externalSubjects['QUI101E'])) {
            SubjectConvalidation::create([
                'external_curriculum_id' => $externalCurriculumId,
                'external_subject_id' => $externalSubjects['QUI101E']->id,
                'internal_subject_code' => null,
                'convalidation_type' => 'free_elective',
                'equivalence_percentage' => 100.0,
                'status' => 'approved',
                'notes' => 'Convalidada como electiva libre'
            ]);
        }

        // Create additional free electives to demonstrate credit limits
        $freeElectiveCodes = ['BIO101E', 'PSI101E', 'ECO101E', 'EST101E', 'FIL101E'];
        foreach ($freeElectiveCodes as $code) {
            if (isset($externalSubjects[$code])) {
                SubjectConvalidation::create([
                    'external_curriculum_id' => $externalCurriculumId,
                    'external_subject_id' => $externalSubjects[$code]->id,
                    'internal_subject_code' => null,
                    'convalidation_type' => 'free_elective',
                    'equivalence_percentage' => 100.0,
                    'status' => 'approved',
                    'notes' => 'Convalidada como electiva libre - Materia de prueba para demostrar límites de créditos'
                ]);
            }
        }
    }
}
