/**
 * Leveling Subjects - Synchronization Module
 * Handles synchronization between simulation and leveling subjects table
 */

// Constants
const STORAGE_KEY = 'simulation_temporary_changes';
const CURRICULUM_ID = 'simulation';

/**
 * Load temporary changes from localStorage
 */
function loadTemporaryChanges() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) return null;
        
        const data = JSON.parse(stored);
        
        // Validate curriculum ID
        if (data.curriculumId !== CURRICULUM_ID) {
            return null;
        }
        
        return data.changes || [];
    } catch (error) {
        console.error('Error loading temporary changes:', error);
        return null;
    }
}

/**
 * Apply green borders to temporary leveling subjects in the table
 */
function highlightTemporarySubjects() {
    const changes = loadTemporaryChanges();
    if (!changes || changes.length === 0) return;
    
    // Filter only 'added' changes of type 'nivelacion'
    // The data is stored in new_value, not data
    const addedLevelingSubjects = changes.filter(change => 
        change.type === 'added' && 
        change.new_value && 
        change.new_value.type === 'nivelacion'
    );
    
    if (addedLevelingSubjects.length === 0) return;
    
    // Get all table rows and track which codes exist
    const tableRows = document.querySelectorAll('tbody tr:not(.temporary-row)');
    const existingCodes = new Set();
    
    tableRows.forEach(row => {
        const codeCell = row.querySelector('td:first-child strong');
        if (!codeCell) return;
        
        const code = codeCell.textContent.trim();
        existingCodes.add(code);
        
        // Check if this code matches any temporary subject
        const isTemporary = addedLevelingSubjects.some(change => 
            change.subject_code === code
        );
        
        if (isTemporary) {
            // Apply temporary styling (same as simulation cards)
            row.classList.add('temporary-subject');
        }
    });
    
    // Create temporary rows for subjects that don't exist in the table
    const tbody = document.querySelector('tbody');
    if (tbody) {
        addedLevelingSubjects.forEach(change => {
            if (!existingCodes.has(change.subject_code)) {
                // Subject doesn't exist in table, create temporary row
                createTemporaryRow(tbody, change);
            }
        });
    }
    
    // Show notification if there are temporary subjects
    if (addedLevelingSubjects.length > 0) {
        showTemporaryNotification(addedLevelingSubjects.length);
    }
}

/**
 * Create a temporary row for a subject that doesn't exist in the database
 */
