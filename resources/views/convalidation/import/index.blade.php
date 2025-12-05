@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-file-import me-2 text-primary"></i>
                        Importar Malla Curricular
                    </h1>
                    <p class="text-muted mb-0">Importa mallas curriculares desde archivos Excel</p>
                </div>
                <div>
                    <a href="{{ route('convalidation.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver a Convalidaciones
                    </a>
                </div>
            </div>

            <!-- Wizard Steps Indicator -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="wizard-steps">
                        <div class="step active" data-step="upload">
                            <div class="step-number">1</div>
                            <div class="step-label">Subir Archivo</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" data-step="analyze">
                            <div class="step-number">2</div>
                            <div class="step-label">Análisis</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" data-step="mapping">
                            <div class="step-number">3</div>
                            <div class="step-label">Mapeo</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" data-step="validate">
                            <div class="step-number">4</div>
                            <div class="step-label">Validación</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step" data-step="confirm">
                            <div class="step-number">5</div>
                            <div class="step-label">Confirmar</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Upload -->
            <div id="step-upload" class="wizard-content active">
                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-upload me-2"></i>
                                    Seleccionar Archivo Excel
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Template Selection -->
                                <div class="mb-4">
                                    <label class="form-label">Usar Plantilla Guardada (Opcional)</label>
                                    <select id="template-select" class="form-select">
                                        <option value="">-- Sin plantilla (detección automática) --</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Las plantillas guardan el mapeo de columnas de importaciones anteriores exitosas
                                    </small>
                                </div>

                                <!-- Drag and Drop Zone -->
                                <div id="dropzone" class="border border-2 border-dashed rounded p-5 text-center mb-4">
                                    <i class="fas fa-cloud-upload-alt fa-4x text-muted mb-3"></i>
                                    <h5>Arrastra tu archivo Excel aquí</h5>
                                    <p class="text-muted mb-3">o haz clic para seleccionar</p>
                                    <input type="file" id="file-input" accept=".xlsx,.xls,.csv" class="d-none">
                                    <button type="button" class="btn btn-outline-primary" id="btn-select-file">
                                        Seleccionar Archivo
                                    </button>
                                    <div class="mt-3">
                                        <small class="text-muted">Formatos soportados: .xlsx, .xls, .csv (Máx. 10MB)</small>
                                    </div>
                                </div>

                                <!-- File Info -->
                                <div id="file-info" class="alert alert-info d-none">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-file-excel me-2"></i>
                                            <strong id="file-name"></strong>
                                            <span id="file-size" class="text-muted ms-2"></span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Curriculum Information -->
                                <div id="curriculum-info-form" class="d-none">
                                    <hr>
                                    <h6 class="mb-3">Información de la Malla Curricular</h6>
                                    
                                    <div class="mb-3">
                                        <label for="curriculum-name" class="form-label">Nombre de la Malla <span class="text-danger">*</span></label>
                                        <input type="text" id="curriculum-name" class="form-control" placeholder="Ej: Ingeniería de Sistemas 2020" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="institution" class="form-label">Institución <span class="text-danger">*</span></label>
                                        <input type="text" id="institution" class="form-control" placeholder="Ej: Universidad Nacional de Colombia" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="year" class="form-label">Año (Opcional)</label>
                                        <input type="number" id="year" class="form-control" placeholder="Ej: 2020" min="1900" max="{{ date('Y') + 10 }}">
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="button" id="btn-upload" class="btn btn-primary btn-lg">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            Continuar al Análisis
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Analysis & Mapping -->
            <div id="step-analyze" class="wizard-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>
                            Análisis y Mapeo de Columnas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="analysis-loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Analizando...</span>
                            </div>
                            <p class="mt-3 text-muted">Analizando estructura del archivo...</p>
                        </div>

                        <div id="analysis-result" class="d-none">
                            <!-- Detection Summary -->
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Resultados del Análisis
                                </h6>
                                <ul class="mb-0">
                                    <li>Fila de encabezados detectada: <strong id="header-row-number"></strong></li>
                                    <li>Total de filas de datos: <strong id="total-data-rows"></strong></li>
                                    <li>Campos requeridos detectados: <strong id="required-fields-count"></strong>/4</li>
                                </ul>
                            </div>

                            <!-- Column Mapping Table -->
                            <h6 class="mb-3">Mapeo de Columnas</h6>
                            <p class="text-muted">Verifica que las columnas del Excel estén correctamente mapeadas a los campos del sistema.</p>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Columna Excel</th>
                                            <th>Encabezado</th>
                                            <th>Mapear a Campo</th>
                                            <th>Vista Previa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="column-mapping-table">
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-warning" id="missing-fields-warning" style="display:none;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Campos requeridos faltantes:</strong>
                                <ul id="missing-fields-list" class="mb-0 mt-2"></ul>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="goToStep('upload')">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver
                                </button>
                                <button type="button" id="btn-confirm-mapping" class="btn btn-primary">
                                    Continuar a Validación
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Validation & Fill Missing -->
            <div id="step-validate" class="wizard-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Validación de Datos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="validation-loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Validando...</span>
                            </div>
                            <p class="mt-3 text-muted">Validando datos de las materias...</p>
                        </div>

                        <div id="validation-result" class="d-none">
                            <!-- Validation Summary -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h3 class="mb-0" id="valid-rows-count">0</h3>
                                            <p class="mb-0">Filas Válidas</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body">
                                            <h3 class="mb-0" id="invalid-rows-count">0</h3>
                                            <p class="mb-0">Filas con Errores</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h3 class="mb-0" id="total-rows-count">0</h3>
                                            <p class="mb-0">Total de Filas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Errors Table (if any) -->
                            <div id="errors-section" style="display:none;">
                                <h6 class="mb-3">
                                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                                    Filas que Requieren Atención
                                </h6>
                                <p class="text-muted">Completa los campos faltantes o corrige los errores:</p>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Fila</th>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                                <th>Semestre</th>
                                                <th>Créditos</th>
                                                <th>Errores</th>
                                            </tr>
                                        </thead>
                                        <tbody id="errors-table">
                                            <!-- Populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Los campos editables están marcados en amarillo. Haz clic para editar.
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary" onclick="goToStep('analyze')">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Volver al Mapeo
                                    </button>
                                    <button type="button" id="btn-save-corrections" class="btn btn-warning">
                                        <i class="fas fa-save me-2"></i>
                                        Guardar Correcciones y Revalidar
                                    </button>
                                </div>
                            </div>

                            <!-- No Errors - Ready to Import -->
                            <div id="no-errors-section" style="display:none;">
                                <div class="alert alert-success">
                                    <h5 class="alert-heading">
                                        <i class="fas fa-check-circle me-2"></i>
                                        ¡Validación Exitosa!
                                    </h5>
                                    <p class="mb-0">Todos los datos son válidos y están listos para importar.</p>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-secondary" onclick="goToStep('analyze')">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Volver al Mapeo
                                    </button>
                                    <button type="button" id="btn-proceed-confirm" class="btn btn-success">
                                        Continuar a Confirmación
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Confirmation -->
            <div id="step-confirm" class="wizard-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-check-double me-2"></i>
                            Confirmar Importación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>
                                Resumen de Importación
                            </h6>
                            <ul class="mb-0">
                                <li>Archivo: <strong id="confirm-filename"></strong></li>
                                <li>Malla: <strong id="confirm-curriculum-name"></strong></li>
                                <li>Institución: <strong id="confirm-institution"></strong></li>
                                <li>Materias a importar: <strong id="confirm-subject-count"></strong></li>
                            </ul>
                        </div>

                        <!-- Preview Table -->
                        <h6 class="mb-3">Vista Previa (Primeras 10 Materias)</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Semestre</th>
                                        <th>Créditos</th>
                                    </tr>
                                </thead>
                                <tbody id="preview-table">
                                    <!-- Populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Save as Template -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="save-as-template">
                            <label class="form-check-label" for="save-as-template">
                                Guardar mapeo como plantilla para futuras importaciones
                            </label>
                        </div>

                        <div id="template-name-section" class="mb-4" style="display:none;">
                            <label for="template-name" class="form-label">Nombre de la Plantilla</label>
                            <input type="text" id="template-name" class="form-control" placeholder="Ej: Plantilla Ingeniería de Sistemas">
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" onclick="goToStep('validate')">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver a Validación
                            </button>
                            <button type="button" id="btn-final-import" class="btn btn-success btn-lg">
                                <i class="fas fa-check me-2"></i>
                                Confirmar e Importar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Step -->
            <div id="step-success" class="wizard-content">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                        <h3 class="mb-3">¡Importación Exitosa!</h3>
                        <p class="text-muted mb-4">La malla curricular ha sido importada correctamente.</p>
                        
                        <div class="alert alert-success mx-auto" style="max-width: 500px;">
                            <strong id="success-subject-count"></strong> materias importadas
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('convalidation.index') }}" class="btn btn-primary me-2">
                                <i class="fas fa-exchange-alt me-2"></i>
                                Ir a Convalidaciones
                            </a>
                            <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                                <i class="fas fa-upload me-2"></i>
                                Importar Otra Malla
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/curriculum-import-wizard.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('js/curriculum-import-wizard.js') }}"></script>
@endpush

@endsection
