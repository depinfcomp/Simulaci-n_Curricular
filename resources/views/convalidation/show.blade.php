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
                                <button class="btn btn-outline-primary btn-sm" onclick="showBulkConvalidation()">
                                    <i class="fas fa-tasks me-2"></i>
                                    Convalidación Masiva
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="getSuggestions()">
                                    <i class="fas fa-magic me-2"></i>
                                    Sugerencias Automáticas
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
                                                            <button type="button" 
                                                                    class="btn btn-outline-info"
                                                                    onclick="getSuggestions({{ $subject->id }})"
                                                                    title="Ver sugerencias">
                                                                <i class="fas fa-magic"></i>
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
                                <input class="form-check-input" type="radio" name="convalidation_type" id="type_direct" value="direct">
                                <label class="form-check-label" for="type_direct">
                                    <strong>Convalidación Directa</strong><br>
                                    <small class="text-muted">Equivale a una materia específica de nuestra malla curricular</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="convalidation_type" id="type_free" value="free_elective">
                                <label class="form-check-label" for="type_free">
                                    <strong>Libre Elección</strong><br>
                                    <small class="text-muted">Se reconoce como créditos electivos, sin equivalencia específica</small>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="convalidation_type" id="type_not_convalidated" value="not_convalidated">
                                <label class="form-check-label" for="type_not_convalidated">
                                    <strong>Materia Nueva</strong><br>
                                    <small class="text-muted">Esta es una materia nueva de la malla externa que el estudiante debe cursar</small>
                                </label>
                            </div>
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

                    <div class="row" id="suggestions_container" style="display: none;">
                        <div class="col-12">
                            <h6>Sugerencias Automáticas:</h6>
                            <div id="suggestions_list"></div>
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
@endsection

@push('scripts')
    <script src="{{ asset('js/convalidation-show.js') }}"></script>
@endpush