function createTemporaryRow(tbody, change) {
    const data = change.new_value;
    
    // Check if row already exists
    const existingRow = tbody.querySelector(`tr[data-temp-code="${change.subject_code}"]`);
    if (existingRow) return; // Already created
    
    const row = document.createElement('tr');
    row.className = 'temporary-subject temporary-row';
    row.setAttribute('data-temp-code', change.subject_code);
    
    // Truncate description if too long
    const shortDescription = data.description ? 
        (data.description.length > 60 ? data.description.substring(0, 60) + '...' : data.description) : '';
    
    row.innerHTML = `
        <td class="text-center">
            <strong>${change.subject_code}</strong>
            <br><span class="badge bg-primary badge-sm mt-1" title="Esta materia fue creada desde la malla de simulación">
                <i class="fas fa-graduation-cap"></i> Oficial
            </span>
        </td>
        <td>
            <div>
                ${data.name}
                ${shortDescription ? `<br><small class="text-muted">${shortDescription}</small>` : ''}
            </div>
        </td>
        <td class="text-center">
            <span class="badge bg-info">${data.credits}</span>
        </td>
        <td class="text-center">${data.classroomHours}h</td>
        <td class="text-center">${data.studentHours}h</td>
        <td class="text-center">
            <div class="btn-group" role="group">
                <button type="button" 
                    class="btn btn-sm btn-outline-primary" 
                    onclick="editLeveling('temp_${change.subject_code}')"
                    title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" 
                    class="btn btn-sm btn-outline-danger" 
                    onclick="deleteLeveling('temp_${change.subject_code}', '${data.name.replace(/'/g, "\\'")}')"
                    title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    
    // Insert at the top of the table
    tbody.insertBefore(row, tbody.firstChild);
}

/**
 * Show notification about temporary subjects
 */
function showTemporaryNotification(count) {
    // Check if notification already exists
    if (document.getElementById('temporary-notification')) return;
    
    const notification = document.createElement('div');
    notification.id = 'temporary-notification';
    notification.className = 'alert alert-warning alert-dismissible fade show mb-4 shadow-sm';
    notification.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2">
                    <i class="fas fa-clock me-2"></i>
                    Cambios Temporales Detectados
                </h6>
                <p class="mb-2">
                    ${count === 1 ? 'Hay <strong>1 materia</strong>' : `Hay <strong>${count} materias</strong>`} 
                    de nivelación agregada(s) temporalmente desde la simulación.
                </p>
                <div class="alert alert-light border-start border-warning border-4 mb-2">
                    <small>
                        <i class="fas fa-info-circle text-info me-1"></i>
                        Estos cambios <strong>no están guardados permanentemente</strong>. 
                        Las filas temporales se identifican con un <strong>borde verde</strong> y un <strong>punto pulsante</strong>.
                    </small>
                </div>
                <div class="mt-3">
                    <a href="/simulation" class="btn btn-sm btn-primary me-2">
                        <i class="fas fa-arrow-right me-1"></i>
                        Ir a Simulación
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showTemporaryHelpModal()">
                        <i class="fas fa-question-circle me-1"></i>
                        ¿Qué significa esto?
                    </button>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Insert after the header, before the info alert
    const container = document.querySelector('.container-fluid .row .col-12');
    const header = container.querySelector('.d-flex.justify-content-between');
    header.after(notification);
}

/**
 * Show help modal about temporary subjects
 */
window.showTemporaryHelpModal = function() {
    const modalHtml = `
        <div class="modal fade" id="helpModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-question-circle me-2"></i>
                            ¿Qué son las Materias Temporales?
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading">
                                <i class="fas fa-lightbulb me-2"></i>
                                Concepto
                            </h6>
                            <p class="mb-0">
                                Las <strong>materias temporales</strong> son materias de nivelación que has agregado 
                                desde la <strong>malla curricular de simulación</strong>, pero que <strong>aún no has guardado</strong> 
                                en la base de datos.
                            </p>
                        </div>
                        
                        <h6 class="mb-3">
                            <i class="fas fa-eye me-2 text-success"></i>
                            Identificación Visual
                        </h6>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Borde verde</strong> en el lado izquierdo de la fila
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Punto verde pulsante</strong> en la esquina superior izquierda del código
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Fondo verdoso</strong> muy sutil en toda la fila
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Badge <span class="badge bg-primary"><i class="fas fa-graduation-cap"></i> Oficial</span> 
                                (creada desde la simulación)
                            </li>
                        </ul>
                        
                        <h6 class="mb-3">
                            <i class="fas fa-tasks me-2 text-primary"></i>
                            ¿Cómo Guardar los Cambios?
                        </h6>
                        <ol class="mb-4">
                            <li class="mb-2">Ve a la <strong>simulación</strong> (<code>/simulation</code>)</li>
                            <li class="mb-2">Verifica tus cambios en la malla curricular</li>
                            <li class="mb-2">Haz clic en el botón <span class="badge bg-success">Guardar Malla</span></li>
                            <li class="mb-2">Los cambios quedarán guardados, de todas formas la malla antigua quedará guardada en versiones.</li>
                            <li class="mb-2">Las filas temporales se convertirán en filas normales (sin borde verde)</li>
                        </ol>
                        
                        <h6 class="mb-3">
                            <i class="fas fa-undo me-2 text-warning"></i>
                            ¿Cómo Descartar los Cambios?
                        </h6>
                        <ol class="mb-4">
                            <li class="mb-2">Ve a la <strong>simulación</strong> (<code>/simulation</code>)</li>
                            <li class="mb-2">Haz clic en el botón <span class="badge bg-warning">Reset</span></li>
                            <li class="mb-2">Confirma la acción</li>
                            <li class="mb-2">Todos los cambios temporales se descartarán</li>
                            <li class="mb-2">Las filas temporales desaparecerán de esta tabla</li>
                        </ol>
                        
                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Importante
                            </h6>
                            <ul class="mb-0">
                                <li>Los cambios temporales <strong>persisten al refrescar la página</strong></li>
                                <li>Se <strong>sincronizan entre pestañas</strong> del navegador</li>
                                <li>Se almacenan en el <strong>navegador local</strong> (localStorage)</li>
                                <li><strong>No están en el servidor</strong> hasta que los guardes</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="/simulation" class="btn btn-primary">
                            <i class="fas fa-arrow-right me-2"></i>
                            Ir a Simulación
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing modal
    const existing = document.getElementById('helpModal');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('helpModal'));
    modal.show();
    
    // Clean up after modal is hidden
    document.getElementById('helpModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
};

