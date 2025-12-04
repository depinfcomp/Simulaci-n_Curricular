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
<script>
// Save component assignment for a single subject
function saveComponent(subjectId) {
    const row = document.querySelector(`tr[data-subject-id="${subjectId}"]`);
    const componentType = row.querySelector('.component-select').value;
    const notes = row.querySelector('.notes-input').value;

    if (!componentType) {
        alert('Por favor seleccione un componente académico');
        return;
    }

    const btn = row.querySelector('.btn-save-component');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('{{ route("convalidation.store-component") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            external_subject_id: subjectId,
            component_type: componentType,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            row.classList.add('table-success');
            updateStats();
            showToast('success', 'Componente asignado correctamente');
        } else {
            showToast('error', data.error || 'Error al asignar componente');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Error de conexión');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// Save all components
function saveAllComponents() {
    const rows = document.querySelectorAll('tbody tr');
    let saved = 0;
    let total = 0;

    rows.forEach(row => {
        const componentSelect = row.querySelector('.component-select');
        if (componentSelect.value) {
            total++;
            const subjectId = componentSelect.dataset.subjectId;
            saveComponent(subjectId);
            saved++;
        }
    });

    if (total === 0) {
        showToast('warning', 'No hay componentes por guardar');
    } else {
        showToast('info', `Guardando ${total} asignaciones...`);
    }
}

// Apply bulk component to all unassigned subjects
function applyBulkComponent() {
    const componentType = document.getElementById('bulkComponentType').value;
    
    if (!componentType) {
        alert('Por favor seleccione un componente');
        return;
    }

    const rows = document.querySelectorAll('tbody tr:not(.table-success)');
    
    rows.forEach(row => {
        row.querySelector('.component-select').value = componentType;
    });

    showToast('info', `Componente "${componentType}" aplicado a ${rows.length} materias pendientes`);
}

// Update statistics
function updateStats() {
    const totalRows = document.querySelectorAll('tbody tr').length;
    const assignedRows = document.querySelectorAll('tbody tr.table-success').length;
    const pendingRows = totalRows - assignedRows;
    const percentage = totalRows > 0 ? ((assignedRows / totalRows) * 100).toFixed(1) : 0;

    document.getElementById('assigned-count').textContent = assignedRows;
    document.getElementById('pending-count').textContent = pendingRows;
    document.getElementById('progress-percentage').textContent = percentage + '%';
}

// Continue to analysis
function continueToAnalysis() {
    const pendingCount = parseInt(document.getElementById('pending-count').textContent);
    
    if (pendingCount > 0) {
        if (!confirm(`Aún hay ${pendingCount} materias sin asignar componente. ¿Desea continuar de todos modos?`)) {
            return;
        }
    }

    window.location.href = '{{ route("convalidation.simulation-analysis", $curriculum->id) }}';
}

// Show toast notification
function showToast(type, message) {
    const colors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white ${colors[type] || 'bg-secondary'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    const container = document.getElementById('toast-container') || createToastContainer();
    container.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Auto-save on component change
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.component-select').forEach(select => {
        select.addEventListener('change', function() {
            const subjectId = this.dataset.subjectId;
            // Optional: auto-save on change
            // saveComponent(subjectId);
        });
    });
});
</script>
@endpush
@endsection
