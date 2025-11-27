@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/convalidation-nn-groups.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="container-fluid" data-external-curriculum-id="{{ $externalCurriculum->id }}">
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
                    <button class="btn btn-danger" onclick="generateConvalidationReportPdf()">
                        <i class="fas fa-file-pdf me-2"></i>
                        Generar Reporte de Convalidaciones
                    </button>
                </div>
            </div>

            <!-- Progress and Stats -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="fas fa-chart-bar me-2"></i>
                                Progreso de Convalidación por Componente (Créditos)
                            </h6>
                            <div class="progress mb-3" style="height: 30px;">
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: {{ $stats['completion_percentage'] }}%"
                                     id="convalidation-progress">
                                    <strong>{{ number_format($stats['completion_percentage'], 1) }}% Configurado</strong>
                                </div>
                            </div>
                            
                            @php
                                $credits = $stats['credits_by_component'];
                                $componentLabels = [
                                    'fundamental_required' => ['label' => 'Fund. Oblig.', 'color' => 'warning', 'icon' => 'book'],
                                    'professional_required' => ['label' => 'Prof. Oblig.', 'color' => 'success', 'icon' => 'graduation-cap'],
                                    'optional_fundamental' => ['label' => 'Opt. Fund.', 'color' => 'info', 'icon' => 'book-open'],
                                    'optional_professional' => ['label' => 'Opt. Prof.', 'color' => 'primary', 'icon' => 'user-graduate'],
                                    'free_elective' => ['label' => 'Libre Elecc.', 'color' => 'secondary', 'icon' => 'star'],
                                    'thesis' => ['label' => 'Trabajo Grado', 'color' => 'dark', 'icon' => 'file-alt'],
                                    'leveling' => ['label' => 'Nivelación', 'color' => 'danger', 'icon' => 'level-up-alt'],
                                    'pending' => ['label' => 'Sin Configurar', 'color' => 'light text-dark', 'icon' => 'question-circle']
                                ];
                            @endphp
                            
                            <div class="row text-center g-2">
                                @foreach($componentLabels as $key => $config)
                                    @php
                                        $creditValue = $credits[$key] ?? 0;
                                        // Only show card if there are credits OR if it's not 'pending'
                                        $shouldShow = $creditValue > 0 || $key !== 'pending';
                                    @endphp
                                    
                                    @if($shouldShow)
                                        <div class="col-lg-3 col-md-4 col-6">
                                            <div class="card border-{{ $config['color'] }} h-100">
                                                <div class="card-body p-2">
                                                    <i class="fas fa-{{ $config['icon'] }} text-{{ $config['color'] }} mb-1"></i>
                                                    <h5 class="mb-0 text-{{ $config['color'] }}" id="{{ $key }}-credits">
                                                        {{ number_format($creditValue, 1) }}
                                                    </h5>
                                                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                        {{ $config['label'] }}
                                                    </small>
                                                    <small class="text-muted" style="font-size: 0.7rem;">créditos</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-exchange-alt me-2"></i>
                                Progreso de Carrera (Doble Vista)
                            </h6>
                            
                            @php
                                $originalStats = $stats['original_curriculum_stats'];
                                $newStats = $stats['new_curriculum_stats'];
                            @endphp
                            
                            <!-- Original (UNAL) Curriculum Progress -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-university"></i> Malla Original (Actual)
                                    </small>
                                    <small class="fw-bold text-success">
                                        {{ number_format($originalStats['percentage'], 1) }}%
                                    </small>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: {{ $originalStats['percentage'] }}%"
                                         id="original-progress">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                    <span id="original-assigned">{{ number_format($originalStats['assigned_credits'], 1) }}</span> de 
                                    <span id="original-total">{{ number_format($originalStats['total_credits'], 1) }}</span> créditos asignados
                                </small>
                            </div>
                            
                            <!-- Arrow indicator -->
                            <div class="text-center my-2">
                                <i class="fas fa-arrow-down fa-2x text-primary"></i>
                            </div>
                            
                            <!-- New (External/Imported) Curriculum Progress -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-file-import"></i> Malla Nueva (Generada/Importada)
                                    </small>
                                    <small class="fw-bold text-primary">
                                        {{ number_format($newStats['percentage'], 1) }}%
                                    </small>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary" 
                                         role="progressbar" 
                                         style="width: {{ $newStats['percentage'] }}%"
                                         id="new-progress">
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                                    <span id="new-convalidated">{{ number_format($newStats['convalidated_credits'], 1) }}</span> de 
                                    <span id="new-total">{{ number_format($newStats['total_credits'], 1) }}</span> créditos convalidados
                                </small>
                            </div>
                            
                            @php
                                $creditDifference = $newStats['convalidated_credits'] - $originalStats['assigned_credits'];
                                $diffClass = $creditDifference > 0 ? 'success' : ($creditDifference < 0 ? 'danger' : 'secondary');
                                $diffIcon = $creditDifference > 0 ? 'arrow-up' : ($creditDifference < 0 ? 'arrow-down' : 'minus');
                                $diffSign = $creditDifference > 0 ? '+' : '';
                            @endphp
                            <div class="alert alert-{{ $diffClass }} p-2 mb-0 mt-3" style="font-size: 0.75rem;">
                                <i class="fas fa-{{ $diffIcon }} me-1"></i>
                                <strong>Diferencia de Créditos:</strong> 
                                {{ $diffSign }}{{ number_format($creditDifference, 1) }} créditos
                                (Original UNAL: {{ number_format($originalStats['assigned_credits'], 1) }} → 
                                Importada: {{ number_format($newStats['convalidated_credits'], 1) }})
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h6>Acciones Rápidas</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                @if(isset($stats['completion_percentage']) && $stats['completion_percentage'] >= 100)
                                    <button class="btn btn-info" onclick="showImpactAnalysisModal()">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Análisis de Impacto a Estudiantes
                                    </button>
                                @endif
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
                                                    
                                                    // Get change type and styling
                                                    $changeType = $subject->change_type ?? 'unchanged';
                                                    $rowClass = '';
                                                    $rowStyle = '';
                                                    $changeBadge = '';
                                                    $isRemoved = false;
                                                    
                                                    switch($changeType) {
                                                        case 'added':
                                                            $rowClass = 'border-start border-success border-3';
                                                            $rowStyle = 'background-color: rgba(40, 167, 69, 0.05);';
                                                            $changeBadge = '<span class="badge bg-success ms-2"><i class="fas fa-plus me-1"></i>AÑADIDA</span>';
                                                            break;
                                                        case 'removed':
                                                            $rowClass = 'border-start border-danger border-3 text-decoration-line-through';
                                                            $rowStyle = 'background-color: rgba(220, 53, 69, 0.05); opacity: 0.6;';
                                                            $changeBadge = '<span class="badge bg-danger ms-2"><i class="fas fa-trash me-1"></i>ELIMINADA</span>';
                                                            $isRemoved = true;
                                                            break;
                                                        case 'modified':
                                                            $rowClass = 'border-start border-warning border-3';
                                                            $rowStyle = 'background-color: rgba(255, 193, 7, 0.05);';
                                                            $changeBadge = '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-edit me-1"></i>MODIFICADA</span>';
                                                            break;
                                                        case 'moved':
                                                            $rowClass = 'border-start border-info border-3';
                                                            $rowStyle = 'background-color: rgba(13, 202, 240, 0.05);';
                                                            $changeBadge = '<span class="badge bg-info ms-2"><i class="fas fa-arrows-alt me-1"></i>MOVIDA</span>';
                                                            break;
                                                    }
                                                @endphp
                                                <tr id="subject-row-{{ $subject->id }}"
                                                    class="{{ $rowClass }}"
                                                    style="{{ $rowStyle }}"
                                                    data-external-subject-id="{{ $subject->id }}"
                                                    data-convalidation-type="{{ $isConvalidated ? $convalidationStatus['type'] : '' }}"
                                                    data-subject-name="{{ $subject->name }}"
                                                    data-subject-code="{{ $subject->code }}"
                                                    data-subject-credits="{{ $subject->credits }}"
                                                    data-change-type="{{ $changeType }}"
                                                    @if($isConvalidated && $convalidationStatus['type'] === 'direct' && isset($convalidationStatus['internal_subject']))
                                                        data-internal-subject-name="{{ $convalidationStatus['internal_subject']->name }}"
                                                        data-internal-subject-code="{{ $convalidationStatus['internal_subject']->code }}"
                                                        data-internal-credits="{{ $convalidationStatus['internal_subject']->credits }}"
                                                    @endif
                                                    @if($componentType)
                                                        data-component-type="{{ $componentType }}"
                                                    @endif
                                                >
                                                    <td>
                                                        <code class="text-primary">{{ $subject->code }}</code>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <h6 class="mb-1 {{ $isRemoved ? 'text-decoration-line-through text-muted' : '' }}">
                                                                {{ $subject->name }}
                                                                {!! $changeBadge !!}
                                                            </h6>
                                                            @if($subject->description)
                                                                <small class="text-muted">{{ Str::limit($subject->description, 60) }}</small>
                                                            @endif
                                                            @if($changeType === 'modified' && $subject->original_semester)
                                                                <small class="text-muted d-block">
                                                                    <i class="fas fa-info-circle me-1"></i>
                                                                    Antes: Semestre {{ $subject->original_semester }}
                                                                </small>
                                                            @endif
                                                            @if($changeType === 'moved' && $subject->original_semester)
                                                                <small class="text-info d-block">
                                                                    <i class="fas fa-arrow-right me-1"></i>
                                                                    Movida de semestre {{ $subject->original_semester }} → {{ $subject->semester }}
                                                                </small>
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
                                                            @elseif($convalidationStatus['type'] === 'flexible_component')
                                                                <div class="d-flex align-items-center">
                                                                    <i class="fas fa-layer-group text-info me-2"></i>
                                                                    <span class="fw-bold text-info">{{ $componentLabel }}</span>
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
                                                                @elseif($convalidationStatus['type'] === 'flexible_component')
                                                                    <span class="badge bg-info">
                                                                        <i class="fas fa-layer-group me-1"></i>
                                                                        Componente Electivo
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
                                                        @if($isRemoved)
                                                            <!-- Materias eliminadas: solo mostrar badge, no botones -->
                                                            <div class="text-center">
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-ban me-1"></i>
                                                                    No convalidable
                                                                </span>
                                                                <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                                                    Materia eliminada
                                                                </small>
                                                            </div>
                                                        @else
                                                            <!-- Materias activas: mostrar botones normales -->
                                                            <div class="btn-group-vertical btn-group-sm w-100" role="group">
                                                                <button type="button" 
                                                                        class="btn btn-outline-primary convalidation-config-btn"
                                                                        data-external-subject-id="{{ $subject->id }}"
                                                                        @if($isConvalidated && $subject->convalidation)
                                                                            data-convalidation-type="{{ $subject->convalidation->convalidation_type }}"
                                                                            data-internal-subject-code="{{ $subject->convalidation->internal_subject_code ?? '' }}"
                                                                            data-component-type="{{ $componentType }}"
                                                                            data-notes="{{ $subject->convalidation->notes ?? '' }}"
                                                                        @endif
                                                                        title="Configurar convalidación 1:1">
                                                                    <i class="fas fa-cog me-1"></i>
                                                                    Conv. 1:1
                                                                </button>
                                                                <button type="button" 
                                                                        class="btn btn-outline-success nn-group-config-btn"
                                                                        data-external-subject-id="{{ $subject->id }}"
                                                                        data-subject-name="{{ $subject->name }}"
                                                                        data-subject-code="{{ $subject->code }}"
                                                                        data-subject-credits="{{ $subject->credits }}"
                                                                        data-change-type="{{ $subject->change_type ?? 'unchanged' }}"
                                                                        title="Configurar grupo N:N (1 = múltiples)">
                                                                    <i class="fas fa-layer-group me-1"></i>
                                                                    Grupo N:N
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
                                                        @endif
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
                                <input class="form-check-input" type="radio" name="convalidation_type" id="type_flexible_component" value="flexible_component">
                                <label class="form-check-label" for="type_flexible_component">
                                    <strong>Componente Electivo (Optativa / Libre Elección)</strong><br>
                                    <small class="text-muted">Esta materia cuenta como créditos de un componente electivo (optativas o libre elección), sin equivalencia específica</small>
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
                                <option value="fundamental_required" data-component-category="required">Fundamental Obligatoria</option>
                                <option value="professional_required" data-component-category="required">Profesional Obligatoria</option>
                                <option value="optional_fundamental" data-component-category="elective">Optativa Fundamental</option>
                                <option value="optional_professional" data-component-category="elective">Optativa Profesional</option>
                                <option value="free_elective" data-component-category="elective">Libre Elección</option>
                                <option value="thesis" data-component-category="required">Trabajo de Grado</option>
                                <option value="leveling" data-component-category="required">Nivelación</option>
                            </select>
                            <small class="text-muted" id="component_type_hint">
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
                                    @php
                                        // Get component type for filtering
                                        $componentType = $subject->component;
                                        // Determine if this is an elective component
                                        $isElective = in_array($componentType, ['optional_fundamental', 'optional_professional', 'free_elective']);
                                        $subjectCategory = $isElective ? 'elective' : 'required';
                                    @endphp
                                    <option value="{{ $subject->code }}" 
                                            data-semester="{{ $subject->semester }}" 
                                            data-credits="{{ $subject->credits }}"
                                            data-component-type="{{ $componentType }}"
                                            data-subject-category="{{ $subjectCategory }}">
                                        {{ $subject->name }} ({{ $subject->code }}) - Semestre {{ $subject->semester }}
                                    </option>
                                @endforeach
                            </select>
                            
                            <!-- Checkbox para crear nuevo código (solo para optativas/libres) -->
                            <div class="form-check mt-3" id="create_new_code_container" style="display: none;">
                                <input class="form-check-input" type="checkbox" id="create_new_code" name="create_new_code">
                                <label class="form-check-label" for="create_new_code">
                                    <strong>Crear nuevo código placeholder</strong><br>
                                    <small class="text-muted">
                                        Genera automáticamente un nuevo código único (ej: #LIBRE-02, #OPT-03) si no hay materias disponibles
                                    </small>
                                </label>
                            </div>
                            
                            <!-- Mensaje cuando se activa el checkbox -->
                            <div class="alert alert-info mt-3" id="new_code_message" style="display: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nuevo código:</strong> El sistema generará automáticamente el siguiente código disponible al guardar.
                            </div>
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

<!-- Impact Analysis Modal -->
<div class="modal fade" id="impactAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line me-2"></i>
                    Análisis de Impacto a Estudiantes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Description -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>¿Qué es esto?</strong> Este análisis simula cómo las convalidaciones configuradas 
                    afectarían el progreso académico de los estudiantes al migrar del plan antiguo al nuevo plan de estudios.
                </div>

                <!-- Loading State -->
                <div id="impact-analysis-loading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Analizando...</span>
                    </div>
                    <p class="mt-3 text-muted">Calculando impacto en estudiantes...</p>
                </div>

                <!-- Results Container -->
                <div id="impact-analysis-results" style="display: none;">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h6 class="text-muted small">Créditos Convalidados</h6>
                                    <h3 class="text-success mb-0" id="impact-convalidated-credits">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h6 class="text-muted small">Créditos Perdidos</h6>
                                    <h3 class="text-danger mb-0" id="impact-lost-credits">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h6 class="text-muted small">Materias Nuevas</h6>
                                    <h3 class="text-warning mb-0" id="impact-new-subjects">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h6 class="text-muted small">Progreso Ajustado</h6>
                                    <h3 class="text-primary mb-0" id="impact-progress-percentage">0%</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credits by Component -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-layer-group me-2"></i>
                                Créditos por Componente Académico
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Componente</th>
                                            <th class="text-center">Créditos de Malla Original</th>
                                            <th class="text-center">Créditos Convalidados</th>
                                            <th class="text-center">Diferencia</th>
                                        </tr>
                                    </thead>
                                    <tbody id="impact-credits-by-component">
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Mapping Table -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-exchange-alt me-2"></i>
                                Mapeo Detallado de Convalidaciones
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Materia Externa (Importada/Nueva)</th>
                                            <th class="text-center" style="width: 80px;"></th>
                                            <th>Convalidación (Materia UNAL)</th>
                                            <th class="text-center" style="width: 150px;">Componente</th>
                                        </tr>
                                    </thead>
                                    <tbody id="impact-subject-mapping">
                                        <!-- Populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div id="impact-analysis-error" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="impact-error-message"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-danger" onclick="generateImpactPdfReportFromShow()" id="export-impact-pdf-btn" style="display: none;">
                    <i class="fas fa-file-pdf me-2"></i>
                    Generar Reporte PDF
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
    <script src="{{ asset('js/convalidation-nn-groups.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/convalidation-visual-sync.js') }}?v={{ time() }}"></script>
@endpush

