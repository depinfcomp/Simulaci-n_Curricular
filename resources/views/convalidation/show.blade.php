@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-exchange-alt me-2 text-primary"></i>
                        {{ $externalCurriculum->name }}
                    </h1>
                    <p class="text-muted mb-0">
                        {{ $externalCurriculum->institution }} 
                        <span class="ms-2">•</span>
                        <span class="ms-2">{{ $stats['total_subjects'] }} materias</span>
                        <span class="ms-2">•</span>
                        <span class="ms-2">{{ $stats['completion_percentage'] }}% convalidado</span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('convalidation.index') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver
                    </a>
                    <button class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download me-2"></i>
                        Exportar Reporte
                    </button>
                </div>
            </div>

            <!-- Progress and Stats -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6>Progreso de Convalidación</h6>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: {{ $stats['completion_percentage'] }}%"
                                     id="convalidation-progress">
                                    {{ number_format($stats['completion_percentage'], 1) }}%
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col">
                                    <h5 class="text-success" id="direct-count">{{ $stats['direct_convalidations'] }}</h5>
                                    <small class="text-muted">Convalidaciones Directas</small>
                                </div>
                                <div class="col">
                                    <h5 class="text-info" id="elective-count">{{ $stats['free_electives'] }}</h5>
                                    <small class="text-muted">Libre Elección</small>
                                </div>
                                <div class="col">
                                    <h5 class="text-warning" id="not-convalidated-count">{{ $stats['not_convalidated'] ?? 0 }}</h5>
                                    <small class="text-muted">Materias Nuevas</small>
                                </div>
                                <div class="col">
                                    <h5 class="text-secondary" id="pending-count">{{ $stats['pending_subjects'] }}</h5>
                                    <small class="text-muted">Sin Configurar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card career-completion-card">
                        <div class="card-body text-center">
                            <h6 class="text-primary mb-3">🎓 Progreso de Carrera</h6>
                            <div class="career-percentage mb-2" id="career-percentage">
                                {{ number_format($stats['career_completion_percentage'], 1) }}%
                            </div>
                            <small class="text-muted mb-3 d-block">
                                <span id="convalidated-credits">{{ number_format($stats['convalidated_credits'], 1) }}</span> de 
                                <span id="total-credits">{{ $stats['total_career_credits'] }}</span> créditos convalidados
                            </small>
                            <div class="progress" style="height: 15px;">
                                <div class="progress-bar bg-primary" 
                                     role="progressbar" 
                                     style="width: {{ $stats['career_completion_percentage'] }}%"
                                     id="career-progress">
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-info-circle"></i> 
                                Basado en equivalencias directas + libre elección
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6>Acciones Rápidas</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" onclick="showBulkConvalidationModal()">
                                    <i class="fas fa-bolt me-2"></i>
                                    Convalidación Masiva Automática
                                </button>
                                <button class="btn btn-outline-danger" onclick="confirmResetConvalidations()">
                                    <i class="fas fa-redo me-2"></i>
                                    Restablecer Convalidación
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Convalidation Interface -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="semesterTabs" role="tablist">
                        @foreach($subjectsBySemester as $semester => $subjects)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                        id="semester-{{ $semester }}-tab" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#semester-{{ $semester }}" 
                                        type="button" 
                                        role="tab">
                                    Semestre {{ $semester }}
                                    <span class="badge bg-primary ms-2">{{ count($subjects) }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="semesterTabsContent">
                        @foreach($subjectsBySemester as $semester => $subjects)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                 id="semester-{{ $semester }}" 
                                 role="tabpanel">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 100px;">Código</th>
                                                <th>Materia Externa</th>
                                                <th style="width: 80px;">Créditos</th>
                                                <th>Convalidación</th>
                                                <th style="width: 120px;">Estado</th>
                                                <th style="width: 140px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($subjects as $subject)
                                                @php
                                                    $convalidationStatus = $subject->getConvalidationStatus();
                                                    $isConvalidated = $subject->isConvalidated();
                                                    $componentType = $subject->getComponentType();
                                                    
                                                    // Map component types to colors (same as in Subject model)
                                                    $componentColors = [
                                                        'fundamental_required' => 'warning',
                                                        'professional_required' => 'success',
                                                        'optional_fundamental' => 'warning',
                                                        'optional_professional' => 'success',
                                                        'thesis' => 'success',
                                                        'free_elective' => 'primary',
                                                        'leveling' => 'danger'
                                                    ];
                                                    
                                                    $componentLabels = [
                                                        'fundamental_required' => 'Fund. Oblig.',
                                                        'professional_required' => 'Prof. Oblig.',
                                                        'optional_fundamental' => 'Opt. Fund.',
                                                        'optional_professional' => 'Opt. Prof.',
                                                        'thesis' => 'Trabajo Grado',
                                                        'free_elective' => 'Libre Elecc.',
                                                        'leveling' => 'Nivelación'
                                                    ];
                                                    
                                                    $componentColor = $componentColors[$componentType] ?? 'secondary';
                                                    $componentLabel = $componentLabels[$componentType] ?? $componentType;
                                                @endphp
                                                <tr id="subject-row-{{ $subject->id }}">
                                                    <td>
                                                        <code class="text-primary">{{ $subject->code }}</code>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <h6 class="mb-1">{{ $subject->name }}</h6>
                                                            @if($subject->description)
                                                                <small class="text-muted">{{ Str::limit($subject->description, 60) }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">{{ $subject->credits }}</span>
                                                    </td>
                                                    <td id="convalidation-display-{{ $subject->id }}">
                                                        @if($isConvalidated)
                                                            @if($convalidationStatus['type'] === 'direct')
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-arrow-right text-success me-2"></i>
                                                                    <div>
                                                                        <small class="fw-bold text-success">{{ $convalidationStatus['internal_subject']->name }}</small><br>
                                                                        <small class="text-muted">{{ $convalidationStatus['internal_subject']->code }}</small>
                                                                    </div>
                                                                </div>
                                                            @elseif($convalidationStatus['type'] === 'free_elective')
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-star text-info me-2"></i>
                                                                    <span class="fw-bold text-info">Libre Elección</span>
                                                                </div>
                                                            @elseif($convalidationStatus['type'] === 'not_convalidated')
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-plus-circle text-warning me-2"></i>
                                                                    <span class="fw-bold text-warning">Materia Nueva</span>
                                                                </div>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Sin convalidar
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($isConvalidated)
                                                            <div class="d-flex flex-column gap-1">
                                                                @if($convalidationStatus['type'] === 'direct')
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-check me-1"></i>
                                                                        Convalidada
                                                                    </span>
                                                                @elseif($convalidationStatus['type'] === 'free_elective')
                                                                    <span class="badge bg-info">
                                                                        <i class="fas fa-star me-1"></i>
                                                                        Libre Elección
                                                                    </span>
                                                                @elseif($convalidationStatus['type'] === 'not_convalidated')
                                                                    <span class="badge bg-warning">
                                                                        <i class="fas fa-plus-circle me-1"></i>
                                                                        Materia Nueva
                                                                    </span>
                                                                @endif
                                                                
                                                                @if($componentType)
                                                                    <span class="badge bg-{{ $componentColor }}">
                                                                        {{ $componentLabel }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Pendiente
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-outline-primary"
                                                                    onclick="showConvalidationModal({{ $subject->id }})"
                                                                    title="Configurar convalidación">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            @if($isConvalidated)
                                                                <button type="button" 
                                                                        class="btn btn-outline-danger"
                                                                        onclick="removeConvalidation({{ $subject->convalidation->id }})"
                                                                        title="Eliminar convalidación">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Convalidation Modal -->
<div class="modal fade" id="convalidationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Convalidación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="convalidationForm">
                    <input type="hidden" id="external_subject_id" name="external_subject_id">
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 id="external_subject_info"></h6>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Tipo de Convalidación</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="convalidation_type" id="type_direct" value="direct" checked>
                                <label class="form-check-label" for="type_direct">
                                    <strong>Convalidación Directa</strong><br>
                                    <small class="text-muted">Equivale a una materia específica de nuestra malla curricular</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="convalidation_type" id="type_not_convalidated" value="not_convalidated">
                                <label class="form-check-label" for="type_not_convalidated">
                                    <strong>Materia Nueva / No Convalidada</strong><br>
                                    <small class="text-muted">Esta materia no tiene equivalencia y el estudiante debe cursarla como materia nueva</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="component_type" class="form-label">
                                Componente Académico <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="component_type" name="component_type" required>
                                <option value="">Seleccionar componente...</option>
                                <option value="fundamental_required">Fundamental Obligatoria</option>
                                <option value="professional_required">Profesional Obligatoria</option>
                                <option value="optional_fundamental">Optativa Fundamental</option>
                                <option value="optional_professional">Optativa Profesional</option>
                                <option value="free_elective">Libre Elección</option>
                                <option value="thesis">Trabajo de Grado</option>
                                <option value="leveling">Nivelación</option>
                            </select>
                            <small class="text-muted">
                                Indica el tipo de componente académico al que pertenece esta materia
                            </small>
                        </div>
                    </div>

                    <div class="row mb-3" id="internal_subject_selection" style="display: none;">
                        <div class="col-12">
                            <label for="internal_subject_code" class="form-label">Materia de Nuestra Malla</label>
                            <select class="form-select" id="internal_subject_code" name="internal_subject_code">
                                <option value="">Seleccionar materia...</option>
                                @foreach($internalSubjects as $subject)
                                    <option value="{{ $subject->code }}" data-semester="{{ $subject->semester }}" data-credits="{{ $subject->credits }}">
                                        {{ $subject->name }} ({{ $subject->code }}) - Semestre {{ $subject->semester }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="notes" class="form-label">Notas Adicionales</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Observaciones sobre la convalidación..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveConvalidation()">
                    <i class="fas fa-save me-2"></i>
                    Guardar Convalidación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Convalidation Modal -->
<div class="modal fade" id="bulkConvalidationModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bolt me-2"></i>
                    Convalidación Masiva Automática
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Explanation -->
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        ¿Cómo funciona la convalidación masiva?
                    </h6>
                    <p class="mb-2">El sistema comparará automáticamente las materias externas con las materias de nuestra base de datos usando dos criterios:</p>
                    <ol class="mb-0">
                        <li><strong>Por código exacto:</strong> Si el código de la materia externa coincide exactamente con una de nuestra malla</li>
                        <li><strong>Por nombre similar:</strong> Si el nombre de la materia tiene una similitud alta (≥80%) con una de nuestra malla</li>
                    </ol>
                    <p class="mt-2 mb-0">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        Además, el sistema asignará automáticamente el mismo <strong>componente académico</strong> que tiene la materia encontrada en nuestra base de datos.
                    </p>
                </div>

                <!-- Warning -->
                <div class="alert alert-warning">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Advertencia Importante
                    </h6>
                    <p class="mb-2">
                        <strong>Las convalidaciones automáticas pueden no ser 100% correctas.</strong> 
                        El sistema hace su mejor esfuerzo, pero es importante que <strong>revises y verifiques</strong> cada convalidación después del proceso automático.
                    </p>
                    <ul class="mb-0">
                        <li><strong>Materias optativas y de libre elección</strong> serán saltadas y deberán convalidarse manualmente</li>
                        <li>La similitud de nombres puede generar <strong>falsos positivos</strong></li>
                        <li>Siempre puedes usar el botón <strong>"Restablecer Convalidación"</strong> si necesitas empezar de nuevo</li>
                    </ul>
                </div>

                <!-- Progress -->
                <div id="bulk_progress" style="display: none;">
                    <div class="progress mb-3" style="height: 25px;">
                        <div id="bulk_progress_bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">
                            0%
                        </div>
                    </div>
                    <p class="text-center text-muted" id="bulk_progress_text">Preparando...</p>
                </div>

                <!-- Results -->
                <div id="bulk_results" style="display: none;">
                    <h6 class="mb-3">Resultados de la Convalidación Masiva:</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h2 id="success_count">0</h2>
                                    <small>Convalidadas Exitosamente</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h2 id="skipped_count">0</h2>
                                    <small>Sin Coincidencias</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h2 id="error_count">0</h2>
                                    <small>Errores</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 30%;">Materia Externa</th>
                                    <th style="width: 30%;">Materia Convalidada</th>
                                    <th style="width: 20%;">Componente</th>
                                    <th style="width: 10%;">Método</th>
                                    <th style="width: 10%;">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="bulk_results_table">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="start_bulk_btn" onclick="startBulkConvalidation()">
                    <i class="fas fa-play me-2"></i>
                    Iniciar Convalidación Masiva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Convalidations Confirmation Modal -->
<div class="modal fade" id="resetConvalidationsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Restablecimiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                </div>
                <p class="mb-3">
                    Estás a punto de <strong>eliminar todas las convalidaciones</strong> realizadas para esta malla curricular.
                </p>
                <p class="mb-0">
                    Esto incluye:
                </p>
                <ul class="mb-3">
                    <li>Todas las convalidaciones directas</li>
                    <li>Todas las materias marcadas como "no convalidadas"</li>
                    <li>Todas las asignaciones de componentes curriculares</li>
                </ul>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Podrás volver a realizar las convalidaciones desde cero después de restablecer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="confirm_reset_btn" onclick="executeResetConvalidations()">
                    <i class="fas fa-redo me-2"></i>
                    Sí, Restablecer Todo
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        // Global variables for routes and CSRF token
        window.convalidationRoutes = {
            store: '{{ route("convalidation.store-convalidation") }}',
            destroy: '{{ route("convalidation.destroy-convalidation", ":id") }}',
            suggestions: '{{ route("convalidation.suggestions") }}',
            export: '{{ route("convalidation.export", $externalCurriculum) }}',
            bulkConvalidation: '{{ route("convalidation.bulk-convalidation") }}',
            reset: '{{ route("convalidation.reset", $externalCurriculum) }}'
        };
        window.csrfToken = '{{ csrf_token() }}';
        window.externalCurriculumId = {{ $externalCurriculum->id }};

        // Reset convalidations functions
        function confirmResetConvalidations() {
            const modal = new bootstrap.Modal(document.getElementById('resetConvalidationsModal'));
            modal.show();
        }

        function executeResetConvalidations() {
            const btn = document.getElementById('confirm_reset_btn');
            const originalHtml = btn.innerHTML;
            
            // Disable button and show loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Restableciendo...';

            fetch(window.convalidationRoutes.reset, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide modal
                    bootstrap.Modal.getInstance(document.getElementById('resetConvalidationsModal')).hide();
                    
                    // Reload page directly without alert
                    location.reload();
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al restablecer las convalidaciones: ' + error.message);
                
                // Restore button
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    </script>
    <script src="{{ asset('js/convalidation-show.js') }}?v={{ time() }}"></script>
@endpush

