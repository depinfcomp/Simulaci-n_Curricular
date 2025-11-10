<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\CreditDistributionService;

class SuccessfulImportExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected $successfulRecords;
    protected $importDate;
    protected $creditService;
    protected $distributionCache = [];

    public function __construct(array $successfulRecords, string $importDate)
    {
        $this->successfulRecords = $successfulRecords;
        $this->importDate = $importDate;
        $this->creditService = app(CreditDistributionService::class);
    }

    public function collection()
    {
        // Get unique documents from successful records
        $documents = array_unique(array_column($this->successfulRecords, 'documento'));
        
        // Get students data
        $students = DB::table('students')
            ->whereIn('document', $documents)
            ->select('document', 'name', 'average_grade', 'progress_percentage', 'approved_credits', 'total_credits_taken')
            ->get()
            ->keyBy('document');
        
        // Get all student_subject records for these students
        $studentSubjects = DB::table('student_subject')
            ->whereIn('student_document', $documents)
            ->select(
                'student_document',
                'subject_code',
                'subject_name',
                'subject_credits',
                'subject_type',
                'grade',
                'alphabetic_grade',
                'status',
                'period',
                'created_at'
            )
            ->orderBy('student_document')
            ->orderBy('subject_code')
            ->get();
        
        // Pre-calculate distributions for all students
        foreach ($documents as $document) {
            try {
                $this->distributionCache[$document] = $this->creditService->calculateDistribution($document);
            } catch (\Exception $e) {
                Log::warning("Error calculating distribution for {$document}: " . $e->getMessage());
                $this->distributionCache[$document] = null;
            }
        }
        
        // Enrich records with student and distribution data
        $enrichedRecords = $studentSubjects->map(function ($record) use ($students) {
            $student = $students->get($record->student_document);
            $distribution = $this->distributionCache[$record->student_document] ?? null;
            
            // Find the subject in the distribution
            $subjectDist = null;
            if ($distribution && isset($distribution['subject_distributions'])) {
                foreach ($distribution['subject_distributions'] as $dist) {
                    if ($dist['subject_code'] === $record->subject_code) {
                        $subjectDist = $dist;
                        break;
                    }
                }
            }
            
            return (object) [
                'student_document' => $record->student_document,
                'student_name' => $student ? $student->name : 'Desconocido',
                'student_average' => $student ? $student->average_grade : 0,
                'student_progress' => $student ? $student->progress_percentage : 0,
                'student_approved_credits' => $student ? $student->approved_credits : 0,
                'student_total_credits' => $student ? $student->total_credits_taken : 0,
                'subject_code' => $record->subject_code,
                'subject_name' => $record->subject_name,
                'subject_credits' => $record->subject_credits,
                'subject_type' => $record->subject_type,
                'grade' => $record->grade,
                'alphabetic_grade' => $record->alphabetic_grade,
                'status' => $record->status,
                'period' => $record->period,
                'counts_towards_degree' => $subjectDist ? $subjectDist['counts_towards_degree'] : false,
                'assigned_component' => $subjectDist ? $subjectDist['assigned_component'] : 'N/A',
                'credits_counted' => $subjectDist ? $subjectDist['credits_counted'] : 0,
                'created_at' => $record->created_at,
            ];
        });
        
        return $enrichedRecords;
    }

    public function headings(): array
    {
        return [
            'Documento',
            'Nombre Estudiante',
            'Promedio',
            'Progreso (%)',
            'Créditos Aprobados',
            'Créditos Cursados',
            'Código Asignatura',
            'Nombre Asignatura',
            'Créditos Asignatura',
            'Tipo Materia',
            'Nota Numérica',
            'Nota Alfabética',
            'Estado',
            'Periodo',
            'Cuenta para Grado',
            'Componente Asignado',
            'Créditos Contados',
            'Fecha Importación',
        ];
    }

    public function map($record): array
    {
        $statusText = match($record->status) {
            'passed' => 'Aprobada',
            'failed' => 'Reprobada',
            'enrolled' => 'Inscrita',
            default => $record->status,
        };
        
        $countsText = $record->counts_towards_degree ? 'Sí' : 'No';
        
        return [
            $record->student_document,
            $record->student_name,
            round($record->student_average, 2),
            round($record->student_progress, 2),
            $record->student_approved_credits,
            $record->student_total_credits,
            $record->subject_code,
            $record->subject_name,
            $record->subject_credits,
            $this->formatSubjectType($record->subject_type),
            round($record->grade, 2),
            $record->alphabetic_grade ?? '',
            $statusText,
            $record->period ?? '',
            $countsText,
            $this->formatComponent($record->assigned_component),
            $record->credits_counted,
            date('Y-m-d H:i:s', strtotime($record->created_at)),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Documento
            'B' => 30, // Nombre
            'C' => 12, // Promedio
            'D' => 12, // Progreso
            'E' => 18, // Créditos Aprobados
            'F' => 18, // Créditos Cursados
            'G' => 18, // Código Asignatura
            'H' => 40, // Nombre Asignatura
            'I' => 18, // Créditos Asignatura
            'J' => 20, // Tipo
            'K' => 12, // Nota
            'L' => 12, // Nota Alfabética
            'M' => 12, // Estado
            'N' => 12, // Periodo
            'O' => 18, // Cuenta para Grado
            'P' => 25, // Componente
            'Q' => 18, // Créditos Contados
            'R' => 20, // Fecha
        ];
    }

    public function title(): string
    {
        return 'Registros Exitosos';
    }

    private function formatSubjectType(string $type): string
    {
        $types = [
            'fundamental' => 'Fundamental Obligatoria',
            'fundamental_optional' => 'Fundamental Optativa',
            'professional' => 'Disciplinar Obligatoria',
            'professional_optional' => 'Disciplinar Optativa',
            'free_elective' => 'Libre Elección',
            'nivelacion' => 'Nivelación',
        ];
        
        return $types[$type] ?? $type;
    }

    private function formatComponent(string $component): string
    {
        $components = [
            'fundamental_required' => 'Fundamental Obligatoria',
            'professional_required' => 'Disciplinar Obligatoria',
            'optional_professional' => 'Disciplinar Optativa',
            'optional_fundamental' => 'Fundamental Optativa',
            'free_elective' => 'Libre Elección',
            'thesis' => 'Trabajo de Grado',
            'leveling' => 'Nivelación',
        ];
        
        return $components[$component] ?? $component;
    }
}
