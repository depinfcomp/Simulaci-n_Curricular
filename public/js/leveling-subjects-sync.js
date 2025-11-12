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
    notification.className = 'alert alert-warning alert-dismissible fade show mb-4';
    notification.innerHTML = `
        <h6 class="alert-heading">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Cambios Temporales Detectados
        </h6>
        <hr>
        <p class="mb-0">
            Hay <strong>${count}</strong> materia(s) de nivelación agregada(s) temporalmente desde la simulación.
            Estos cambios <strong>no están guardados</strong>. 
            <a href="/simulation" class="alert-link">Ir a la simulación</a> para guardar o descartar los cambios.
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert after the header, before the info alert
    const container = document.querySelector('.container-fluid .row .col-12');
    const header = container.querySelector('.d-flex.justify-content-between');
    header.after(notification);
}

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
    
    // Show alert that this is a temporary subject
    showInfoModal(
        'Materia Temporal',
        'Esta materia aún no está guardada en la base de datos. Los cambios solo se reflejarán en la simulación. Para guardar permanentemente, ve a la simulación y haz clic en "Guardar Cambios".'
    );
};

/**
 * Delete temporary leveling subject
 * Removes from localStorage and redirects to simulation
 */
window.deleteTemporaryLeveling = function(code, name) {
    showConfirmModal(
        `¿Estás seguro de eliminar "${name}"?`,
        'Esta materia es temporal y no está guardada en la base de datos. Para eliminarla definitivamente, debes ir a la simulación y usar el botón "Reset" o eliminarla desde allí.',
        function() {
            // Redirect to simulation
            window.location.href = '/simulation';
        },
        'warning',
        'Ir a Simulación',
        'Cancelar'
    );
};

/**
 * Show info modal
 */
function showInfoModal(title, message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: title,
            html: message,
            confirmButtonText: 'Entendido'
        });
    } else {
        alert(message);
    }
}

/**
 * Show confirm modal
 */
function showConfirmModal(title, message, onConfirm, icon = 'warning', confirmText = 'Sí', cancelText = 'No') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: icon,
            title: title,
            html: message,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed && onConfirm) {
                onConfirm();
            }
        });
    } else {
        if (confirm(message) && onConfirm) {
            onConfirm();
        }
    }
}
