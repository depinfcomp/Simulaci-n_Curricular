@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-history me-2"></i>
                    Historias Académicas
                </h1>
                <div class="btn-group" role="group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload me-2"></i>
                        Importar Historia Académica
                    </button>
                    @if($stats['total_records'] > 0)
                        <button class="btn btn-danger" onclick="confirmClearAll()">
                            <i class="fas fa-trash-alt me-2"></i>
                            Eliminar Historias Actuales
                        </button>
                    @endif
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['total_imports'] ?? 0 }}</h5>
                                    <p class="card-text">Importaciones</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-import fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['total_records'] ?? 0 }}</h5>
                                    <p class="card-text">Registros Totales</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-database fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['unique_students'] ?? 0 }}</h5>
                                    <p class="card-text">Estudiantes</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ number_format($stats['avg_success_rate'] ?? 0, 1) }}%</h5>
                                    <p class="card-text">Tasa de Éxito Promedio</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-chart-line fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Imports List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Historial de Importaciones
                    </h5>
                </div>
                <div class="card-body">
                    @if($imports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Fecha</th>
                                        <th>Registros</th>
                                        <th>Exitosos</th>
                                        <th>Fallidos</th>
                                        <th>Tasa Éxito</th>
                                        <th>Estado</th>
                                        <th>Usuario</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($imports as $import)
                                        @php
                                            $statusClass = [
                                                'pending' => 'secondary',
                                                'processing' => 'info',
                                                'completed' => 'success',
                                                'failed' => 'danger'
                                            ][$import->status] ?? 'secondary';
                                            
                                            $statusText = [
                                                'pending' => 'Pendiente',
                                                'processing' => 'Procesando',
                                                'completed' => 'Completado',
                                                'failed' => 'Fallido'
                                            ][$import->status] ?? 'Desconocido';
                                        @endphp
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $import->original_filename }}</strong>
                                                    @if($import->column_mapping)
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-cog me-1"></i>
                                                            Mapeo personalizado
                                                        </small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <small>{{ $import->created_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $import->total_records }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $import->successful_imports }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">{{ $import->failed_imports }}</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px; min-width: 80px;">
                                                    <div class="progress-bar bg-{{ $import->success_rate >= 80 ? 'success' : ($import->success_rate >= 50 ? 'warning' : 'danger') }}" 
                                                         style="width: {{ $import->success_rate }}%">
                                                        {{ number_format($import->success_rate, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $statusClass }}">{{ $statusText }}</span>
                                            </td>
                                            <td>
                                                <small>{{ $import->importedBy->name ?? 'Sistema' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    @if($import->status == 'completed')
                                                        <!-- Dropdown for export options -->
                                                        <div class="btn-group" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-success dropdown-toggle" 
                                                                    data-bs-toggle="dropdown" 
                                                                    aria-expanded="false"
                                                                    title="Opciones de exportación">
                                                                <i class="fas fa-download"></i>
                                                                Exportar
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('academic-history.export-successful', $import) }}">
                                                                        <i class="fas fa-check-circle text-success"></i>
                                                                        Registros Exitosos
                                                                        <span class="badge bg-success ms-1">{{ $import->successful_imports }}</span>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('academic-history.export-failed', $import) }}">
                                                                        <i class="fas fa-times-circle text-danger"></i>
                                                                        Registros Fallidos
                                                                        <span class="badge bg-danger ms-1">{{ $import->failed_imports }}</span>
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('academic-history.export', $import) }}">
                                                                        <i class="fas fa-file-csv text-primary"></i>
                                                                        Exportar Todo (Original)
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteImport({{ $import->id }})"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $imports->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No hay importaciones registradas</h5>
                            <p class="text-muted">Comienza importando un archivo Excel con historias académicas</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="fas fa-upload me-2"></i>
                                Importar Primera Historia
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>
                    Importar Historia Académica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Formato del Archivo
                        </h6>
                        <p class="mb-2"><strong>El archivo CSV debe contener estas columnas:</strong></p>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="mb-0 small">
                                    <li><code>DOCUMENTO</code></li>
                                    <li><code>COD_ASIGNATURA</code></li>
                                    <li><code>ASIGNATURA</code></li>
                                    <li><code>NOTA_NUMERICA</code></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0 small">
                                    <li><code>NOTA_ALFABETICA</code></li>
                                    <li><code>PERIODO_INSCRIPCION</code></li>
                                    <li><code>CREDITOS</code></li>
                                    <li><code>TIPO</code></li>
                                </ul>
                            </div>
                        </div>
                        <p class="mt-2 mb-0">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                El sistema procesará automáticamente los datos y creará los estudiantes con nombres aleatorios si no existen.
                            </small>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">
                            <i class="fas fa-file-csv me-1"></i>
                            Seleccionar Archivo CSV
                        </label>
                        <input type="file" class="form-control" id="file" name="file" 
                               accept=".csv" required>
                        <small class="form-text text-muted">
                            Solo archivos <strong>CSV</strong> | Tamaño máximo: 50MB
                        </small>
                    </div>

                    <div id="uploadProgress" style="display: none;">
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">0%</div>
                        </div>
                        <p class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Cargando archivo...
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <i class="fas fa-cloud-upload-alt me-1"></i>
                        Cargar Archivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/academic-history-index.js') }}?v={{ time() }}"></script>
@endpush