/**
 * Listen for storage changes from other tabs/windows
 */
window.addEventListener('storage', function(e) {
    if (e.key === STORAGE_KEY) {
        // Reload highlights when storage changes
        // Remove old highlights first
        document.querySelectorAll('.temporary-subject').forEach(row => {
            row.classList.remove('temporary-subject');
        });
        
        // Remove temporary rows
        document.querySelectorAll('.temporary-row').forEach(row => {
            row.remove();
        });
        
        // Remove notification
        const notification = document.getElementById('temporary-notification');
        if (notification) notification.remove();
        
        // Reapply highlights
        highlightTemporarySubjects();
    }
});

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    highlightTemporarySubjects();
    
    // Recheck every 2 seconds for changes (in case storage event doesn't fire)
    setInterval(function() {
        const currentHighlights = document.querySelectorAll('.temporary-subject').length;
        const changes = loadTemporaryChanges();
        const expectedHighlights = changes ? 
            changes.filter(c => c.type === 'added' && c.new_value?.type === 'nivelacion').length : 0;
        
        // If counts don't match, re-highlight
        if (currentHighlights !== expectedHighlights) {
            document.querySelectorAll('.temporary-subject').forEach(row => {
                row.classList.remove('temporary-subject');
            });
            // Remove temporary rows
            document.querySelectorAll('.temporary-row').forEach(row => {
                row.remove();
            });
            // Remove notification
            const notification = document.getElementById('temporary-notification');
            if (notification) notification.remove();
            
            highlightTemporarySubjects();
        }
    }, 2000);
});

/**
 * Edit temporary leveling subject
 * Opens edit modal with data from localStorage
 */
window.editTemporaryLeveling = function(code) {
    const changes = loadTemporaryChanges();
    if (!changes) {
        showErrorModal('Error', 'No se encontraron datos temporales');
        return;
    }
    
    const change = changes.find(c => 
        c.type === 'added' && 
        c.subject_code === code &&
        c.new_value?.type === 'nivelacion'
    );
    
    if (!change) {
        showErrorModal('Error', 'No se encontró la materia temporal');
        return;
    }
    
    const data = change.new_value;
    
    // Show info modal about temporary subject
    showInfoModal(
        'Materia Temporal',
        `
        <div class="text-start">
            <p class="mb-3">
                <i class="fas fa-info-circle text-info me-2"></i>
                Esta materia <strong>aún no está guardada</strong> en la base de datos.
            </p>
            <div class="alert alert-warning mb-3">
                <strong>Importante:</strong>
                <ul class="mb-0 mt-2">
                    <li>Los cambios solo se reflejarán en la <strong>simulación</strong></li>
                    <li>Para guardar permanentemente, ve a la simulación</li>
                    <li>Haz clic en el botón <strong>"Guardar Malla"</strong></li>
                </ul>
            </div>
            <p class="mb-0 text-muted">
                <i class="fas fa-lightbulb me-2"></i>
                <small>Las materias temporales se identifican con un borde verde y un punto pulsante.</small>
            </p>
        </div>
        `,
        function() {
            // Redirect to simulation
            window.location.href = '/simulation';
        }
    );
};

