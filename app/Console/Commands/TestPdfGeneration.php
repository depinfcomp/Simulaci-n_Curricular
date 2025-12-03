<?php

namespace App\Console\Commands;

use App\Models\ExternalCurriculum;
use App\Services\CurriculumPdfService;
use Illuminate\Console\Command;

class TestPdfGeneration extends Command
{
    protected $signature = 'test:pdf {curriculum_id}';
    protected $description = 'Test PDF generation for a curriculum';

    public function handle()
    {
        $curriculumId = $this->argument('curriculum_id');
        
        $this->info("Generando PDF para curriculum ID: {$curriculumId}");
        
        $curriculum = ExternalCurriculum::find($curriculumId);
        
        if (!$curriculum) {
            $this->error("Curriculum no encontrado");
            return 1;
        }
        
        $service = new CurriculumPdfService();
        
        // Simular datos de curriculum
        $curriculumData = [
            'subjects' => [],
            'changes' => []
        ];
        
        try {
            $path = $service->generateComparisonReport($curriculumId, $curriculumData);
            $fullPath = storage_path('app/' . $path);
            
            $this->info("✅ PDF generado en: {$path}");
            $this->info("Path completo: {$fullPath}");
            $this->info("Archivo existe: " . (file_exists($fullPath) ? 'SI' : 'NO'));
            
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $this->info("Tamaño: " . round($size / 1024, 2) . " KB");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
