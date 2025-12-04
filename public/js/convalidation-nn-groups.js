/**
 * Convalidation N:N Groups Management
 * Sistema para gestionar grupos de convalidación N:N (1 externa = N internas)
 * 
 * Features:
 * - Visualizar malla nueva con cambios (añadido, eliminado, modificado, movido)
 * - Seleccionar materias del estado ANTERIOR para equivalencias
 * - Modal similar al de prerrequisitos con búsqueda
 * - Soporte para tipos: ALL, ANY, CREDITS
 */

// Global state
window.nnGroupsState = {
    currentExternalSubject: null,
    selectedInternalSubjects: [],
    originalCurriculumState: null, // Estado ANTES de los cambios
    newCurriculumState: null, // Estado DESPUÉS de los cambios (con indicadores)
    groups: []
};

/**
 * Initialize N:N groups management
 */
function initializeNNGroups() {
    console.log('Initializing N:N Groups Management...');
    
    // Load groups from backend
    loadExistingGroups();
    
    // Attach event listeners to "Configurar Convalidación Múltiple" buttons
    attachNNGroupButtons();
}

/**
 * Load existing N:N groups from backend
 */
async function loadExistingGroups() {
    const curriculumId = document.querySelector('[data-external-curriculum-id]')?.dataset.externalCurriculumId;
    if (!curriculumId) return;
    
    try {
        const response = await fetch(`/convalidation/${curriculumId}/groups`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            window.nnGroupsState.groups = data.groups || [];
            console.log('Loaded N:N groups:', window.nnGroupsState.groups);
            
            // Update UI to show existing groups
            updateGroupIndicators();
        }
    } catch (error) {
        console.error('Error loading N:N groups:', error);
    }
}

/**
 * Update UI indicators for subjects that have N:N groups
 */