/**
 * Delete temporary leveling subject
 * Removes from localStorage and redirects to simulation
 */
window.deleteTemporaryLeveling = function(code, name) {
    showConfirmModal(
        '¿Eliminar Materia Temporal?',
        `
        <div class="text-start">
            <p class="mb-3">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Estás intentando eliminar: <strong>"${name}"</strong>
            </p>
            <div class="alert alert-info mb-3">
                <strong>Información:</strong>
                <ul class="mb-0 mt-2">
                    <li>Esta materia es <strong>temporal</strong> y no está guardada en la base de datos</li>
                    <li>Para eliminarla definitivamente, debes ir a la <strong>simulación</strong></li>
                    <li>Usa el botón <strong>"Reset"</strong> para descartar todos los cambios</li>
                    <li>O elimínala manualmente desde la malla curricular</li>
                </ul>
            </div>
            <p class="mb-0">
                <i class="fas fa-question-circle text-primary me-2"></i>
                ¿Deseas ir a la simulación ahora?
            </p>
        </div>
        `,
        function() {
            // Redirect to simulation
            window.location.href = '/simulation';
        },
        'question',
        '<i class="fas fa-arrow-right me-2"></i>Ir a Simulación',
        'Cancelar'
    );
};

/**
 * Show info modal with optional action button
 */
function showInfoModal(title, message, onConfirm = null) {
    const confirmButtonHtml = onConfirm ? 
        `<button type="button" class="btn btn-primary" id="infoModalConfirm">
            <i class="fas fa-arrow-right me-2"></i>Ir a Simulación
        </button>` : '';
    
    const modalHtml = `
        <div class="modal fade" id="infoModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle me-2"></i>
                            ${title}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${message}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                        ${confirmButtonHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing modal
    const existing = document.getElementById('infoModal');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('infoModal'));
    modal.show();
    
    // Add confirm button handler if provided
    if (onConfirm) {
        document.getElementById('infoModalConfirm')?.addEventListener('click', function() {
            modal.hide();
            onConfirm();
        });
    }
    
    // Clean up after modal is hidden
    document.getElementById('infoModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

/**
 * Show confirm modal with custom styling
 */
function showConfirmModal(title, message, onConfirm, icon = 'warning', confirmText = 'Confirmar', cancelText = 'Cancelar') {
    const iconClass = {
        'warning': 'fa-exclamation-triangle text-warning',
        'question': 'fa-question-circle text-primary',
        'danger': 'fa-exclamation-circle text-danger',
        'info': 'fa-info-circle text-info'
    }[icon] || 'fa-question-circle text-primary';
    
    const headerClass = {
        'warning': 'bg-warning text-dark',
        'question': 'bg-primary text-white',
        'danger': 'bg-danger text-white',
        'info': 'bg-info text-white'
    }[icon] || 'bg-warning text-dark';
    
    const buttonClass = {
        'warning': 'btn-warning',
        'question': 'btn-primary',
        'danger': 'btn-danger',
        'info': 'btn-info'
    }[icon] || 'btn-primary';
    
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header ${headerClass}">
                        <h5 class="modal-title">
                            <i class="fas ${iconClass.split(' ')[0]} me-2"></i>
                            ${title}
                        </h5>
                        <button type="button" class="btn-close ${headerClass.includes('text-white') ? 'btn-close-white' : ''}" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${message}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            ${cancelText}
                        </button>
                        <button type="button" class="btn ${buttonClass}" id="confirmModalBtn">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing modal
    const existing = document.getElementById('confirmModal');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
    
    // Add confirm button handler
    document.getElementById('confirmModalBtn')?.addEventListener('click', function() {
        modal.hide();
        if (onConfirm) onConfirm();
    });
    
    // Clean up after modal is hidden
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
