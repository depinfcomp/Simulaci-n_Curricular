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

    public function __construct(array $successfulRecords, string $importDate)
    {
        $this->successfulRecords = $successfulRecords;
        $this->importDate = $importDate;
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
                'effective_credits',
                'overflow_credits',
                'actual_component_type',
                'is_duplicate',
                'counts_for_percentage',
                'assignment_notes',
                'created_at'
            )
            ->orderBy('student_document')
            ->orderBy('subject_code')
            ->get();
        
        // Enrich records with student data (no need for distribution service anymore)
        $enrichedRecords = $studentSubjects->map(function ($record) use ($students) {
            $student = $students->get($record->student_document);
            
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
                // New fields from credit distribution
                'effective_credits' => $record->effective_credits ?? 0,
                'overflow_credits' => $record->overflow_credits ?? 0,
                'actual_component_type' => $record->actual_component_type ?? 'N/A',
                'is_duplicate' => $record->is_duplicate ?? false,
                'counts_for_percentage' => $record->counts_for_percentage ?? true,
                'assignment_notes' => $record->assignment_notes ?? '',
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
            'Créditos Totales',
            'Tipo Materia',
            'Nota Numérica',
            'Nota Alfabética',
            'Estado',
            'Periodo',
            'Créditos Efectivos',
            'Créditos Overflow',
            'Componente Asignado',
            'Es Duplicado',
            'Cuenta para %',
            'Notas de Asignación',
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
        
        $countsText = $record->counts_for_percentage ? 'Sí' : 'No';
        $duplicateText = $record->is_duplicate ? 'Sí' : 'No';
        
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
            $record->effective_credits,
            $record->overflow_credits,
            $this->formatComponent($record->actual_component_type),
            $duplicateText,
            $countsText,
            $record->assignment_notes ?? '',
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
            'I' => 18, // Créditos Totales
            'J' => 25, // Tipo
            'K' => 12, // Nota Numérica
            'L' => 12, // Nota Alfabética
            'M' => 12, // Estado
            'N' => 12, // Periodo
            'O' => 18, // Créditos Efectivos
            'P' => 18, // Créditos Overflow
            'Q' => 30, // Componente Asignado
            'R' => 12, // Es Duplicado
            'S' => 12, // Cuenta para %
            'T' => 50, // Notas de Asignación
            'U' => 20, // Fecha
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
            'practice' => 'Práctica',
            'leveling' => 'Nivelación',
            'na' => 'N/A (No cuenta para %)',
        ];
        
        return $components[$component] ?? $component;
    }
}
