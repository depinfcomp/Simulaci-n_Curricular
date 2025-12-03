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
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Análisis de Impacto en Estudiantes</h1>
        <div class="subtitle">{{ $curriculum->name }}</div>
        <div class="subtitle" style="font-size: 12pt;">{{ $curriculum->institution ?? 'Universidad Nacional de Colombia' }}</div>
        <div class="date">Generado: {{ $generated_at }}</div>
    </div>
    
    <!-- Executive Summary -->
    <div class="section">
        <div class="section-title">Resumen Ejecutivo</div>
        
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
                <span class="value">{{ number_format($results['average_progress_change'] ?? 0, 1) }}%</span>
                <span class="label">Cambio Promedio (Absoluto)</span>
                <small style="font-size: 10px; color: #7f8c8d; display: block; margin-top: 3px;">
                    Min: {{ number_format($results['min_progress_change'] ?? 0, 1) }}% | 
                    Max: {{ number_format($results['max_progress_change'] ?? 0, 1) }}%
                </small>
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
                    <strong style="color: #27ae60;">Progreso Mejorado</strong>
                    <p style="font-size: 16pt; color: #27ae60; font-weight: bold;">{{ $results['students_with_improved_progress'] ?? 0 }}</p>
                    <p style="font-size: 9pt; color: #7f8c8d;">Estudiantes beneficiados</p>
                </div>
                <div>
                    <strong style="color: #e74c3c;">Progreso Reducido</strong>
                    <p style="font-size: 16pt; color: #e74c3c; font-weight: bold;">{{ $results['students_with_reduced_progress'] ?? 0 }}</p>
                    <p style="font-size: 9pt; color: #7f8c8d;">Estudiantes perjudicados</p>
                </div>
                <div>
                    <strong style="color: #95a5a6;">Sin Cambio</strong>
                    <p style="font-size: 16pt; color: #95a5a6; font-weight: bold;">{{ $results['students_with_no_change'] ?? 0 }}</p>
                    <p style="font-size: 9pt; color: #7f8c8d;">Sin cambio significativo</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Credits by Component Comparison -->
    <div class="section">
        <div class="section-title">Comparación de Créditos por Componente</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Componente</th>
                    <th style="width: 20%; text-align: center;">Malla Original<br>(UNAL)</th>
                    <th style="width: 20%; text-align: center;">Nueva Malla<br>(Convalidaciones)</th>
                    <th style="width: 15%; text-align: center;">Diferencia</th>
                    <th style="width: 15%; text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $originalCredits = $results['original_curriculum_credits'] ?? [];
                    $newCredits = $results['convalidated_credits_by_component'] ?? [];
                    
                    $components = [
                        'fundamental_required' => 'Fundamental Obligatorio',
                        'optional_fundamental' => 'Fundamental Optativo',
                        'professional_required' => 'Disciplinar Obligatorio',
                        'optional_professional' => 'Disciplinar Optativo',
                        'free_elective' => 'Libre Elección',
                        'leveling' => 'Nivelación',
                        'thesis' => 'Trabajo de Grado',
                    ];
                    
                    $totalOriginal = 0;
                    $totalNew = 0;
                @endphp
                
                @foreach($components as $key => $label)
                    @php
                        $original = $originalCredits[$key] ?? 0;
                        $new = $newCredits[$key] ?? 0;
                        $difference = $new - $original;
                        $totalOriginal += $original;
                        $totalNew += $new;
                        
                        if ($difference > 0) {
                            $statusClass = 'badge-success';
                            $statusText = '+' . $difference;
                        } elseif ($difference < 0) {
                            $statusClass = 'badge-danger';
                            $statusText = $difference;
                        } else {
                            $statusClass = 'badge-secondary';
                            $statusText = '=';
                        }
                    @endphp
                    <tr>
                        <td><strong>{{ $label }}</strong></td>
                        <td style="text-align: center;">{{ $original }} créditos</td>
                        <td style="text-align: center;"><strong>{{ $new }} créditos</strong></td>
                        <td style="text-align: center;">
                            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                        </td>
                        <td style="text-align: center;">
                            @if($difference > 0)
                                <span style="color: #27ae60;">Mayor</span>
                            @elseif($difference < 0)
                                <span style="color: #e74c3c;">Menor</span>
                            @else
                                <span style="color: #95a5a6;">Igual</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                
                <tr style="background-color: #ecf0f1; font-weight: bold;">
                    <td><strong>TOTAL</strong></td>
                    <td style="text-align: center;">{{ $totalOriginal }} créditos</td>
                    <td style="text-align: center;">{{ $totalNew }} créditos</td>
                    <td style="text-align: center;">
                        @php
                            $totalDiff = $totalNew - $totalOriginal;
                            $totalStatusClass = $totalDiff > 0 ? 'badge-success' : ($totalDiff < 0 ? 'badge-danger' : 'badge-secondary');
                            $totalStatusText = $totalDiff > 0 ? '+' . $totalDiff : $totalDiff;
                        @endphp
                        <span class="badge {{ $totalStatusClass }}">{{ $totalStatusText }}</span>
                    </td>
                    <td style="text-align: center;">
                        @if($totalDiff > 0)
                            <span style="color: #27ae60;">Mayor</span>
                        @elseif($totalDiff < 0)
                            <span style="color: #e74c3c;">Menor</span>
                        @else
                            <span style="color: #95a5a6;">Igual</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="summary-box" style="margin-top: 15px;">
            <p><strong>Nota:</strong> Los créditos de la "Nueva Malla" son los límites por componente configurados en las convalidaciones. 
            Estos son los límites usados para calcular el progreso de migración de cada estudiante.</p>
        </div>
    </div>
    
    <div class="page-break"></div>
    
    <!-- Detailed Student List -->
    <div class="section">
        <div class="section-title">Detalle por Estudiante</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Documento</th>
                    <th style="width: 14%; text-align: center;">Progreso<br>Original</th>
                    <th style="width: 14%; text-align: center;">Progreso<br>Nuevo</th>
                    <th style="width: 12%; text-align: center;">Cambio</th>
                    <th style="width: 12%; text-align: center;">Materias<br>Convalidadas</th>
                    <th style="width: 12%; text-align: center;">Materias<br>Nuevas</th>
                    <th style="width: 12%; text-align: center;">Créditos<br>Convalidados</th>
                    <th style="width: 12%; text-align: center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($results['student_details']) && count($results['student_details']) > 0)
                    @foreach($results['student_details'] as $student)
                        <tr>
                            <td>{{ $student['document'] ?? 'N/A' }}</td>
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
                                <strong>{{ $student['total_convalidated_credits'] ?? 0 }}</strong>
                            </td>
                            <td style="text-align: center;">
                                @if($change > 0.1)
                                    <span class="badge badge-success">Mejoró</span>
                                @elseif($change < -0.1)
                                    <span class="badge badge-danger">Empeoró</span>
                                @else
                                    <span class="badge badge-secondary">Igual</span>
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
        <div class="section-title">Análisis Estadístico</div>
        
        <div class="summary-box">
            <h3>Interpretación de Resultados</h3>
            <p><strong>Variación Promedio del Progreso (Valor Absoluto):</strong> 
                <span style="color: #3498db;">{{ number_format($results['average_progress_change'], 1) }}%</span> 
                - En promedio, el progreso de los estudiantes varía en esta magnitud con la nueva malla.
            </p>
            
            <p><strong>Rango de Variación:</strong>
                Mínimo: <span style="color: {{ ($results['min_progress_change'] ?? 0) < 0 ? '#e74c3c' : '#27ae60' }};">{{ number_format($results['min_progress_change'] ?? 0, 1) }}%</span> | 
                Máximo: <span style="color: {{ ($results['max_progress_change'] ?? 0) < 0 ? '#e74c3c' : '#27ae60' }};">{{ number_format($results['max_progress_change'] ?? 0, 1) }}%</span>
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
                @if(($results['average_progress_change'] ?? 0) < 5 && ($results['affected_percentage'] ?? 0) < 50)
                    El impacto es bajo y homogéneo. La nueva malla puede implementarse con ajustes menores.
                @elseif(($results['average_progress_change'] ?? 0) > 15 || ($results['affected_percentage'] ?? 0) > 75)
                    El impacto es significativo. Se recomienda revisar casos individuales y ajustar convalidaciones.
                @else
                    El impacto es moderado. Se recomienda evaluar casos específicos antes de la implementación final.
                @endif
            </p>
        </div>
    </div>
    
    <!-- Convalidations Table -->
    <div class="section" style="page-break-before: always;">
        <div class="section-title">Tabla de Convalidaciones</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 35%;">Materia Externa</th>
                    <th style="width: 10%; text-align: center;">Créditos</th>
                    <th style="width: 35%;">Convalida a</th>
                    <th style="width: 10%; text-align: center;">Créditos</th>
                    <th style="width: 10%; text-align: center;">Componente</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($convalidations) && count($convalidations) > 0)
                    @foreach($convalidations as $convalidation)
                        <tr>
                            <td>{{ $convalidation->externalSubject->name ?? 'N/A' }}</td>
                            <td style="text-align: center;">
                                <strong>{{ $convalidation->externalSubject->credits ?? 0 }}</strong>
                            </td>
                            <td>{{ $convalidation->internalSubject->name ?? 'N/A' }}</td>
                            <td style="text-align: center;">
                                <strong>{{ $convalidation->internalSubject->credits ?? 0 }}</strong>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $type = $convalidation->internalSubject->type ?? 'unknown';
                                    $isRequired = $convalidation->internalSubject->is_required ?? true;
                                    $componentLabel = '';
                                    $componentClass = 'badge-secondary';
                                    
                                    if ($type === 'fundamental') {
                                        $componentLabel = $isRequired ? 'Fund. Oblig.' : 'Fund. Opt.';
                                        $componentClass = 'badge-warning';
                                    } elseif ($type === 'professional') {
                                        $componentLabel = $isRequired ? 'Disc. Oblig.' : 'Disc. Opt.';
                                        $componentClass = 'badge-success';
                                    } elseif ($type === 'free_elective') {
                                        $componentLabel = 'Libre Elección';
                                        $componentClass = 'badge-primary';
                                    } elseif ($type === 'nivelacion') {
                                        $componentLabel = 'Nivelación';
                                        $componentClass = 'badge-danger';
                                    } else {
                                        $componentLabel = 'Otro';
                                    }
                                @endphp
                                <span class="badge {{ $componentClass }}">{{ $componentLabel }}</span>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #7f8c8d;">
                            No hay convalidaciones directas configuradas
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    <!-- N:N Groups Table -->
    @if(isset($nnGroups) && count($nnGroups) > 0)
    <div class="section">
        <div class="section-title">Grupos N:N de Convalidación</div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Materia Externa (Nueva)</th>
                    <th style="width: 8%; text-align: center;">Créditos Nueva</th>
                    <th style="width: 32%;">Materias Internas (Viejas)</th>
                    <th style="width: 8%; text-align: center;">Créditos Viejas</th>
                    <th style="width: 8%; text-align: center;">Balance</th>
                    <th style="width: 12%; text-align: center;">Tipo Equiv.</th>
                    <th style="width: 7%; text-align: center;">Componente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nnGroups as $group)
                    @php
                        $newCredits = $group->externalSubject->credits ?? 0;
                        $oldCredits = $group->internalSubjects->sum('credits');
                        $balance = $newCredits - $oldCredits;
                        $balanceClass = $balance > 0 ? 'color: green;' : ($balance < 0 ? 'color: red;' : 'color: #666;');
                        $balanceText = $balance > 0 ? '+' . $balance : $balance;
                    @endphp
                    <tr>
                        <td>{{ $group->externalSubject->name ?? 'N/A' }}</td>
                        <td style="text-align: center;">
                            <strong>{{ $newCredits }}</strong>
                        </td>
                        <td>
                            <ul style="margin: 0; padding-left: 15px; font-size: 8pt; line-height: 1.3;">
                                @foreach($group->internalSubjects as $subject)
                                    <li>{{ $subject->name }} <small>({{ $subject->credits }} cr.)</small></li>
                                @endforeach
                            </ul>
                        </td>
                        <td style="text-align: center;">
                            <strong>{{ $oldCredits }}</strong>
                        </td>
                        <td style="text-align: center; {{ $balanceClass }} font-weight: bold;">
                            {{ $balanceText }}
                        </td>
                        <td style="text-align: center; font-size: 8pt;">
                            @php
                                $equivalenceLabels = [
                                    'all' => 'TODAS',
                                    'any' => 'CUALQUIERA',
                                    'credits' => 'CRÉDITOS'
                                ];
                                $equivalenceLabel = $equivalenceLabels[$group->equivalence_type] ?? $group->equivalence_type;
                            @endphp
                            <span class="badge badge-info">{{ $equivalenceLabel }}</span>
                            @if($group->equivalence_type === 'credits')
                                <br><small>{{ $group->equivalence_percentage }}%</small>
                            @endif
                        </td>
                        <td style="text-align: center; font-size: 8pt;">
                            @php
                                $componentLabels = [
                                    'fundamental_required' => ['label' => 'Fund. Oblig.', 'class' => 'badge-warning'],
                                    'professional_required' => ['label' => 'Prof. Oblig.', 'class' => 'badge-success'],
                                    'optional_fundamental' => ['label' => 'Opt. Fund.', 'class' => 'badge-info'],
                                    'optional_professional' => ['label' => 'Opt. Prof.', 'class' => 'badge-primary'],
                                    'free_elective' => ['label' => 'Libre Elecc.', 'class' => 'badge-secondary'],
                                    'thesis' => ['label' => 'Trab. Grado', 'class' => 'badge-dark'],
                                    'leveling' => ['label' => 'Nivelación', 'class' => 'badge-danger']
                                ];
                                $componentInfo = $componentLabels[$group->component_type] ?? ['label' => 'N/A', 'class' => 'badge-secondary'];
                            @endphp
                            <span class="badge {{ $componentInfo['class'] }}">{{ $componentInfo['label'] }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin-top: 10px; padding: 10px; background-color: #fff3cd; border-left: 4px solid #ffc107; font-size: 9pt;">
            <strong>⚠️ Balance de Créditos:</strong><br>
            • <span style="color: green; font-weight: bold;">Balance positivo (+)</span>: El estudiante gana créditos al migrar (la materia nueva vale más que las viejas)<br>
            • <span style="color: red; font-weight: bold;">Balance negativo (-)</span>: El estudiante pierde créditos al migrar (las materias viejas valían más que la nueva)<br>
            • El análisis de impacto calcula el <strong>balance neto</strong> para determinar el avance real del estudiante
        </div>
    </div>
    @endif
    
    <!-- Footer -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; text-align: center; color: #7f8c8d; font-size: 9pt;">
        <p><strong>Universidad Nacional de Colombia</strong></p>
        <p>Sistema de Simulación Curricular - Análisis de Impacto de Convalidaciones</p>
        <p>Este reporte fue generado automáticamente el {{ $generated_at }}</p>
    </div>
</body>
</html>
