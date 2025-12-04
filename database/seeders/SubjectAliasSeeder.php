<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SubjectAlias;

class SubjectAliasSeeder extends Seeder
{
    /**
     * Seeds the subject_aliases table with historical subject code mappings. This handles subjects
     * that have changed codes over time, allowing the system to recognize both old and new codes
     * when importing historical student records or processing convalidations.
     * 
     * Use cases:
     * - Importing academic histories with old subject codes from previous years
     * - Processing transfer credits from students who took courses under old codes
     * - Maintaining backward compatibility with legacy systems
     * - Supporting curriculum transitions where subjects were renamed/recoded
     * 
     * Example: '4200924' (old code) → '4200915' (current code for "Programación con tecnologías web")
     * 
     * The seeder:
     * - Truncates the table for clean re-seeding (development only)
     * - Verifies subject existence before creating aliases
     * - Provides console output for tracking alias creation
     * - Checks both subjects and elective_subjects tables for valid references
     * 
     * To add new aliases, simply add entries to the $aliases array following the format shown.
     */
    public function run(): void
    {
        // Limpiar tabla (solo en desarrollo)
        SubjectAlias::truncate();
        
        $aliases = [
            // Ejemplo mencionado por el usuario: Programación con tecnologías web
            [
                'subject_code' => '4200915', // Código actual
                'alias_code' => '4200924',   // Código antiguo
                'description' => 'Programación con tecnologías web - Código histórico anterior a 2020'
            ],
            
            // Agregar más alias según sea necesario
            // Formato:
            // [
            //     'subject_code' => 'CODIGO_ACTUAL',
            //     'alias_code' => 'CODIGO_ANTIGUO',
            //     'description' => 'Descripción del cambio'
            // ],
        ];
        
        foreach ($aliases as $alias) {
            // Verificar que la materia principal existe
            $subjectExists = DB::table('subjects')->where('code', $alias['subject_code'])->exists();
            $electiveExists = DB::table('elective_subjects')->where('code', $alias['subject_code'])->exists();
            
            if ($subjectExists || $electiveExists) {
                SubjectAlias::create($alias);
                $this->command->info("✓ Alias creado: {$alias['alias_code']} → {$alias['subject_code']}");
            } else {
                $this->command->warn("✗ Materia {$alias['subject_code']} no existe, alias no creado");
            }
        }
        
        $this->command->info("\nSeeder completado: " . count($aliases) . " alias(es) procesados");
    }
}
