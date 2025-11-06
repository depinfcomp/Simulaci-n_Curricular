<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class CurriculumImportAnalyzer
{
    /**
     * Sinónimos para detectar columnas automáticamente
     */
    private const COLUMN_SYNONYMS = [
        'code' => ['código', 'codigo', 'code', 'cód', 'cod', 'asignatura', 'materia', 'subject code', 'subject_code'],
        'name' => ['nombre', 'name', 'asignatura', 'materia', 'subject', 'subject name', 'denominación', 'denominacion'],
        'semester' => ['semestre', 'semester', 'sem', 'periodo', 'period', 'nivel', 'level'],
        'credits' => ['créditos', 'creditos', 'credits', 'cred', 'cr', 'crédito', 'credito', 'ects'],
        'classroom_hours' => ['horas presenciales', 'horas clase', 'h. presenciales', 'classroom hours', 'horas aula', 'h presenciales'],
        'student_hours' => ['horas independientes', 'horas estudiante', 'h. independientes', 'student hours', 'horas trabajo', 'h independientes'],
        'type' => ['tipo', 'type', 'categoría', 'categoria', 'category', 'clasificación', 'clasificacion'],
        'is_required' => ['obligatoria', 'requerida', 'required', 'oblig', 'req', 'obligatorio']
    ];

    /**
     * Campos requeridos mínimos
     */
    private const REQUIRED_FIELDS = ['code', 'name', 'semester', 'credits'];

    /**
     * Analizar archivo Excel y detectar formato
     */
    public function analyze(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Leer todas las filas para análisis
        $allRows = [];
        for ($row = 1; $row <= min($highestRow, 100); $row++) { // Limitar a 100 filas para análisis
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cell = $sheet->getCellByColumnAndRow($col, $row);
                $rowData[] = (string) $cell->getValue();
            }
            $allRows[] = $rowData;
        }

        // Detectar fila de encabezados
        $headerRow = $this->detectHeaderRow($allRows);
        
        // Detectar mapeo de columnas
        $headers = $allRows[$headerRow] ?? [];
        $columnMapping = $this->detectColumnMapping($headers);
        
        // Detectar fila de inicio de datos
        $dataStartRow = $headerRow + 1;
        
        // Obtener datos de preview (primeras 10 filas de datos)
        $previewData = [];
        for ($i = $dataStartRow; $i < min($dataStartRow + 10, count($allRows)); $i++) {
            if (!empty(array_filter($allRows[$i]))) { // Ignorar filas vacías
                $previewData[] = $allRows[$i];
            }
        }

        // Verificar campos requeridos
        $requiredFieldsStatus = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            $requiredFieldsStatus[$field] = in_array($field, array_values($columnMapping));
        }

        return [
            'header_row' => $headerRow,
            'data_start_row' => $dataStartRow,
            'total_rows' => $highestRow - $dataStartRow,
            'total_columns' => $highestColumnIndex,
            'headers' => $headers,
            'column_mapping' => $columnMapping,
            'detected_columns' => $this->getDetectedColumnsWithConfidence($headers, $columnMapping),
            'required_fields_status' => $requiredFieldsStatus,
            'preview_data' => $previewData,
            'missing_required_fields' => array_diff(self::REQUIRED_FIELDS, array_values($columnMapping)),
        ];
    }

    /**
     * Detectar fila de encabezados (ignora títulos decorativos)
     */
    private function detectHeaderRow(array $rows): int
    {
        $scores = [];
        
        foreach ($rows as $index => $row) {
            $score = 0;
            $nonEmptyCount = count(array_filter($row, fn($cell) => !empty($cell)));
            
            // Skip empty or near-empty rows
            if ($nonEmptyCount < 3) {
                continue;
            }
            
            // Check for column name matches
            foreach ($row as $cell) {
                $cellLower = mb_strtolower(trim($cell));
                foreach (self::COLUMN_SYNONYMS as $synonyms) {
                    foreach ($synonyms as $synonym) {
                        if (str_contains($cellLower, mb_strtolower($synonym))) {
                            $score += 10;
                            break 2;
                        }
                    }
                }
            }
            
            // Bonus for consistent non-empty cells
            $score += $nonEmptyCount * 2;
            
            // Penalty for numeric-only cells (likely data, not headers)
            $numericCount = count(array_filter($row, fn($cell) => is_numeric($cell)));
            if ($numericCount > $nonEmptyCount / 2) {
                $score -= 20;
            }
            
            $scores[$index] = $score;
        }
        
        // Return row with highest score
        arsort($scores);
        return array_key_first($scores) ?? 0;
    }

    /**
     * Detectar mapeo de columnas basado en sinónimos
     */
    private function detectColumnMapping(array $headers): array
    {
        $mapping = [];
        
        foreach ($headers as $index => $header) {
            $headerLower = mb_strtolower(trim($header));
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            
            foreach (self::COLUMN_SYNONYMS as $field => $synonyms) {
                foreach ($synonyms as $synonym) {
                    if (str_contains($headerLower, mb_strtolower($synonym))) {
                        $mapping[$columnLetter] = $field;
                        break 2; // Break both loops
                    }
                }
            }
        }
        
        return $mapping;
    }

    /**
     * Obtener columnas detectadas con nivel de confianza
     */
    private function getDetectedColumnsWithConfidence(array $headers, array $mapping): array
    {
        $detected = [];
        
        foreach ($mapping as $columnLetter => $field) {
            $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnLetter) - 1;
            $header = $headers[$columnIndex] ?? '';
            
            // Calculate confidence based on exact match vs partial match
            $headerLower = mb_strtolower(trim($header));
            $confidence = 'low';
            
            if (isset(self::COLUMN_SYNONYMS[$field])) {
                foreach (self::COLUMN_SYNONYMS[$field] as $synonym) {
                    if ($headerLower === mb_strtolower($synonym)) {
                        $confidence = 'high';
                        break;
                    } elseif (str_contains($headerLower, mb_strtolower($synonym))) {
                        $confidence = 'medium';
                    }
                }
            }
            
            $detected[$columnLetter] = [
                'field' => $field,
                'header' => $header,
                'confidence' => $confidence
            ];
        }
        
        return $detected;
    }

    /**
     * Validar datos de una fila
     */
    public function validateRow(array $row, array $columnMapping): array
    {
        $errors = [];
        $data = [];
        
        // Extract data based on mapping
        foreach ($columnMapping as $columnLetter => $field) {
            $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnLetter) - 1;
            $value = $row[$columnIndex] ?? null;
            $data[$field] = $value;
        }
        
        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Campo requerido vacío";
            }
        }
        
        // Validate specific field types
        if (!empty($data['credits']) && !is_numeric($data['credits'])) {
            $errors['credits'] = "Debe ser un número";
        }
        
        if (!empty($data['semester'])) {
            $semester = is_numeric($data['semester']) ? (int)$data['semester'] : null;
            if ($semester === null || $semester < 1 || $semester > 10) {
                $errors['semester'] = "Debe ser entre 1 y 10";
            }
        }
        
        if (!empty($data['code'])) {
            $data['code'] = strtoupper(trim($data['code']));
        }
        
        return [
            'data' => $data,
            'errors' => $errors,
            'valid' => empty($errors)
        ];
    }

    /**
     * Obtener lista de campos disponibles para mapeo manual
     */
    public static function getAvailableFields(): array
    {
        return [
            'code' => 'Código de materia (requerido)',
            'name' => 'Nombre de materia (requerido)',
            'semester' => 'Semestre (requerido)',
            'credits' => 'Créditos (requerido)',
            'classroom_hours' => 'Horas presenciales (opcional)',
            'student_hours' => 'Horas independientes (opcional)',
            'type' => 'Tipo de materia (opcional)',
            'is_required' => 'Es obligatoria (opcional)',
        ];
    }
}
