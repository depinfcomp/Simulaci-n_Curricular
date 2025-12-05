@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Configurar Importación
                    </h1>
                    <p class="text-muted mb-0">{{ $import->original_filename }}</p>
                </div>
                <a href="{{ route('academic-history.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    Volver
                </a>
            </div>

            <!-- Progress Steps -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="badge bg-success mb-2" style="font-size: 1.2rem;">
                                <i class="fas fa-check"></i>
                            </div>
                            <h6>1. Archivo Cargado</h6>
                            <small class="text-muted">{{ $analysis['total_rows'] }} registros</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="badge bg-primary mb-2" style="font-size: 1.2rem;">
                                <i class="fas fa-cog fa-spin"></i>
                            </div>
                            <h6>2. Mapear Columnas</h6>
                            <small class="text-muted">En progreso</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="badge bg-secondary mb-2" style="font-size: 1.2rem;">
                                <i class="fas fa-play"></i>
                            </div>
                            <h6>3. Procesar Datos</h6>
                            <small class="text-muted">Pendiente</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column Mapping -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-columns me-2"></i>
                        Mapeo de Columnas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Instrucciones
                        </h6>
                        <p class="mb-0">
                            Asocia cada columna de tu archivo Excel con los campos requeridos del sistema. 
                            Las sugerencias se basan en los nombres de tus columnas.
                        </p>
                    </div>

                    <form id="mappingForm">
                        <div class="row">
                            @php
                                $requiredFields = [
                                    'student_code' => ['label' => 'Código Estudiante', 'icon' => 'fa-user', 'required' => true],
                                    'subject_code' => ['label' => 'Código Materia', 'icon' => 'fa-book', 'required' => true],
                                    'subject_name' => ['label' => 'Nombre Materia', 'icon' => 'fa-bookmark', 'required' => true],
                                    'grade' => ['label' => 'Nota', 'icon' => 'fa-star', 'required' => false],
                                    'credits' => ['label' => 'Créditos', 'icon' => 'fa-calculator', 'required' => false],
                                    'period' => ['label' => 'Período', 'icon' => 'fa-calendar', 'required' => false],
                                ];
                            @endphp

                            @foreach($requiredFields as $field => $config)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas {{ $config['icon'] }} me-1"></i>
                                        <strong>{{ $config['label'] }}</strong>
                                        @if($config['required'])
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    <select class="form-select mapping-select" 
                                            name="mapping[{{ $field }}]" 
                                            id="mapping_{{ $field }}"
                                            {{ $config['required'] ? 'required' : '' }}>
                                        <option value="">-- Seleccionar columna --</option>
                                        @foreach($analysis['headers'] as $index => $header)
                                            <option value="{{ $index }}" 
                                                    {{ isset($analysis['suggested_mapping'][$field]) && $analysis['suggested_mapping'][$field] == $index ? 'selected' : '' }}>
                                                Columna {{ $index + 1 }}: {{ $header ?: '(vacío)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if(isset($analysis['suggested_mapping'][$field]))
                                        <small class="text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Sugerencia automática aplicada
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Data -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Vista Previa (Primeras 10 filas)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    @foreach($analysis['headers'] as $index => $header)
                                        <th>
                                            <small class="text-muted">Col {{ $index + 1 }}</small><br>
                                            {{ $header ?: '(vacío)' }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analysis['preview_data'] as $rowIndex => $row)
                                    <tr>
                                        <td><strong>{{ $rowIndex + 1 }}</strong></td>
                                        @foreach($row as $cell)
                                            <td>{{ $cell ?: '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">¿Todo está correcto?</h6>
                            <small class="text-muted">
                                Verifica que las columnas estén correctamente mapeadas antes de procesar
                            </small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success btn-lg" onclick="procesarImportacion()">
                                <i class="fas fa-play me-2"></i>
                                Procesar Importación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width: 4rem; height: 4rem;" role="status">
                    <span class="visually-hidden">Procesando...</span>
                </div>
                <h5>Procesando Importación</h5>
                <p class="text-muted mb-0">
                    Esto puede tomar algunos minutos dependiendo del tamaño del archivo...
                </p>
                <div class="progress mt-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 100%">
                        Procesando...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/academic-history-preview.js') }}?v={{ time() }}"></script>
<script>
    initAcademicHistoryPreview({
        importId: {{ $import->id }},
        csrfToken: '{{ csrf_token() }}'
    });
</script>
@endpush
