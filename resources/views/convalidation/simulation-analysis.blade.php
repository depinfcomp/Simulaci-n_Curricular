@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Análisis de Simulación
                    </h1>
                    <p class="text-muted mb-0">
                        {{ $curriculum->name }} - {{ $curriculum->institution }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('convalidation.assign-components', $curriculum->id) }}" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver a Componentes
                    </a>
                    <button class="btn btn-success" onclick="createSimulation({{ $curriculum->id }})">
                        <i class="fas fa-play-circle me-2"></i>
                        Crear Simulación
                    </button>
                </div>
            </div>

            <!-- Instructions -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle me-3 mt-1"></i>
                    <div>
                        <h6 class="alert-heading mb-2">Resumen de Créditos por Componente</h6>
                        <p class="mb-0">
                            A continuación se muestra la suma total de créditos asignados a cada componente académico. 
                            Los créditos de <strong>Nivelación</strong> pueden ser ajustados para aumentar (no disminuir).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Component Credits Summary -->
            <div class="row mb-4">
                <!-- Fundamental Required -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-book me-2"></i>
                                Fundamental Obligatoria
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">{{ $creditsByComponent['fundamental_required'] ?? 0 }}</h2>
                                    <small class="text-muted">créditos asignados</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Límite: {{ $componentLimits['fundamental_required'] }}</span>
                                    @if(($creditsByComponent['fundamental_required'] ?? 0) > $componentLimits['fundamental_required'])
                                        <br><span class="badge bg-warning mt-1">Excede límite</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Required -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-briefcase me-2"></i>
                                Profesional Obligatoria
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">{{ $creditsByComponent['professional_required'] ?? 0 }}</h2>
                                    <small class="text-muted">créditos asignados</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Límite: {{ $componentLimits['professional_required'] }}</span>
                                    @if(($creditsByComponent['professional_required'] ?? 0) > $componentLimits['professional_required'])
                                        <br><span class="badge bg-warning mt-1">Excede límite</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optional Fundamental -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-stream me-2"></i>
                                Optativa Fundamental
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">{{ $creditsByComponent['optional_fundamental'] ?? 0 }}</h2>
                                    <small class="text-muted">créditos asignados</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Límite: {{ $componentLimits['optional_fundamental'] }}</span>
                                    @if(($creditsByComponent['optional_fundamental'] ?? 0) > $componentLimits['optional_fundamental'])
                                        <br><span class="badge bg-warning mt-1">Excede límite</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optional Professional -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Optativa Profesional
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">{{ $creditsByComponent['optional_professional'] ?? 0 }}</h2>
                                    <small class="text-muted">créditos asignados</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Límite: {{ $componentLimits['optional_professional'] }}</span>
                                    @if(($creditsByComponent['optional_professional'] ?? 0) > $componentLimits['optional_professional'])
                                        <br><span class="badge bg-warning mt-1">Excede límite</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Free Elective -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-secondary">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-star me-2"></i>
                                Libre Elección
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">{{ $creditsByComponent['free_elective'] ?? 0 }}</h2>
                                    <small class="text-muted">créditos asignados</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Límite: {{ $componentLimits['free_elective'] }}</span>
                                    @if(($creditsByComponent['free_elective'] ?? 0) > $componentLimits['free_elective'])
                                        <br><span class="badge bg-warning mt-1">Excede límite</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thesis -->
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 border-dark">
                        <div class="card-header bg-dark text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-graduation-cap me-2"></i>
                                Trabajo de Grado
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">{{ $creditsByComponent['thesis'] ?? 0 }}</h2>
                                    <small class="text-muted">créditos asignados</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary">Límite: {{ $componentLimits['thesis'] }}</span>
                                    @if(($creditsByComponent['thesis'] ?? 0) > $componentLimits['thesis'])
                                        <br><span class="badge bg-warning mt-1">Excede límite</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leveling Component (Editable) -->
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        Nivelación (Editable)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h3 class="mb-0">
                                <span id="calculated-leveling">{{ $creditsByComponent['leveling'] ?? 0 }}</span>
                                <small class="text-muted">créditos calculados</small>
                            </h3>
                        </div>
                        <div class="col-md-8">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Nota:</strong> Los créditos de nivelación pueden ser aumentados pero no disminuidos por debajo del valor calculado ({{ $creditsByComponent['leveling'] ?? 0 }} créditos).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Summary -->
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h4 class="text-primary mb-0">
                                {{ array_sum($creditsByComponent) }}
                            </h4>
                            <small class="text-muted">Total Créditos Asignados</small>
                        </div>
                        <div class="col-md-4">
                            <h4 class="text-success mb-0">167</h4>
                            <small class="text-muted">Créditos Requeridos (Carrera)</small>
                        </div>
                        <div class="col-md-4">
                            @php
                                $totalAssigned = array_sum($creditsByComponent);
                                $percentage = ($totalAssigned / 167) * 100;
                            @endphp
                            <h4 class="{{ $percentage >= 100 ? 'text-success' : 'text-warning' }} mb-0">
                                {{ number_format($percentage, 1) }}%
                            </h4>
                            <small class="text-muted">Porcentaje de Avance</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Simulation Form -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Configuración de Simulación
                    </h6>
                </div>
                <div class="card-body">
                    <form id="simulationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de la Simulación *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="simulation_name" 
                                       placeholder="Ej: Simulación Convalidación 2025-1"
                                       required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Créditos de Nivelación</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="leveling_credits" 
                                       min="{{ $creditsByComponent['leveling'] ?? 0 }}"
                                       value="{{ $creditsByComponent['leveling'] ?? 0 }}"
                                       placeholder="Mínimo: {{ $creditsByComponent['leveling'] ?? 0 }}">
                                <small class="text-muted">
                                    Solo puede aumentar, no disminuir de {{ $creditsByComponent['leveling'] ?? 0 }}
                                </small>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Descripción (Opcional)</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          rows="3"
                                          placeholder="Descripción de la simulación..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/convalidation-simulation-analysis.js') }}?v={{ time() }}"></script>
<script>
    initSimulationAnalysis({
        createRoute: '{{ route("convalidation.simulation.create") }}',
        csrfToken: '{{ csrf_token() }}',
        minLevelingCredits: {{ $creditsByComponent['leveling'] ?? 0 }}
    });
</script>
@endpush
@endsection