function updateGroupIndicators() {
    window.nnGroupsState.groups.forEach(group => {
        const row = document.querySelector(`tr[data-external-subject-id="${group.external_subject_id}"]`);
        if (row) {
            // Update convalidation display cell
            const displayCell = row.querySelector(`#convalidation-display-${group.external_subject_id}`);
            if (displayCell) {
                const internalCount = group.internal_subjects ? group.internal_subjects.length : 0;
                displayCell.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-layer-group text-primary me-2"></i>
                        <div>
                            <small class="fw-bold text-primary">Conv. Múltiple</small><br>
                            <small class="text-muted">${internalCount} materia(s) equivalente(s)</small>
                        </div>
                    </div>
                `;
            }
            
            // Update status cell (Estado column - 5th td)
            const statusCell = row.querySelector('td:nth-child(5)');
            if (statusCell) {
                const componentType = group.component_type;
                const componentColor = getComponentColor(componentType);
                const componentLabel = getComponentLabel(componentType);
                
                statusCell.innerHTML = `
                    <div class="d-flex flex-column gap-1">
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Convalidada
                        </span>
                        ${componentType ? `
                        <span class="badge bg-${componentColor}">
                            <i class="fas fa-layer-group me-1"></i>${componentLabel}
                        </span>
                        ` : ''}
                    </div>
                `;
            }
            
            // Update actions cell (Acciones column - 6th td)
            const actionsCell = row.querySelector('td:nth-child(6)');
            if (actionsCell) {
                const btnGroup = actionsCell.querySelector('.btn-group-vertical');
                const existingBtn = btnGroup ? btnGroup.querySelector('.nn-group-config-btn') : null;
                
                if (existingBtn) {
                    // Update button to show "edit" icon
                    existingBtn.innerHTML = '<i class="fas fa-edit me-1"></i>Editar';
                    existingBtn.classList.remove('btn-outline-success');
                    existingBtn.classList.add('btn-outline-primary');
                    
                    // Add delete button if it doesn't exist
                    if (!btnGroup.querySelector('.nn-group-delete-btn')) {
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'btn btn-outline-danger btn-sm nn-group-delete-btn';
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteBtn.title = 'Eliminar convalidación múltiple';
                        deleteBtn.onclick = function(e) {
                            e.stopPropagation();
                            showDeleteConfirmModal(group.id);
                        };
                        
                        // Add to the button group
                        btnGroup.appendChild(deleteBtn);
                    }
                }
            }
        }
    });
}

// Helper functions to get component styling (should match convalidation-show.js)
function getComponentColor(componentType) {
    const colors = {
        'fundamental_required': 'warning',
        'professional_required': 'success',
        'optional_fundamental': 'info',
        'optional_professional': 'primary',
        'free_elective': 'secondary',
        'thesis': 'dark',
        'leveling': 'danger'
    };
    return colors[componentType] || 'secondary';
}

function getComponentLabel(componentType) {
    const labels = {
        'fundamental_required': 'Fund. Oblig.',
        'professional_required': 'Prof. Oblig.',
        'optional_fundamental': 'Opt. Fund.',
        'optional_professional': 'Opt. Prof.',
        'free_elective': 'Libre Elecc.',
        'thesis': 'Trabajo Grado',
        'leveling': 'Nivelación'
    };
    return labels[componentType] || componentType;
}

/**
 * Attach click handlers to N:N group buttons
 */
function attachNNGroupButtons() {
    document.querySelectorAll('.nn-group-config-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const externalSubjectId = this.dataset.externalSubjectId;
            const subjectName = this.dataset.subjectName;
            const subjectCode = this.dataset.subjectCode;
            const subjectCredits = this.dataset.subjectCredits;
            const changeType = this.dataset.changeType || 'unchanged';
            
            openNNGroupModal({
                id: externalSubjectId,
                name: subjectName,
                code: subjectCode,
                credits: subjectCredits,
                changeType: changeType
            });
        });
    });
}

/**
 * Open the N:N group configuration modal
 */
function openNNGroupModal(externalSubject) {
    console.log('Opening N:N modal for:', externalSubject);
    window.nnGroupsState.currentExternalSubject = externalSubject;
    
    // Load the original curriculum state (BEFORE changes)
    loadOriginalCurriculumState().then(() => {
        showNNGroupModal();
    });
}

/**
 * Load original curriculum state from backend
 * This is the state BEFORE the user made changes in simulation
 */
async function loadOriginalCurriculumState() {
    const curriculumId = document.querySelector('[data-external-curriculum-id]')?.dataset.externalCurriculumId;
    if (!curriculumId) return;
    
    try {
        const response = await fetch(`/convalidation/${curriculumId}/original-state`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            window.nnGroupsState.originalCurriculumState = data.subjects || [];
            console.log('Loaded original curriculum state:', window.nnGroupsState.originalCurriculumState);
        }
    } catch (error) {
        console.error('Error loading original curriculum state:', error);
        // Fallback: use current external subjects
        window.nnGroupsState.originalCurriculumState = getAllExternalSubjects();
    }
}

/**
 * Get all external subjects from the current page
 */
function getAllExternalSubjects() {
    const subjects = [];
    document.querySelectorAll('tr[data-external-subject-id]').forEach(row => {
        subjects.push({
            id: row.dataset.externalSubjectId,
            name: row.dataset.subjectName,
            code: row.dataset.subjectCode,
            credits: row.dataset.subjectCredits,
            change_type: row.dataset.changeType || 'unchanged'
        });
    });
    return subjects;
}

/**
 * Show the N:N group modal
 */
function showNNGroupModal() {
    const subject = window.nnGroupsState.currentExternalSubject;
    
    // Get existing group if any
    const existingGroup = window.nnGroupsState.groups.find(g => g.external_subject_id == subject.id);
    
    // Prepare selected subjects
    let selectedSubjects = [];
    let selectedSubjectsHTML = '<p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>No hay materias seleccionadas. Haz clic en "Buscar Materias"</p>';
    
    if (existingGroup && existingGroup.internal_subjects && existingGroup.internal_subjects.length > 0) {
        selectedSubjects = existingGroup.internal_subjects;
        window.nnGroupsState.selectedInternalSubjects = selectedSubjects.map(s => ({
            code: s.code,
            name: s.name,
            credits: s.credits
        }));
        selectedSubjectsHTML = generateSelectedSubjectsHTML(window.nnGroupsState.selectedInternalSubjects);
    } else {
        window.nnGroupsState.selectedInternalSubjects = [];
    }
    
    const equivalenceType = existingGroup ? existingGroup.equivalence_type : 'all';
    const creditsThreshold = existingGroup ? existingGroup.credits_threshold_percentage : 100;
    
    // Build change indicator
    const changeIndicator = getChangeIndicator(subject.changeType);
    
    const modalHtml = `
        <div class="modal fade" id="nnGroupModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-layer-group me-2"></i>
                            Configurar Convalidación Múltiple
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Subject Info with Change Indicator -->
                        <div class="alert alert-info d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <strong>Materia Externa (Nueva Malla):</strong> 
                                    <code class="bg-white text-primary px-2 py-1 rounded">${subject.code}</code>
                                    ${subject.name}
                                    <span class="badge bg-secondary ms-2">${subject.credits} créditos</span>
                                </h6>
                                ${changeIndicator}
                            </div>
                        </div>

                        <!-- Explanation -->
                        <div class="alert alert-warning">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>¿Cómo funciona?</strong> Selecciona una o más materias del <strong>estado anterior</strong> 
                            (antes de los cambios) que equivalen a esta materia nueva. Esto ayuda a mitigar el impacto en los estudiantes.
                        </div>

                        <!-- Equivalence Type Selection -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-sliders-h me-2"></i>
                                    Tipo de Equivalencia
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="equivalenceType" 
                                                   id="typeAll" value="all" ${equivalenceType === 'all' ? 'checked' : ''}>
                                            <label class="form-check-label fw-bold" for="typeAll">
                                                <i class="fas fa-check-double text-success me-1"></i>
                                                TODAS (AND)
                                            </label>
                                            <p class="text-muted small mb-0">El estudiante debe haber cursado TODAS las materias seleccionadas</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="equivalenceType" 
                                                   id="typeAny" value="any" ${equivalenceType === 'any' ? 'checked' : ''}>
                                            <label class="form-check-label fw-bold" for="typeAny">
                                                <i class="fas fa-check text-info me-1"></i>
                                                CUALQUIERA (OR)
                                            </label>
                                            <p class="text-muted small mb-0">Con haber cursado UNA de las materias seleccionadas es suficiente</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="equivalenceType" 
                                                   id="typeCredits" value="credits" ${equivalenceType === 'credits' ? 'checked' : ''}>
                                            <label class="form-check-label fw-bold" for="typeCredits">
                                                <i class="fas fa-percentage text-warning me-1"></i>
                                                POR CRÉDITOS
                                            </label>
                                            <p class="text-muted small mb-0">Debe cumplir un % de los créditos totales</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Credits Threshold (only visible when type=credits) -->
                                <div id="creditsThresholdContainer" class="mt-3" style="display: ${equivalenceType === 'credits' ? 'block' : 'none'};">
                                    <label class="form-label">Porcentaje de Créditos Requerido</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="creditsThreshold" 
                                               min="1" max="100" value="${creditsThreshold}" placeholder="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted">Por ejemplo: 80% significa que el estudiante debe haber cursado materias 
                                    que sumen al menos el 80% de los créditos totales del grupo</small>
                                </div>
                            </div>
                        </div>

                        <!-- Component Assignment -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-layer-group me-2"></i>
                                    Componente Curricular
                                </h6>
                            </div>
                            <div class="card-body">
                                <label class="form-label fw-bold">Selecciona el componente curricular al que pertenecerá esta materia</label>
                                <select class="form-select" id="componentType" required>
                                    <option value="">-- Seleccione un componente --</option>
                                    <option value="fundamental_required" ${existingGroup?.component_type === 'fundamental_required' ? 'selected' : ''}>
                                        Fundamental Obligatorio
                                    </option>
                                    <option value="professional_required" ${existingGroup?.component_type === 'professional_required' ? 'selected' : ''}>
                                        Profesional Obligatorio
                                    </option>
                                    <option value="optional_fundamental" ${existingGroup?.component_type === 'optional_fundamental' ? 'selected' : ''}>
                                        Optativa Fundamental
                                    </option>
                                    <option value="optional_professional" ${existingGroup?.component_type === 'optional_professional' ? 'selected' : ''}>
                                        Optativa Profesional
                                    </option>
                                    <option value="free_elective" ${existingGroup?.component_type === 'free_elective' ? 'selected' : ''}>
                                        Libre Elección
                                    </option>
                                    <option value="thesis" ${existingGroup?.component_type === 'thesis' ? 'selected' : ''}>
                                        Trabajo de Grado
                                    </option>
                                    <option value="leveling" ${existingGroup?.component_type === 'leveling' ? 'selected' : ''}>
                                        Nivelación
                                    </option>
                                </select>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Este componente se usará para calcular los créditos del programa y las estadísticas
                                </small>
                            </div>
                        </div>

                        <!-- Subject Selection -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Materias Equivalentes (Estado Anterior)
                                </h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="openInternalSubjectSelector()">
                                    <i class="fas fa-search me-1"></i>
                                    Buscar Materias
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="selectedInternalSubjects" class="border rounded p-3 bg-light min-height-100">
                                    ${selectedSubjectsHTML}
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-3">
                            <label class="form-label">Notas Adicionales (Opcional)</label>
                            <textarea class="form-control" id="groupNotes" rows="2" 
                                      placeholder="Ej: Esta equivalencia aplica solo para estudiantes de X cohorte...">${existingGroup ? existingGroup.notes || '' : ''}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </button>
                        ${existingGroup ? `
                        <button type="button" class="btn btn-danger" onclick="deleteNNGroup(${existingGroup.id})">
                            <i class="fas fa-trash me-1"></i>
                            Eliminar Grupo
                        </button>
                        ` : ''}
                        <button type="button" class="btn btn-success" onclick="saveNNGroup(${existingGroup ? existingGroup.id : 'null'})">
                            <i class="fas fa-save me-1"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('nnGroupModal');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('nnGroupModal'));
    modal.show();
    
    // Attach event listeners
    document.querySelectorAll('input[name="equivalenceType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('creditsThresholdContainer').style.display = 
                this.value === 'credits' ? 'block' : 'none';
        });
    });
    
    // Cleanup on close
    document.getElementById('nnGroupModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Get change indicator HTML based on change type
 */
function getChangeIndicator(changeType) {
    const indicators = {
        'added': '<span class="badge bg-success"><i class="fas fa-plus me-1"></i>AÑADIDA</span>',
        'removed': '<span class="badge bg-danger"><i class="fas fa-trash me-1"></i>ELIMINADA (Tachada en malla)</span>',
        'modified': '<span class="badge bg-warning text-dark"><i class="fas fa-edit me-1"></i>MODIFICADA (Borde amarillo)</span>',
        'moved': '<span class="badge bg-info"><i class="fas fa-arrows-alt me-1"></i>MOVIDA de semestre</span>',
        'unchanged': '<span class="badge bg-secondary"><i class="fas fa-check me-1"></i>Sin cambios</span>'
    };
    
    return indicators[changeType] || indicators['unchanged'];
}

/**
 * Generate HTML for selected internal subjects
 */
function generateSelectedSubjectsHTML(selectedSubjects) {
    if (!Array.isArray(selectedSubjects) || selectedSubjects.length === 0) {
        return '<p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>No hay materias seleccionadas</p>';
    }
    
    // If selectedSubjects is array of codes, get the full objects
    const subjects = selectedSubjects.map(item => {
        if (typeof item === 'string') {
            // It's a code, find the subject
            const found = window.nnGroupsState.selectedInternalSubjects.find(s => s.code === item);
            return found || { code: item, name: 'Cargando...', credits: 0 };
        }
        return item;
    });
    
    return subjects.map(subject => `
        <div class="selected-subject-chip d-inline-flex align-items-center bg-white border rounded p-2 me-2 mb-2">
            <div class="me-2">
                <strong class="text-primary">${subject.code}</strong>
                <small class="text-muted d-block">${subject.name}</small>
                <span class="badge bg-secondary">${subject.credits} créditos</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="removeInternalSubject('${subject.code}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

/**
 * Open the internal subject selector modal (similar to prerequisites)
 */
async function openInternalSubjectSelector() {
    // Fetch available internal subjects from UNAL curriculum
    const internalSubjects = await fetchInternalSubjects();
    
    // Get currently selected subjects
    const currentSelection = window.nnGroupsState.selectedInternalSubjects;
    
    const selectorHtml = `
        <div class="modal fade" id="internalSubjectSelectorModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-search me-2"></i>
                            Seleccionar Materias Equivalentes (Estado Anterior)
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Search Bar -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="internalSubjectSearch" 
                                       placeholder="Buscar por código o nombre de materia..." 
                                       onkeyup="filterInternalSubjects()">
                            </div>
                        </div>
                        
                        <!-- Info Alert -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Estas son las materias del <strong>estado anterior</strong> (antes de los cambios). 
                            Selecciona las que equivalen a la materia nueva.
                        </div>
                        
                        <!-- Subject List -->
                        <div style="max-height: 400px; overflow-y: auto;" id="internalSubjectList">
                            ${generateInternalSubjectListHTML(internalSubjects, currentSelection)}
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <i class="fas fa-hand-pointer me-1"></i>
                            Haga clic en una materia para seleccionarla/deseleccionarla
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" onclick="applyInternalSubjectSelection()">
                            <i class="fas fa-check me-1"></i>
                            Confirmar Selección
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', selectorHtml);
    const modal = new bootstrap.Modal(document.getElementById('internalSubjectSelectorModal'));
    modal.show();
    
    document.getElementById('internalSubjectSelectorModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Fetch internal subjects from the backend
 */
async function fetchInternalSubjects() {
    try {
        const response = await fetch('/api/subjects/all', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            return data.subjects || [];
        }
    } catch (error) {
        console.error('Error fetching internal subjects:', error);
    }
    
    // Fallback: return empty array
    return [];
}

/**
 * Generate HTML for internal subject list
 */
function generateInternalSubjectListHTML(subjects, selectedCodes) {
    if (subjects.length === 0) {
        return '<p class="text-muted text-center">No hay materias disponibles</p>';
    }
    
    return subjects.map(subject => {
        const isSelected = selectedCodes.includes(subject.code);
        return `
            <div class="internal-subject-option mb-2" data-code="${subject.code}" data-name="${subject.name}">
                <div class="internal-subject-card p-3 border rounded ${isSelected ? 'selected bg-success bg-opacity-10 border-success' : ''}" 
                     style="cursor: pointer; transition: all 0.3s ease;"
                     onclick="toggleInternalSubject(this, '${subject.code}', '${subject.name}', ${subject.credits})">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <strong class="text-primary d-block">${subject.code}</strong>
                            <div class="text-dark">${subject.name}</div>
                        </div>
                        <div class="d-flex flex-column align-items-end">
                            <span class="badge bg-secondary mb-2">${subject.credits} créditos</span>
                            <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; opacity: ${isSelected ? '1' : '0'};"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Toggle internal subject selection
 */
function toggleInternalSubject(cardElement, code, name, credits) {
    const icon = cardElement.querySelector('.fa-check-circle');
    
    if (cardElement.classList.contains('selected')) {
        // Deselect
        cardElement.classList.remove('selected', 'bg-success', 'bg-opacity-10', 'border-success');
        icon.style.opacity = '0';
        
        // Remove from selection
        const index = window.nnGroupsState.selectedInternalSubjects.findIndex(s => s.code === code);
        if (index > -1) {
            window.nnGroupsState.selectedInternalSubjects.splice(index, 1);
        }
    } else {
        // Select
        cardElement.classList.add('selected', 'bg-success', 'bg-opacity-10', 'border-success');
        icon.style.opacity = '1';
        
        // Add to selection
        window.nnGroupsState.selectedInternalSubjects.push({ code, name, credits });
    }
}

/**
 * Filter internal subjects by search term
 */
function filterInternalSubjects() {
    const searchTerm = document.getElementById('internalSubjectSearch').value.toLowerCase();
    const options = document.querySelectorAll('.internal-subject-option');
    
    options.forEach(option => {
        const code = option.dataset.code.toLowerCase();
        const name = option.dataset.name.toLowerCase();
        const matches = code.includes(searchTerm) || name.includes(searchTerm);
        option.style.display = matches ? 'block' : 'none';
    });
}

/**
 * Apply selected internal subjects
 */
function applyInternalSubjectSelection() {
    // Update the display in the main modal
    const container = document.getElementById('selectedInternalSubjects');
    const selectedCodes = window.nnGroupsState.selectedInternalSubjects.map(s => s.code);
    
    if (selectedCodes.length > 0) {
        container.innerHTML = generateSelectedSubjectsHTML(selectedCodes);
    } else {
        container.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>No hay materias seleccionadas</p>';
    }
    
    // Close selector modal
    bootstrap.Modal.getInstance(document.getElementById('internalSubjectSelectorModal')).hide();
}

/**
 * Remove an internal subject from selection
 */
function removeInternalSubject(code) {
    const index = window.nnGroupsState.selectedInternalSubjects.findIndex(s => s.code === code);
    if (index > -1) {
        window.nnGroupsState.selectedInternalSubjects.splice(index, 1);
    }
    
    // Update display
    const container = document.getElementById('selectedInternalSubjects');
    const selectedCodes = window.nnGroupsState.selectedInternalSubjects.map(s => s.code);
    
    if (selectedCodes.length > 0) {
        container.innerHTML = generateSelectedSubjectsHTML(selectedCodes);
    } else {
        container.innerHTML = '<p class="text-muted mb-0"><i class="fas fa-info-circle me-1"></i>No hay materias seleccionadas</p>';
    }
}

/**
 * Save N:N group
 */
async function saveNNGroup(groupId) {
    const externalSubjectId = window.nnGroupsState.currentExternalSubject.id;
    const externalSubjectName = window.nnGroupsState.currentExternalSubject.name;
    const equivalenceType = document.querySelector('input[name="equivalenceType"]:checked').value;
    const creditsThreshold = equivalenceType === 'credits' 
        ? parseInt(document.getElementById('creditsThreshold').value) 
        : 100;
    const notes = document.getElementById('groupNotes').value;
    const componentType = document.getElementById('componentType').value;
    const internalSubjects = window.nnGroupsState.selectedInternalSubjects;
    
    // Validation
    if (internalSubjects.length === 0) {
        alert('Debes seleccionar al menos una materia equivalente');
        return;
    }
    
    if (!componentType) {
        alert('Debes seleccionar un componente curricular');
        return;
    }
    
    if (equivalenceType === 'credits' && (creditsThreshold < 1 || creditsThreshold > 100)) {
        alert('El porcentaje de créditos debe estar entre 1 y 100');
        return;
    }
    
    // Generate group name
    const groupName = `${externalSubjectName} → ${internalSubjects.length} materias`;
    
    const payload = {
        external_curriculum_id: window.externalCurriculumId,
        external_subject_id: externalSubjectId,
        group_name: groupName,
        description: notes || `Convalidación múltiple para ${externalSubjectName}`,
        equivalence_type: equivalenceType,
        equivalence_percentage: creditsThreshold,
        component_type: componentType,
        internal_subject_codes: internalSubjects.map(s => s.code)
    };
    
    console.log('Saving N:N group:', payload);
    
    // Disable button
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
    
    try {
        const url = groupId 
            ? `/convalidation/groups/${groupId}` 
            : '/convalidation/groups';
        const method = groupId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Reload groups to update UI
            await loadExistingGroups();
            
            // Close modal
            const modal = document.getElementById('nnGroupModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
            
            // Reload page to update all statistics
            location.reload();
        } else {
            // Show specific validation errors
            let errorMessage = 'Error al guardar el grupo';
            if (data.errors) {
                const errorDetails = Object.values(data.errors).flat().join('\n');
                errorMessage += ':\n\n' + errorDetails;
            } else if (data.message) {
                errorMessage = data.message;
            }
            throw new Error(errorMessage);
        }
    } catch (error) {
        console.error('Error saving N:N group:', error);
        alert('' + error.message);
        
        // Restore button
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

/**
 * Show delete confirmation modal
 */
function showDeleteConfirmModal(groupId) {
    // Find group info
    const group = window.nnGroupsState.groups.find(g => g.id == groupId);
    const groupName = group ? group.group_name : 'este grupo';
    
    const modalHtml = `
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Confirmar Eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            ¿Estás seguro de que deseas eliminar la convalidación múltiple?
                        </p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>${groupName}</strong>
                        </div>
                        <p class="text-muted mb-0">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            Esta acción no se puede deshacer. La materia volverá a estado "Sin configurar".
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmDeleteNNGroup(${groupId})">
                            <i class="fas fa-trash me-1"></i>
                            Sí, Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('deleteConfirmModal');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

/**
 * Confirm and execute deletion
 */
async function confirmDeleteNNGroup(groupId) {
    // Close confirmation modal
    const confirmModal = document.getElementById('deleteConfirmModal');
    if (confirmModal) {
        const modalInstance = bootstrap.Modal.getInstance(confirmModal);
        if (modalInstance) modalInstance.hide();
    }
    
    // Execute deletion
    await deleteNNGroup(groupId);
}

/**
 * Delete N:N group
 */
async function deleteNNGroup(groupId) {
    
    try {
        const response = await fetch(`/convalidation/groups/${groupId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            }
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            // Find the deleted group's external_subject_id before reloading
            const deletedGroup = window.nnGroupsState.groups.find(g => g.id == groupId);
            const externalSubjectId = deletedGroup ? deletedGroup.external_subject_id : null;
            
            // Reload groups
            await loadExistingGroups();
            
            // Reset UI for the subject that had the group
            if (externalSubjectId) {
                const row = document.querySelector(`tr[data-external-subject-id="${externalSubjectId}"]`);
                if (row) {
                    // Reset display cell
                    const displayCell = row.querySelector(`#convalidation-display-${externalSubjectId}`);
                    if (displayCell) {
                        displayCell.innerHTML = `
                            <div class="d-flex align-items-center">
                                <i class="fas fa-times text-muted me-2"></i>
                                <span class="text-muted">Sin configurar</span>
                            </div>
                        `;
                    }
                    
                    // Reset status cell
                    const statusCell = row.querySelector('td:nth-child(5)');
                    if (statusCell) {
                        statusCell.innerHTML = `
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-question-circle me-1"></i>Pendiente
                            </span>
                        `;
                    }
                    
                    // Reset actions cell - restore original button
                    const actionsCell = row.querySelector('td:nth-child(6)');
                    if (actionsCell) {
                        const btnGroup = actionsCell.querySelector('.btn-group-vertical');
                        const editBtn = btnGroup ? btnGroup.querySelector('.nn-group-config-btn') : null;
                        
                        if (editBtn) {
                            editBtn.innerHTML = '<i class="fas fa-layer-group me-1"></i>Conv. Múltiple';
                            editBtn.classList.remove('btn-outline-primary');
                            editBtn.classList.add('btn-outline-success');
                        }
                        
                        // Remove delete button
                        const deleteBtn = btnGroup ? btnGroup.querySelector('.nn-group-delete-btn') : null;
                        if (deleteBtn) deleteBtn.remove();
                    }
                }
            }
            
            // Close modal if open
            const modal = document.getElementById('nnGroupModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
            
            // Reload page statistics
            if (typeof updateStatistics === 'function') {
                location.reload(); // Reload to update all statistics
            }
        } else {
            throw new Error(data.error || 'Error al eliminar el grupo');
        }
    } catch (error) {
        console.error('Error deleting N:N group:', error);
        alert('Error al eliminar el grupo: ' + error.message);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('[data-external-curriculum-id]')) {
        initializeNNGroups();
    }
});
