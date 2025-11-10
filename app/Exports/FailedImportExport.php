<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FailedImportExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    protected $failedRecords;
    protected $importDate;

    public function __construct(array $failedRecords, string $importDate)
    {
        $this->failedRecords = $failedRecords;
        $this->importDate = $importDate;
    }

    public function collection()
    {
        return collect($this->failedRecords)->map(function ($record) {
            return (object) [
                'documento' => $record['documento'] ?? '',
                'cod_asignatura' => $record['cod_asignatura'] ?? '',
                'asignatura' => $record['asignatura'] ?? '',
                'periodo' => $record['periodo'] ?? '',
                'nota_numerica' => $record['nota_numerica'] ?? '',
                'nota_alfabetica' => $record['nota_alfabetica'] ?? '',
                'creditos' => $record['creditos'] ?? '',
                'tipo' => $record['tipo'] ?? '',
                'error' => $record['error'] ?? 'Error desconocido',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Documento',
            'Código Asignatura',
            'Nombre Asignatura (CSV)',
            'Periodo',
            'Nota Numérica',
            'Nota Alfabética',
            'Créditos (CSV)',
            'Tipo (CSV)',
            'Motivo de Rechazo',
        ];
    }

    public function map($record): array
    {
        return [
            $record->documento,
            $record->cod_asignatura,
            $record->asignatura,
            $record->periodo,
            $record->nota_numerica,
            $record->nota_alfabetica,
            $record->creditos,
            $record->tipo,
            $record->error,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C00000']
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Documento
            'B' => 18, // Código
            'C' => 40, // Nombre
            'D' => 12, // Periodo
            'E' => 15, // Nota Numérica
            'F' => 15, // Nota Alfabética
            'G' => 15, // Créditos
            'H' => 15, // Tipo
            'I' => 60, // Error
        ];
    }

    public function title(): string
    {
        return 'Registros Rechazados';
    }
}
