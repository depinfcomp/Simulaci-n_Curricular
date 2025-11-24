<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Impacto - {{ $curriculum->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        @media print {
            body {
                padding: 10px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            h1, h2, h3 {
                page-break-after: avoid;
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 24pt;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 14pt;
            margin-bottom: 5px;
        }
        
        .header .date {
            color: #95a5a6;
            font-size: 10pt;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .info-card.success {
            border-left-color: #27ae60;
        }
        
        .info-card.warning {
            border-left-color: #f39c12;
        }
        
        .info-card.danger {
            border-left-color: #e74c3c;
        }
        
        .info-card .value {
            font-size: 24pt;
            font-weight: bold;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-card .label {
            font-size: 9pt;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: white;
        }
        
        table thead {
            background-color: #34495e;
            color: white;
        }
        
        table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 10pt;
        }
        
        table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 9pt;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        table tbody tr:hover {
            background-color: #e8f4f8;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 12pt;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .print-button:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }
        
        .summary-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .summary-box h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 12pt;
        }
        
        .summary-box p {
            margin: 5px 0;
            font-size: 10pt;
        }
        
        .component-limits {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .component-limit-item {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .component-limit-item strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 9pt;
        }
        
        .component-limit-item span {
            color: #7f8c8d;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <!-- Print Button (hidden in print) -->
    <button class="print-button no-print" onclick="window.print()">
        📄 Generar PDF (Ctrl+P)
    </button>
    
    <!-- Header -->
    <div class="header">
        <h1>Análisis de Impacto en Estudiantes</h1>
        <div class="subtitle">{{ $curriculum->name }}</div>
        <div class="subtitle" style="font-size: 12pt;">{{ $curriculum->institution ?? 'Universidad Nacional de Colombia' }}</div>
        <div class="date">Generado: {{ $generated_at }}</div>
    </div>
    
    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">📊 Resumen Ejecutivo</div>
        
        <div class="info-grid">
            <div class="info-card">
                <span class="value">{{ $results['total_students'] ?? 0 }}</span>
                <span class="label">Total Estudiantes</span>
            </div>
            
            <div class="info-card {{ ($results['affected_students'] ?? 0) > 0 ? 'warning' : 'success' }}">
                <span class="value">{{ $results['affected_students'] ?? 0 }}</span>
                <span class="label">Estudiantes Afectados</span>
            </div>
            
            <div class="info-card {{ ($results['average_progress_change'] ?? 0) >= 0 ? 'success' : 'danger' }}">
                <span class="value">{{ ($results['average_progress_change'] ?? 0) > 0 ? '+' : '' }}{{ number_format($results['average_progress_change'] ?? 0, 1) }}%</span>
                <span class="label">Cambio Promedio</span>
            </div>
            
            <div class="info-card {{ ($results['affected_percentage'] ?? 0) > 50 ? 'danger' : 'success' }}">
                <span class="value">{{ number_format($results['affected_percentage'] ?? 0, 1) }}%</span>
                <span class="label">% Afectado</span>
            </div>
        </div>
        
        <div class="summary-box">
            <h3>Distribución del Impacto</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 10px;">
                <div>
                    <strong style="color: #27ae60;">✓ Progreso Mejorado</strong>
                    <p style="font-size: 16pt; color: #27ae60; font-weight: bold;">{{ $results['students_with_improved_progress'] ?? 0 }}</p>
                    <p style="font-size: 9pt; color: #7f8c8d;">Estudiantes beneficiados</p>
                </div>
                <div>
                    <strong style="color: #e74c3c;">✗ Progreso Reducido</strong>
                    <p style="font-size: 16pt; color: #e74c3c; font-weight: bold;">{{ $results['students_with_reduced_progress'] ?? 0 }}</p>
                    <p style="font-size: 9pt; color: #7f8c8d;">Estudiantes perjudicados</p>
                </div>
                <div>
                    <strong style="color: #95a5a6;">= Sin Cambio</strong>
                    <p style="font-size: 16pt; color: #95a5a6; font-weight: bold;">{{ $results['students_with_no_change'] ?? 0 }}</p>
                    <p style="font-size: 9pt; color: #7f8c8d;">Sin cambio significativo</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Credit Limits Configuration -->
    <div class="section">
        <div class="section-title">⚙️ Configuración de Límites de Créditos</div>
        
        <div class="component-limits">
            <div class="component-limit-item">
                <strong>🔵 Libre Elección</strong>
                <span>{{ $credit_limits['max_free_elective_credits'] ?? 36 }} créditos</span>
            </div>
            <div class="component-limit-item">
                <strong>🟢 Disciplinar Optativo</strong>
                <span>{{ $credit_limits['max_optional_professional_credits'] ?? 9 }} créditos</span>
            </div>
            <div class="component-limit-item">
                <strong>🟡 Fundamental Optativo</strong>
                <span>{{ $credit_limits['max_optional_fundamental_credits'] ?? 6 }} créditos</span>
            </div>
            <div class="component-limit-item">
                <strong>🔴 Nivelación</strong>
                <span>{{ $credit_limits['max_leveling_credits'] ?? 12 }} créditos</span>
            </div>
            <div class="component-limit-item">
                <strong>🟡 Fundamental Obligatorio</strong>
                <span>{{ $credit_limits['max_required_fundamental_credits'] ?? 60 }} créditos</span>
            </div>
            <div class="component-limit-item">
                <strong>🟢 Disciplinar Obligatorio</strong>
                <span>{{ $credit_limits['max_required_professional_credits'] ?? 80 }} créditos</span>
            </div>
            <div class="component-limit-item">
                <strong>📘 Trabajo de Grado</strong>
                <span>{{ $credit_limits['max_thesis_credits'] ?? 6 }} créditos</span>
            </div>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- Detailed Student List -->
    <div class="section">
        <div class="section-title">👥 Detalle por Estudiante</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Documento</th>
                    <th style="width: 25%;">Nombre</th>
                    <th style="width: 12%; text-align: center;">Progreso<br>Original</th>
                    <th style="width: 12%; text-align: center;">Progreso<br>Nuevo</th>
                    <th style="width: 10%; text-align: center;">Cambio</th>
                    <th style="width: 10%; text-align: center;">Materias<br>Convalidadas</th>
                    <th style="width: 10%; text-align: center;">Materias<br>Nuevas</th>
                    <th style="width: 11%; text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($results['student_details']) && count($results['student_details']) > 0)
                    @foreach($results['student_details'] as $student)
                        <tr>
                            <td>{{ $student['document'] ?? 'N/A' }}</td>
                            <td>{{ $student['name'] ?? 'Sin nombre' }}</td>
                            <td style="text-align: center;">
                                <strong>{{ number_format($student['original_progress'] ?? 0, 1) }}%</strong>
                            </td>
                            <td style="text-align: center;">
                                <strong>{{ number_format($student['new_progress'] ?? 0, 1) }}%</strong>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $change = $student['progress_change'] ?? 0;
                                    $badgeClass = $change > 0 ? 'badge-success' : ($change < 0 ? 'badge-danger' : 'badge-secondary');
                                    $symbol = $change > 0 ? '+' : '';
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ $symbol }}{{ number_format($change, 1) }}%
                                </span>
                            </td>
                            <td style="text-align: center;">{{ $student['convalidated_subjects'] ?? 0 }}</td>
                            <td style="text-align: center;">{{ $student['new_subjects'] ?? 0 }}</td>
                            <td style="text-align: center;">
                                @if($change > 0.1)
                                    <span class="badge badge-success">✓ Mejoró</span>
                                @elseif($change < -0.1)
                                    <span class="badge badge-danger">✗ Empeoró</span>
                                @else
                                    <span class="badge badge-secondary">= Igual</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                            No hay datos de estudiantes disponibles
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    <!-- Statistics by Progress Change -->
    <div class="section">
        <div class="section-title">📈 Análisis Estadístico</div>
        
        <div class="summary-box">
            <h3>Interpretación de Resultados</h3>
            <p><strong>Progreso Ajustado Promedio:</strong> 
                @if(($results['average_progress_change'] ?? 0) > 0)
                    <span style="color: #27ae60;">+{{ number_format($results['average_progress_change'], 1) }}%</span> 
                    - Los estudiantes en promedio mejoran su progreso académico con la nueva malla.
                @elseif(($results['average_progress_change'] ?? 0) < 0)
                    <span style="color: #e74c3c;">{{ number_format($results['average_progress_change'], 1) }}%</span> 
                    - Los estudiantes en promedio pierden progreso académico con la nueva malla.
                @else
                    <span style="color: #95a5a6;">0%</span> 
                    - La nueva malla no afecta significativamente el progreso promedio.
                @endif
            </p>
            
            <p><strong>Impacto General:</strong>
                @if(($results['affected_percentage'] ?? 0) < 25)
                    Bajo impacto ({{ number_format($results['affected_percentage'], 1) }}% de estudiantes afectados)
                @elseif(($results['affected_percentage'] ?? 0) < 50)
                    Impacto moderado ({{ number_format($results['affected_percentage'], 1) }}% de estudiantes afectados)
                @elseif(($results['affected_percentage'] ?? 0) < 75)
                    Impacto considerable ({{ number_format($results['affected_percentage'], 1) }}% de estudiantes afectados)
                @else
                    Impacto alto ({{ number_format($results['affected_percentage'], 1) }}% de estudiantes afectados)
                @endif
            </p>
            
            <p><strong>Recomendación:</strong>
                @if(($results['average_progress_change'] ?? 0) > 0 && ($results['affected_percentage'] ?? 0) > 50)
                    La nueva malla parece beneficiosa para la mayoría de estudiantes. Se recomienda su implementación.
                @elseif(($results['average_progress_change'] ?? 0) < 0 && ($results['affected_percentage'] ?? 0) > 50)
                    La nueva malla perjudica a la mayoría de estudiantes. Se recomienda revisar las convalidaciones antes de implementar.
                @else
                    El impacto es mixto. Se recomienda analizar casos individuales antes de tomar una decisión.
                @endif
            </p>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 9pt;">
        <p><strong>Universidad Nacional de Colombia</strong></p>
        <p>Sistema de Simulación Curricular - Análisis de Impacto de Convalidaciones</p>
        <p>Este reporte fue generado automáticamente el {{ $generated_at }}</p>
    </div>
</body>
</html>
