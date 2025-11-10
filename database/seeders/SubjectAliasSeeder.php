<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\SubjectAlias;

class SubjectAliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Agrega alias de materias que han cambiado de código a lo largo de los años
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
        
        $this->command->info("\n✅ Seeder completado: " . count($aliases) . " alias(es) procesados");
    }
}
