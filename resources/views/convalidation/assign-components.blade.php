@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-layer-group me-2 text-primary"></i>
                        Asignar Componentes Académicos
                    </h1>
                    <p class="text-muted mb-0">
                        {{ $curriculum->name }} - {{ $curriculum->institution }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('convalidation.show', $curriculum->id) }}" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver
                    </a>
                    <button class="btn btn-success" id="btnContinueAnalysis" onclick="continueToAnalysis()">
                        <i class="fas fa-chart-bar me-2"></i>
                        Continuar al Análisis
                    </button>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle me-3 mt-1"></i>
                    <div>
                        <h6 class="alert-heading mb-2">Instrucciones</h6>
                        <p class="mb-0">
                            Asigne el tipo de componente académico a cada materia externa. Esto determinará cómo se contabilizarán los créditos en el análisis de convalidación.
                        </p>
                        <small class="text-muted">
                            Una vez asignados todos los componentes, podrá continuar al análisis para ver la sumatoria de créditos por componente.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Progress Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="text-primary mb-0" id="assigned-count">
                                {{ $curriculum->externalSubjects()->whereHas('assignedComponent')->count() }}
                            </h5>
                            <small class="text-muted">Asignadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h5 class="text-warning mb-0" id="pending-count">
                                {{ $curriculum->externalSubjects()->whereDoesntHave('assignedComponent')->count() }}
                            </h5>
                            <small class="text-muted">Pendientes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="text-info mb-0">{{ $curriculum->externalSubjects()->count() }}</h5>
                            <small class="text-muted">Total Materias</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="text-success mb-0" id="progress-percentage">
                                {{ $curriculum->externalSubjects()->count() > 0 ? number_format(($curriculum->externalSubjects()->whereHas('assignedComponent')->count() / $curriculum->externalSubjects()->count()) * 100, 1) : 0 }}%
                            </h5>
                            <small class="text-muted">Completado</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subjects Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Materias Externas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">Código</th>
                                    <th style="width: 25%;">Nombre</th>
                                    <th style="width: 8%;" class="text-center">Créditos</th>
                                    <th style="width: 8%;" class="text-center">Semestre</th>
                                    <th style="width: 25%;">Componente Académico</th>
                                    <th style="width: 20%;">Notas</th>
                                    <th style="width: 4%;" class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($curriculum->externalSubjects->sortBy('semester') as $subject)
                                <tr data-subject-id="{{ $subject->id }}" 
                                    class="{{ $subject->assignedComponent ? 'table-success' : '' }}">
                                    <td>{{ $subject->code }}</td>
                                    <td>{{ $subject->name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $subject->credits }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $subject->semester }}</span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm component-select" 
                                                data-subject-id="{{ $subject->id }}"
                                                {{ $subject->assignedComponent ? '' : 'required' }}>
                                            <option value="">Seleccionar componente...</option>
                                            @foreach($componentTypes as $key => $label)
                                            <option value="{{ $key }}" 
                                                {{ $subject->assignedComponent && $subject->assignedComponent->component_type === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               class="form-control form-control-sm notes-input" 
                                               data-subject-id="{{ $subject->id }}"
                                               placeholder="Notas opcionales..."
                                               value="{{ $subject->assignedComponent ? $subject->assignedComponent->notes : '' }}">
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary btn-save-component" 
                                                data-subject-id="{{ $subject->id }}"
                                                onclick="saveComponent({{ $subject->id }})">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-tasks me-2"></i>
                        Acciones Rápidas
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <select class="form-select" id="bulkComponentType">
                                    <option value="">Seleccionar componente...</option>
                                    @foreach($componentTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-primary" onclick="applyBulkComponent()">
                                    <i class="fas fa-layer-group me-2"></i>
                                    Aplicar a Pendientes
                                </button>
                            </div>
                            <small class="text-muted">Aplicar el mismo componente a todas las materias sin asignar</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-outline-success" onclick="saveAllComponents()">
                                <i class="fas fa-check-double me-2"></i>
                                Guardar Todos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/convalidation-assign-components.js') }}?v={{ time() }}"></script>
<script>
    initAssignComponents({
        storeComponentRoute: '{{ route("convalidation.store-component") }}',
        analysisRoute: '{{ route("convalidation.simulation-analysis", $curriculum->id) }}',
        csrfToken: '{{ csrf_token() }}'
    });
</script>
@endpush
@endsection
