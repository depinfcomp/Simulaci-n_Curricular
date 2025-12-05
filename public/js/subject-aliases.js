/**
 * Subject Aliases Management
 * Handles CRUD operations for subject aliases (alternative codes)
 */

let modal;
let storeRoute;
let csrfToken;

/**
 * Initialize the module with required data from the view
 */
function initSubjectAliases(config) {
    storeRoute = config.storeRoute;
    csrfToken = config.csrfToken;
}

/**
 * Initialize modal on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('addAliasModal');
    if (modalElement) {
        modal = new bootstrap.Modal(modalElement);
    }
});

/**
 * Show modal to add a new alias for a subject
 * @param {string} subjectCode - Subject code
 * @param {string} subjectName - Subject name
 * @param {string} subjectType - Subject type (obligatory/elective)
 */
function showAddAliasModal(subjectCode, subjectName, subjectType) {
    document.getElementById('subject_code').value = subjectCode;
    document.getElementById('subject_name').textContent = `${subjectCode} - ${subjectName}`;
    document.getElementById('subject_type').value = subjectType;
    document.getElementById('alias_code').value = '';
    document.getElementById('notes').value = '';
    modal.show();
}

/**
 * Save a new alias via AJAX
 */
function saveAlias() {
    const form = document.getElementById('addAliasForm');
    const formData = new FormData(form);
    
    // Show loading state
    const saveButton = document.querySelector('#addAliasModal .btn-primary');
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveButton.disabled = true;
    
    fetch(storeRoute, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            subject_code: formData.get('subject_code'),
            alias_code: formData.get('alias_code'),
            description: formData.get('notes')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modal.hide();
            // Show success message
            showSuccessMessage('Alias agregado correctamente');
            // Reload after a brief delay
            setTimeout(() => location.reload(), 800);
        } else {
            showErrorMessage('Error: ' + data.message);
            // Restore button
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Error al guardar el alias');
        // Restore button
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
    });
}

/**
 * Delete an alias with confirmation
 * @param {number} aliasId - Alias ID to delete
 */
function deleteAlias(aliasId) {
    if (!confirm('¿Está seguro de eliminar este alias?')) {
        return;
    }
    
    fetch(`/subject-aliases/${aliasId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('Alias eliminado correctamente');
            setTimeout(() => location.reload(), 800);
        } else {
            showErrorMessage('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Error al eliminar el alias');
    });
}

/**
 * Show success message
 * @param {string} message - Message to display
 */
function showSuccessMessage(message) {
    // Check if alerts container exists
    let alertsContainer = document.querySelector('.container-fluid > .row');
    if (!alertsContainer) {
        alertsContainer = document.querySelector('.container-fluid');
    }
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.setAttribute('role', 'alert');
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertsContainer.insertBefore(alert, alertsContainer.firstChild);
}

/**
 * Show error message
 * @param {string} message - Message to display
 */
function showErrorMessage(message) {
    // Check if alerts container exists
    let alertsContainer = document.querySelector('.container-fluid > .row');
    if (!alertsContainer) {
        alertsContainer = document.querySelector('.container-fluid');
    }
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.setAttribute('role', 'alert');
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertsContainer.insertBefore(alert, alertsContainer.firstChild);
}
