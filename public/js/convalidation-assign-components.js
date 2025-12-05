/**
 * Convalidation Component Assignment Module
 * 
 * Manages the assignment of academic components (curricular components) to external subjects
 * during the convalidation process.
 * 
 * Features:
 * - Individual component assignment
 * - Bulk component application
 * - Progress statistics tracking
 * - Auto-save functionality
 * - Toast notifications
 * 
 * @version 1.0.0
 */

let storeComponentRoute, analysisRoute, csrfToken;

/**
 * Initialize the component assignment module
 * @param {Object} config - Configuration object
 * @param {string} config.storeComponentRoute - Route for storing component assignments
 * @param {string} config.analysisRoute - Route to navigate after completion
 * @param {string} config.csrfToken - CSRF token for requests
 */
function initAssignComponents(config) {
    storeComponentRoute = config.storeComponentRoute;
    analysisRoute = config.analysisRoute;
    csrfToken = config.csrfToken;

    setupComponentChangeListeners();
}

/**
 * Setup event listeners for component select changes
 * @private
 */
function setupComponentChangeListeners() {
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.component-select').forEach(select => {
            select.addEventListener('change', function() {
                const subjectId = this.dataset.subjectId;
                // Optional: auto-save on change
                // saveComponent(subjectId);
            });
        });
    });
}

/**
 * Save component assignment for a single subject
 * @param {number|string} subjectId - The external subject ID
 */
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

    fetch(storeComponentRoute, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
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

/**
 * Save all components that have been selected
 * Iterates through all rows and saves those with selected components
 */
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

/**
 * Apply bulk component to all unassigned subjects
 * Applies the selected component type to all subjects that haven't been assigned yet
 */
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

/**
 * Update statistics display
 * Calculates and updates the progress statistics (assigned, pending, percentage)
 */
function updateStats() {
    const totalRows = document.querySelectorAll('tbody tr').length;
    const assignedRows = document.querySelectorAll('tbody tr.table-success').length;
    const pendingRows = totalRows - assignedRows;
    const percentage = totalRows > 0 ? ((assignedRows / totalRows) * 100).toFixed(1) : 0;

    document.getElementById('assigned-count').textContent = assignedRows;
    document.getElementById('pending-count').textContent = pendingRows;
    document.getElementById('progress-percentage').textContent = percentage + '%';
}

/**
 * Continue to analysis screen
 * Navigates to the simulation analysis page, with confirmation if there are pending assignments
 */
function continueToAnalysis() {
    const pendingCount = parseInt(document.getElementById('pending-count').textContent);
    
    if (pendingCount > 0) {
        if (!confirm(`Aún hay ${pendingCount} materias sin asignar componente. ¿Desea continuar de todos modos?`)) {
            return;
        }
    }

    window.location.href = analysisRoute;
}

/**
 * Show toast notification
 * @param {string} type - Type of toast (success, error, warning, info)
 * @param {string} message - Message to display
 */
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
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
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

/**
 * Create toast container if it doesn't exist
 * @private
 * @returns {HTMLElement} The toast container element
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
