<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Convalidación - {{ $externalCurriculum->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        h1 {
            font-size: 16pt;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        h2 {
            font-size: 13pt;
            color: #34495e;
            margin-top: 15px;
            margin-bottom: 10px;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 3px;
        }
        h3 {
            font-size: 11pt;
            color: #555;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .header {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        .header p {
            margin: 3px 0;
        }
        .info-row {
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }
        th {
            background-color: #3498db;
            color: white;
            padding: 6px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 5px 6px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-success {
            background-color: #27ae60;
            color: white;
        }
        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }
        .badge-warning {
            background-color: #f39c12;
            color: white;
        }
        .badge-info {
            background-color: #3498db;
            color: white;
        }
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .summary-item {
            margin: 5px 0;
        }
        .change-detail {
            font-size: 9pt;
            color: #666;
            margin-left: 10px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Comparación Curricular</h1>
        <div class="info-row">
            <span class="info-label">Malla Externa:</span>
            {{ $externalCurriculum->name }}
        </div>
        <div class="info-row">
            <span class="info-label">Institución:</span>
            {{ $externalCurriculum->institution }}
        </div>
        <div class="info-row">
            <span class="info-label">Fecha de Generación:</span>
            {{ $generatedAt }}
        </div>
    </div>

    <div class="summary-box">
        <h2>Resumen de Cambios</h2>
        <div class="summary-item">
            <strong>Estado Anterior:</strong> {{ $oldState['subjectCount'] }} asignaturas, {{ $oldState['totalCredits'] }} créditos totales
        </div>
        <div class="summary-item">
            <strong>Estado Nuevo:</strong> {{ $newState['subjectCount'] }} asignaturas, {{ $newState['totalCredits'] }} créditos totales
        </div>
        <div class="summary-item">
            <strong>Asignaturas Añadidas:</strong> <span class="badge badge-success">{{ count($differences['added']) }}</span>
        </div>
        <div class="summary-item">
            <strong>Asignaturas Eliminadas:</strong> <span class="badge badge-danger">{{ count($differences['removed']) }}</span>
        </div>
        <div class="summary-item">
            <strong>Asignaturas Modificadas:</strong> <span class="badge badge-warning">{{ count($differences['modified']) }}</span>
        </div>
        <div class="summary-item">
            <strong>Asignaturas Sin Cambios:</strong> <span class="badge badge-info">{{ count($differences['unchanged']) }}</span>
        </div>
    </div>

    @if(count($differences['added']) > 0)
    <h2>Asignaturas Añadidas</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Semestre</th>
                <th>Créditos</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($differences['added'] as $subject)
            <tr>
                <td>{{ $subject['code'] }}</td>
                <td>{{ $subject['name'] }}</td>
                <td>{{ $subject['semester'] }}</td>
                <td>{{ $subject['credits'] }}</td>
                <td>{{ ucfirst($subject['type']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($differences['removed']) > 0)
    <h2>Asignaturas Eliminadas</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Semestre</th>
                <th>Créditos</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($differences['removed'] as $subject)
            <tr>
                <td>{{ $subject['code'] }}</td>
                <td>{{ $subject['name'] }}</td>
                <td>{{ $subject['semester'] }}</td>
                <td>{{ $subject['credits'] }}</td>
                <td>{{ ucfirst($subject['type']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($differences['modified']) > 0)
    <h2>Asignaturas Modificadas</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Cambios</th>
            </tr>
        </thead>
        <tbody>
            @foreach($differences['modified'] as $item)
            <tr>
                <td>{{ $item['subject']['code'] }}</td>
                <td>{{ $item['subject']['name'] }}</td>
                <td>
                    @foreach($item['changes'] as $field => $change)
                    <div class="change-detail">
                        <strong>{{ ucfirst($field) }}:</strong> 
                        {{ $change['old'] }} → {{ $change['new'] }}
                    </div>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h2>Estado Completo de la Malla (Después de Cambios)</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Semestre</th>
                <th>Créditos</th>
                <th>Tipo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($newState['subjects'] as $subject)
            <tr>
                <td>{{ $subject['code'] }}</td>
                <td>{{ $subject['name'] }}</td>
                <td>{{ $subject['semester'] }}</td>
                <td>{{ $subject['credits'] }}</td>
                <td>{{ ucfirst($subject['type']) }}</td>
                <td>
                    @if(isset($subject['isAdded']) && $subject['isAdded'])
                        <span class="badge badge-success">Nueva</span>
                    @elseif(collect($differences['modified'])->where('subject.code', $subject['code'])->isNotEmpty())
                        <span class="badge badge-warning">Modificada</span>
                    @else
                        <span class="badge badge-info">Sin cambios</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el Sistema de Simulación Curricular</p>
        <p>{{ config('app.name') }} - {{ now()->format('Y') }}</p>
    </div>
</body>
</html>
