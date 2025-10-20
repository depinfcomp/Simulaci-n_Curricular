<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tipos disponibles para 'type':
        // - 'fundamental': Materias fundamentales (color naranja)
        // - 'profesional': Materias profesionales (color verde)
        // - 'optativa_profesional': Optativas profesionales (color verde)
        // - 'optativa_fundamentacion': Optativas de fundamentación (color naranja)
        // - 'lengua_extranjera': Lengua extranjera (color rosado/rosa)
        // - 'libre_eleccion': Libre elección (color azul)
        
        $subjects = [
            // 1st semester - Fundamentales
            ['code' => '4200910', 'name' => 'FUNDAMENTOS DE PROGRAMACIÓN', 'semester' => 1, 'credits' => 3, 'classroom_hours' => 6, 'student_hours' => 3, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '1000004', 'name' => 'CÁLCULO DIFERENCIAL', 'semester' => 1, 'credits' => 4, 'classroom_hours' => 4, 'student_hours' => 8, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '4100538', 'name' => 'INTRODUCCIÓN A LA ADMINISTRACIÓN DE SISTEMAS INFORMÁTICOS', 'semester' => 1, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '4100543', 'name' => 'INTRODUCCIÓN A LA EPISTEMOLOGÍA', 'semester' => 1, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '#LIBRE-01', 'name' => 'LIBRE ELECCIÓN', 'semester' => 1, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],
            
            // 2nd semester
            ['code' => '4200916', 'name' => 'PROGRAMACIÓN ORIENTADA A OBJETOS', 'semester' => 2, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200919', 'name' => 'TEORÍA DE LA ADMINISTRACIÓN Y LA ORGANIZACIÓN I', 'semester' => 2, 'credits' => 2, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '1000005', 'name' => 'CÁLCULO INTEGRAL', 'semester' => 2, 'credits' => 4, 'classroom_hours' => 4, 'student_hours' => 8, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '4100539', 'name' => 'FUNDAMENTOS DE ECONOMÍA', 'semester' => 2, 'credits' => 3, 'classroom_hours' => 6, 'student_hours' => 3, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '#LIBRE-02', 'name' => 'LIBRE ELECCIÓN', 'semester' => 2, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],
            ['code' => '1000044', 'name' => 'INGLÉS I', 'semester' => 2, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'lengua_extranjera', 'is_required' => true],

            // 3rd semester
            ['code' => '4100548', 'name' => 'ESTRUCTURAS DE DATOS', 'semester' => 3, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200908', 'name' => 'ARQUITECTURA DE COMPUTADORES', 'semester' => 3, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '4100578', 'name' => 'ESTADÍSTICA I', 'semester' => 3, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '4100550', 'name' => 'SISTEMAS DE INFORMACIÓN', 'semester' => 3, 'credits' => 3, 'classroom_hours' => 3, 'student_hours' => 6, 'type' => 'profesional', 'is_required' => true],
            ['code' => '1000003', 'name' => 'ÁLGEBRA LINEAL', 'semester' => 3, 'credits' => 4, 'classroom_hours' => 4, 'student_hours' => 8, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '1000045', 'name' => 'INGLÉS II', 'semester' => 3, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'lengua_extranjera', 'is_required' => true],

            // 4th semester
            ['code' => '4100549', 'name' => 'ANÁLISIS Y DISEÑO DE ALGORITMOS', 'semester' => 4, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100552', 'name' => 'BASES DE DATOS I', 'semester' => 4, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100555', 'name' => 'PLANEACIÓN DE SISTEMAS INFORMÁTICOS', 'semester' => 4, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200909', 'name' => 'CONTABILIDAD Y COSTOS', 'semester' => 4, 'credits' => 3, 'classroom_hours' => 3, 'student_hours' => 9, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '4100591', 'name' => 'INVESTIGACIÓN DE OPERACIONES I', 'semester' => 4, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '1000046', 'name' => 'INGLÉS III', 'semester' => 4, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'lengua_extranjera', 'is_required' => true],

            // 5th semester
            ['code' => '4100553', 'name' => 'INGENIERÍA DE SOFTWARE I', 'semester' => 5, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200915', 'name' => 'PROGRAMACIÓN CON TECNOLOGÍAS WEB', 'semester' => 5, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100541', 'name' => 'ADMINISTRACIÓN FINANCIERA', 'semester' => 5, 'credits' => 3, 'classroom_hours' => 6, 'student_hours' => 3, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '#OPTFUN-01', 'name' => 'ASIGNATURA OPTATIVA FUNDAMENTACIÓN', 'semester' => 5, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'optativa_fundamentacion', 'is_required' => false],
            ['code' => '#LIBRE-03', 'name' => 'LIBRE ELECCIÓN', 'semester' => 5, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],
            ['code' => '1000047', 'name' => 'INGLÉS IV', 'semester' => 5, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'lengua_extranjera', 'is_required' => true],

            // 6th semester
            ['code' => '4100554', 'name' => 'INGENIERÍA DE SOFTWARE II', 'semester' => 6, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100557', 'name' => 'SISTEMAS OPERATIVOS', 'semester' => 6, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200917', 'name' => 'SISTEMAS INTELIGENTES COMPUTACIONALES', 'semester' => 6, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '#OPTDIS-01', 'name' => 'ASIGNATURA OPTATIVA DISCIPLINAR', 'semester' => 6, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'optativa_profesional', 'is_required' => false],
            ['code' => '#OPTFUN-02', 'name' => 'ASIGNATURA OPTATIVA FUNDAMENTACIÓN', 'semester' => 6, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'optativa_fundamentacion', 'is_required' => false],
            ['code' => '#LIBRE-04', 'name' => 'LIBRE ELECCIÓN', 'semester' => 6, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],

            // 7th semester
            ['code' => '4100561', 'name' => 'AUDITORÍA DE SISTEMAS I', 'semester' => 7, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100558', 'name' => 'FUNDAMENTOS DE REDES DE DATOS', 'semester' => 7, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 4, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100544', 'name' => 'PSICOLOGÍA SOCIAL', 'semester' => 7, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'fundamental', 'is_required' => true],
            ['code' => '#OPTDIS-02', 'name' => 'ASIGNATURA OPTATIVA DISCIPLINAR', 'semester' => 7, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'optativa_profesional', 'is_required' => false],
            ['code' => '#LIBRE-05', 'name' => 'LIBRE ELECCIÓN', 'semester' => 7, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],
            ['code' => '#LIBRE-06', 'name' => 'LIBRE ELECCIÓN', 'semester' => 7, 'credits' => 4, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],

            // 8th semester
            ['code' => '4200914', 'name' => 'MODELOS DE GESTIÓN DE TECNOLOGÍAS DE LA INFORMACIÓN', 'semester' => 8, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100562', 'name' => 'FORMULACIÓN Y EVALUACIÓN DE PROYECTOS INFORMÁTICOS', 'semester' => 8, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200911', 'name' => 'GERENCIA ESTRATÉGICA DEL TALENTO HUMANO', 'semester' => 8, 'credits' => 2, 'classroom_hours' => 2, 'student_hours' => 4, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100560', 'name' => 'METODOLOGÍA DE LA INVESTIGACIÓN', 'semester' => 8, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4200918', 'name' => 'TENDENCIAS EN ADMINISTRACIÓN DE SISTEMAS INFORMÁTICOS', 'semester' => 8, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '#LIBRE-07', 'name' => 'LIBRE ELECCIÓN', 'semester' => 8, 'credits' => 4, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],

            // 9th semester
            ['code' => '4200921', 'name' => 'ARQUITECTURA EMPRESARIAL', 'semester' => 9, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100563', 'name' => 'GERENCIA DE PROYECTOS TECNOLÓGICOS', 'semester' => 9, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100565', 'name' => 'LEGISLACIÓN TECNOLÓGICA', 'semester' => 9, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'profesional', 'is_required' => true],
            ['code' => '#OPTDIS-03', 'name' => 'ASIGNATURA OPTATIVA DISCIPLINAR', 'semester' => 9, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'optativa_profesional', 'is_required' => false],
            ['code' => '#LIBRE-08', 'name' => 'LIBRE ELECCIÓN', 'semester' => 9, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],
            ['code' => '#LIBRE-09', 'name' => 'LIBRE ELECCIÓN', 'semester' => 9, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],

            // 10th semester
            ['code' => '4100573', 'name' => 'TRABAJO DE GRADO', 'semester' => 10, 'credits' => 6, 'classroom_hours' => 3, 'student_hours' => 15, 'type' => 'profesional', 'is_required' => true],
            ['code' => '4100559', 'name' => 'PRÁCTICA', 'semester' => 10, 'credits' => 7, 'classroom_hours' => 2, 'student_hours' => 20, 'type' => 'profesional', 'is_required' => true],
            ['code' => '#LIBRE-10', 'name' => 'LIBRE ELECCIÓN', 'semester' => 10, 'credits' => 4, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],
            ['code' => '#LIBRE-11', 'name' => 'LIBRE ELECCIÓN', 'semester' => 10, 'credits' => 3, 'classroom_hours' => 4, 'student_hours' => 5, 'type' => 'libre_eleccion', 'is_required' => false],

        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['code' => $subject['code']], // Condition to check for existing record
                [
                    'name' => $subject['name'],
                    'semester' => $subject['semester'],
                    'credits' => $subject['credits'],
                    'classroom_hours' => $subject['classroom_hours'],
                    'student_hours' => $subject['student_hours'],
                    'type' => $subject['type'],
                    'is_required' => $subject['is_required'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
