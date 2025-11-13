<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrerequisitesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing prerequisites
        DB::table('subject_prerequisites')->delete();
        
        // Prerequisites mapping based on prerrequisitos.txt
        $prerequisites = [
            // 2nd semester
            '1000005' => ['1000004'], // CÁLCULO INTEGRAL → Cálculo Diferencial
            '4100548' => ['4200910'], // ESTRUCTURAS DE DATOS → Fundamentos de Programación (1° sem)
            '4100539' => ['4100538'], // FUNDAMENTOS DE ECONOMÍA → Introducción a ASI
            // 3rd semester 
            '4200916' => ['4100548'], // PROGRAMACIÓN ORIENTADA A OBJETOS → Estructuras de Datos (2° sem)
            '4200908' => [], // ARQUITECTURA DE COMPUTADORES → N/A
            '4100578' => ['1000005'], // ESTADÍSTICA I → Cálculo Integral
            '4100550' => ['4100538'], // SISTEMAS DE INFORMACIÓN → Introducción a ASI
            '1000045' => ['1000044'], // INGLES II → INGLES I
            // 4th semester
            '4100549' => ['4200916'], // ANÁLISIS Y DISEÑO DE ALGORITMOS → POO
            '4100552' => ['4200916'], // BASES DE DATOS I → POO
            '4100555' => ['4100550'], // PLANEACIÓN DE SI → Sistemas de Información
            '4100591' => ['1000003'], // INVESTIGACIÓN DE OPERACIONES I → Álgebra Lineal
            '1000046' => ['1000045'], // INGLES III → INGLES II

            // 5th semester
            '4100553' => ['4100549', '4100555'], // INGENIERÍA DE SOFTWARE I → Algoritmos, PLANEACIÓN DE SI
            '4200915' => ['4100552'], // PROGRAMACIÓN CON TECNOLOGÍAS WEB → BASES DE DATOS I
            '4100541' => ['4200909'], // ADMINISTRACIÓN FINANCIERA → Contabilidad y Costos
            '1000047' => ['1000046'], // INGLES IV → INGLES III

            // 6th semester
            '4100554' => ['4100553'], // INGENIERÍA DE SOFTWARE II → Ingeniería de Software I
            '4100557' => ['4200908'], // SISTEMAS OPERATIVOS → Arquitectura de Computadores
            '4200917' => [], // SISTEMAS INTELIGENTES → N/A
            
            // 7th semester
            '4100561' => ['4100557', '4100555'], // AUDITORÍA DE SISTEMAS I → Sistemas Operativos, Planeación SI
            '4100558' => ['4100557'], // FUNDAMENTOS DE REDES → Sistemas Operativos
            
            // 8th semester
            '4200914' => ['4100561'], // MODELOS DE GESTIÓN DE TI → Auditoría de Sistemas I
            '4100562' => ['4100541'], // FORMULACIÓN Y EVALUACIÓN → Adm. Financiera
            '4100560' => ['4100578'], // METODOLOGÍA DE LA INVESTIGACIÓN → Estadística I
            '4200911' => [], // GERENCIA ESTRATÉGICA TH → N/A

            // 9th semester
            '4200921' => ['4200914', '4100550'], // ARQUITECTURA EMPRESARIAL → Modelos Gestión TI, SI
            '4100563' => ['4200911', '4100562'], // GERENCIA DE PROYECTOS → GERENCIA ESTRATÉGICA TH, Formulación Proyectos

            // 10th semester
            '4100573' => ['4100560'], // TRABAJO DE GRADO → Metodología de la Investigación
            '4100559' => [], // PRÁCTICA → (70-80% plan aprobado - handled by business logic)
        ];
        
        foreach ($prerequisites as $subjectCode => $prereqCodes) {
            foreach ($prereqCodes as $prereqCode) {
                DB::table('subject_prerequisites')->updateOrInsert(
                    [
                        'subject_code' => $subjectCode,
                        'prerequisite_code' => $prereqCode
                    ],
                    [
                        'subject_code' => $subjectCode,
                        'prerequisite_code' => $prereqCode,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
    }
}
