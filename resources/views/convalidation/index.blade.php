
                                        <th>Malla Curricular</th>
                                        <th>Institución</th>
                                        <th>Materias</th>
                                        <th>Convalidaciones</th>
                                        <th>Progreso</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($externalCurriculums as $curriculum)
                                        @php
                                            $stats = $curriculum->getStats();
                                            $progressPercentage = $stats['completion_percentage'];
                                            $progressClass = $progressPercentage >= 80 ? 'success' : ($progressPercentage >= 50 ? 'warning' : 'danger');
                                        @endphp
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1">{{ $curriculum->name }}</h6>
                                                    @if($curriculum->description)
                                                        <small class="text-muted">{{ Str::limit($curriculum->description, 80) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $curriculum->institution ?? 'No especificada' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    {{ $stats['total_subjects'] }} materias
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <span class="badge bg-success">{{ $stats['direct_convalidations'] }} directas</span>
                                                    <span class="badge bg-info">{{ $stats['free_electives'] }} libres</span>
                                                </div>
                                            </td>
                                            <td style="width: 200px;">
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                        <div class="progress-bar bg-{{ $progressClass }}" 
                                                             role="progressbar" 
                                                             style="width: {{ $progressPercentage }}%">
                                                            {{ number_format($progressPercentage, 1) }}%
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ $stats['convalidated_subjects'] }}/{{ $stats['total_subjects'] }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $curriculum->created_at->format('d/m/Y') }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('convalidation.show', $curriculum) }}" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Ver y editar convalidaciones">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-warning"
                                                            onclick="showImpactConfigModal({{ $curriculum->id }})"
                                                            title="Analizar impacto en estudiantes">
                                                        <i class="fas fa-users"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-info"
                                                            onclick="exportReport({{ $curriculum->id }})"
                                                            title="Exportar reporte">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteCurriculum({{ $curriculum->id }})"
                                                            title="Eliminar malla">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No hay mallas curriculares externas</h5>
                            <p class="text-muted">Comienza cargando una malla curricular externa desde Excel</p>
                            <a href="{{ route('convalidation.create') }}" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>
                                Cargar Primera Malla
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar esta malla curricular?</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Impact Analysis Modal -->
<div class="modal fade" id="impactAnalysisModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>
                    Análisis de Impacto en Estudiantes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="impactAnalysisContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Analizando...</span>
                        </div>
                        <p class="mt-3">Analizando impacto en estudiantes...</p>
                        <small class="text-muted">Esto puede tomar unos momentos</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="exportImpactBtn" style="display: none;" onclick="exportImpactResults()">
                    <i class="fas fa-download me-1"></i>
                    Exportar Resultados
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Impact Configuration Modal -->
<div class="modal fade" id="impactConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog me-2"></i>
                    Configuración del Análisis de Impacto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        ¿Qué hace este análisis?
                    </h6>
                    <p class="mb-0">
                        Simula la migración de todos los estudiantes actuales de la malla original 
                        a esta nueva malla con convalidaciones, mostrando cómo cambiaría su progreso académico.
                    </p>
                </div>

                <!-- Total de créditos de la malla -->
                <div class="alert alert-primary mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Total de Créditos de la Malla Externa
                            </h6>
                        </div>
                        <div class="col-md-4 text-end">
                            <h4 class="mb-0" id="curriculumTotalCredits">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Calculando...
                            </h4>
                        </div>
                    </div>
                </div>
                
                <form id="impactConfigForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        Límites de Créditos por Componente
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <small>
                                            <i class="fas fa-info-circle me-1"></i>
                                            Los créditos excedentes de cada componente se convalidarán como libre elección hasta el límite. 
                                            El resto no se contará en el avance. <strong>Todos los campos son obligatorios.</strong>
                                        </small>
                                    </div>
                                    
                                    <!-- Libre Elección -->
                                    <div class="mb-3">
                                        <label for="maxFreeElectiveCredits" class="form-label">
                                            <strong><i class="fas fa-circle text-primary me-1"></i> Libre Elección</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxFreeElectiveCredits" 
                                                   value="36" min="0" max="200" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Disciplinar Optativo -->
                                    <div class="mb-3">
                                        <label for="maxOptionalProfessionalCredits" class="form-label">
                                            <strong><i class="fas fa-circle text-success me-1"></i> Disciplinar Optativo</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxOptionalProfessionalCredits" 
                                                   value="9" min="0" max="200" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Fundamental Optativo -->
                                    <div class="mb-3">
                                        <label for="maxOptionalFundamentalCredits" class="form-label">
                                            <strong><i class="fas fa-circle text-warning me-1"></i> Fundamental Optativo</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxOptionalFundamentalCredits" 
                                                   value="6" min="0" max="200" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Nivelación -->
                                    <div class="mb-3">
                                        <label for="maxLevelingCredits" class="form-label">
                                            <strong><i class="fas fa-circle text-danger me-1"></i> Nivelación</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxLevelingCredits" 
                                                   value="12" min="0" max="200" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-sliders-h me-2"></i>
                                        Límites Adicionales
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Fundamental Obligatorio -->
                                    <div class="mb-3">
                                        <label for="maxRequiredFundamentalCredits" class="form-label">
                                            <strong><i class="fas fa-circle text-warning me-1"></i> Fundamental Obligatorio</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxRequiredFundamentalCredits" 
                                                   value="60" min="0" max="200" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Disciplinar Obligatorio -->
                                    <div class="mb-3">
                                        <label for="maxRequiredProfessionalCredits" class="form-label">
                                            <strong><i class="fas fa-circle text-success me-1"></i> Disciplinar Obligatorio</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxRequiredProfessionalCredits" 
                                                   value="80" min="0" max="200" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Trabajo de Grado -->
                                    <div class="mb-3">
                                        <label for="maxThesisCredits" class="form-label">
                                            <strong><i class="fas fa-book me-1"></i> Trabajo de Grado</strong>
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="maxThesisCredits" 
                                                   value="6" min="0" max="50" step="1" required>
                                            <span class="input-group-text">créditos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="runImpactAnalysis()">
                    <i class="fas fa-play me-1"></i>
                    Ejecutar Análisis
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Progress Explanation Modal -->
<div class="modal fade" id="progressExplanationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-line me-2"></i>
                    Explicación del Cambio de Progreso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <h6 id="student-name-title" class="text-primary"></h6>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="badge bg-secondary fs-6 mb-2" id="original-progress-badge">0%</div>
                            <div class="small text-muted">Progreso Original</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-arrow-right text-muted fs-4"></i>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div class="badge fs-6 mb-2" id="new-progress-badge">0%</div>
                            <div class="small text-muted">Progreso con Nueva Malla</div>
                        </div>
                    </div>
                </div>
                
                <div class="alert" id="change-summary"></div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Explicación Detallada
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="detailed-explanation" style="white-space: pre-line;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/convalidation-index.js') }}?v={{ time() }}"></script>
@endpush
