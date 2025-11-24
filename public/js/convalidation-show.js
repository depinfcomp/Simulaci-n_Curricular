let currentExternalSubjectId = null;

/**
 * Get Bootstrap color class for component type badge
 * Maps component types to their corresponding colors
 */
function getComponentColor(componentType) {
    const colors = {
        'fundamental_required': 'warning',      // Orange (naranja)
        'professional_required': 'success',     // Green (verde)
        'optional_fundamental': 'warning',      // Orange (naranja)
        'optional_professional': 'success',     // Green (verde)
        'thesis': 'success',                    // Green (verde)
        'free_elective': 'primary',             // Blue (azul)
        'leveling': 'danger'                    // Red (rojo)
    };
    return colors[componentType] || 'secondary';
}

/**
 * Get human-readable label for component type
 */
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

function showConvalidationModal(externalSubjectId) {
    currentExternalSubjectId = externalSubjectId;
    
    // Get subject info and show modal
    const row = document.getElementById(`subject-row-${externalSubjectId}`);
    const subjectCode = row.querySelector('code').textContent;
    const subjectName = row.querySelector('h6').textContent;
    const subjectCredits = row.querySelector('.badge').textContent;
    
    document.getElementById('external_subject_id').value = externalSubjectId;
    document.getElementById('external_subject_info').innerHTML = 
        `<strong>${subjectName}</strong> (${subjectCode}) - ${subjectCredits} créditos`;
    
    // Reset form
    document.getElementById('convalidationForm').reset();
    document.getElementById('external_subject_id').value = externalSubjectId;
    
    // Check "Convalidación Directa" by default
    document.getElementById('type_direct').checked = true;
    
    // Show internal subject selection by default (since direct is checked)
    document.getElementById('internal_subject_selection').style.display = 'block';
    
    const modal = new bootstrap.Modal(document.getElementById('convalidationModal'));
    modal.show();
}

// Handle convalidation type change
document.querySelectorAll('input[name="convalidation_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const internalSubjectSelection = document.getElementById('internal_subject_selection');
        if (this.value === 'direct') {
            internalSubjectSelection.style.display = 'block';
        } else {
            internalSubjectSelection.style.display = 'none';
        }
    });
});

function saveConvalidation() {
    const formData = new FormData(document.getElementById('convalidationForm'));
    
    // Validate component type is selected
    const componentType = formData.get('component_type');
    if (!componentType) {
        showAlert('danger', 'Por favor seleccione un componente académico');
        return;
    }
    
    // Store current active semester before making the request
    const currentActiveSemester = getCurrentActiveSemester();
    
    fetch(window.convalidationRoutes.store, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the convalidation display
            updateConvalidationDisplay(currentExternalSubjectId, data.convalidation);
            
            // Update statistics without page reload
            if (data.stats) {
                updateStatistics(data.stats);
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('convalidationModal'));
            modal.hide();
            
            // Restore active semester
            restoreActiveSemester(currentActiveSemester);
            
            // Show success message
            showAlert('success', 'Convalidación guardada exitosamente');
        } else {
            showAlert('danger', data.error || 'Error al guardar la convalidación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error de conexión');
    });
}

function getCurrentActiveSemester() {
    // Find which semester tab is currently active
    const activeTab = document.querySelector('.nav-link.active[data-bs-target^="#semester"]');
    if (activeTab) {
        const href = activeTab.getAttribute('data-bs-target');
        return href.replace('#semester-', '');
    }
    return '1'; // Default to semester 1
}

function restoreActiveSemester(semesterNumber) {
    // Restore the active semester tab
    setTimeout(() => {
        const targetTab = document.querySelector(`[data-bs-target="#semester-${semesterNumber}"]`);
        if (targetTab) {
            const tab = new bootstrap.Tab(targetTab);
            tab.show();
        }
    }, 100);
}

function updateStatistics(stats) {
    // Update convalidation progress
    const progressBar = document.getElementById('convalidation-progress');
    if (progressBar) {
        progressBar.style.width = `${stats.completion_percentage}%`;
        progressBar.textContent = `${stats.completion_percentage.toFixed(1)}%`;
    }
    
    // Update counts
    const directCount = document.getElementById('direct-count');
    if (directCount) directCount.textContent = stats.direct_convalidations;
    
    const electiveCount = document.getElementById('elective-count');
    if (electiveCount) electiveCount.textContent = stats.free_electives;
    
    const notConvalidatedCount = document.getElementById('not-convalidated-count');
    if (notConvalidatedCount) notConvalidatedCount.textContent = stats.not_convalidated;
    
    const pendingCount = document.getElementById('pending-count');
    if (pendingCount) pendingCount.textContent = stats.pending_subjects;
    
    // Update career completion stats
    const careerPercentage = document.getElementById('career-percentage');
    if (careerPercentage) careerPercentage.textContent = `${stats.career_completion_percentage.toFixed(1)}%`;
    
    const convalidatedCredits = document.getElementById('convalidated-credits');
    if (convalidatedCredits) convalidatedCredits.textContent = stats.convalidated_credits.toFixed(1);
    
    const careerProgress = document.getElementById('career-progress');
    if (careerProgress) careerProgress.style.width = `${stats.career_completion_percentage}%`;
}

function updateConvalidationDisplay(subjectId, convalidation) {
    const displayElement = document.getElementById(`convalidation-display-${subjectId}`);
    
    if (convalidation.convalidation_type === 'direct') {
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-arrow-right text-success me-2"></i>
                <div>
                    <small class="fw-bold text-success">${convalidation.internal_subject.name}</small><br>
                    <small class="text-muted">${convalidation.internal_subject.code}</small>
                </div>
            </div>
        `;
    } else if (convalidation.convalidation_type === 'free_elective') {
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-star text-info me-2"></i>
                <span class="fw-bold text-info">Libre Elección</span>
            </div>
        `;
    } else if (convalidation.convalidation_type === 'not_convalidated') {
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-plus-circle text-warning me-2"></i>
                <span class="fw-bold text-warning">Materia Nueva</span>
            </div>
        `;
    }
    
    // Update status badge in the "Estado" column (5th td)
    const row = document.getElementById(`subject-row-${subjectId}`);
    const statusCell = row.querySelector('td:nth-child(5)'); // 5th column is Estado
    
    // Get component type and color
    const componentType = convalidation.component_assignment?.component_type;
    const componentColor = componentType ? getComponentColor(componentType) : 'secondary';
    const componentLabel = componentType ? getComponentLabel(componentType) : '';
    
    // Build status badges HTML
    let statusHtml = '<div class="d-flex flex-column gap-1">';
    
    if (convalidation.convalidation_type === 'direct') {
        statusHtml += '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Convalidada</span>';
    } else if (convalidation.convalidation_type === 'free_elective') {
        statusHtml += '<span class="badge bg-info"><i class="fas fa-star me-1"></i>Libre Elección</span>';
    } else if (convalidation.convalidation_type === 'not_convalidated') {
        statusHtml += '<span class="badge bg-warning"><i class="fas fa-plus-circle me-1"></i>Materia Nueva</span>';
    }
    
    // Add component type badge if available
    if (componentType) {
        statusHtml += `<span class="badge bg-${componentColor}">${componentLabel}</span>`;
    }
    
    statusHtml += '</div>';
    statusCell.innerHTML = statusHtml;
}

function getSuggestions(externalSubjectId = null) {
    const targetId = externalSubjectId || currentExternalSubjectId;
    
    fetch(`${window.convalidationRoutes.suggestions}?external_subject_id=${targetId}`)
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('suggestions_container');
        const list = document.getElementById('suggestions_list');
        
        if (data.suggestions && data.suggestions.length > 0) {
            list.innerHTML = data.suggestions.map(suggestion => `
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${suggestion.subject.name}</strong>
                                <small class="text-muted">(${suggestion.subject.code})</small>
                                <div class="mt-1">
                                    <span class="badge bg-info">${suggestion.match_percentage}% similitud</span>
                                    <span class="badge bg-secondary">Semestre ${suggestion.subject.semester}</span>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="selectSuggestion('${suggestion.subject.code}')">
                                Seleccionar
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            container.style.display = 'block';
        } else {
            list.innerHTML = '<p class="text-muted">No se encontraron sugerencias automáticas</p>';
            container.style.display = 'block';
        }
    });
}

function selectSuggestion(subjectCode) {
    document.getElementById('type_direct').checked = true;
    document.getElementById('internal_subject_code').value = subjectCode;
    document.getElementById('internal_subject_selection').style.display = 'block';
}

function removeConvalidation(convalidationId) {
    if (confirm('¿Está seguro de que desea eliminar esta convalidación?')) {
        const url = window.convalidationRoutes.destroy.replace(':id', convalidationId);
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert('danger', 'Error al eliminar la convalidación');
            }
        });
    }
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at top of page
    document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alertHtml);
}

function exportReport() {
    window.location.href = window.convalidationRoutes.export;
}

// ============================================
// BULK CONVALIDATION FUNCTIONS
// ============================================

function showBulkConvalidationModal() {
    // Reset modal state
    document.getElementById('bulk_progress').style.display = 'none';
    document.getElementById('bulk_results').style.display = 'none';
    document.getElementById('start_bulk_btn').style.display = 'block';
    
    // Get modal element
    const modalElement = document.getElementById('bulkConvalidationModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Add event listener to reload page when modal is closed
    modalElement.addEventListener('hidden.bs.modal', function () {
        location.reload();
    }, { once: true }); // Use 'once: true' to avoid multiple listeners
    
    // Show modal
    modal.show();
}

function startBulkConvalidation() {
    const btn = document.getElementById('start_bulk_btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
    
    // Show progress
    document.getElementById('bulk_progress').style.display = 'block';
    
    // Start the bulk convalidation
    fetch(window.convalidationRoutes.bulkConvalidation, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        },
        body: JSON.stringify({
            external_curriculum_id: window.externalCurriculumId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayBulkResults(data.results);
            btn.style.display = 'none';
            
            // Don't auto-reload, let user close modal manually
            // Reload will happen when modal is closed
        } else {
            showAlert('danger', data.error || 'Error en la convalidación masiva');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Convalidación Masiva';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Iniciar Convalidación Masiva';
    });
}

function displayBulkResults(results) {
    // Hide progress
    document.getElementById('bulk_progress').style.display = 'none';
    
    // Show results
    document.getElementById('bulk_results').style.display = 'block';
    
    // Count results
    let successCount = 0;
    let skippedCount = 0;
    let errorCount = 0;
    
    results.forEach(result => {
        if (result.status === 'success') successCount++;
        else if (result.status === 'skipped') skippedCount++;
        else if (result.status === 'error') errorCount++;
    });
    
    // Update counters
    document.getElementById('success_count').textContent = successCount;
    document.getElementById('skipped_count').textContent = skippedCount;
    document.getElementById('error_count').textContent = errorCount;
    
    // Build results table
    const tableBody = document.getElementById('bulk_results_table');
    tableBody.innerHTML = results.map(result => {
        const statusIcon = result.status === 'success' 
            ? '<i class="fas fa-check-circle text-success"></i>'
            : result.status === 'skipped'
            ? '<i class="fas fa-minus-circle text-warning"></i>'
            : '<i class="fas fa-times-circle text-danger"></i>';
        
        const methodBadge = result.method === 'code'
            ? '<span class="badge bg-primary">Código</span>'
            : result.method === 'name'
            ? '<span class="badge bg-info">Nombre</span>'
            : '<span class="badge bg-secondary">-</span>';
        
        const componentBadge = result.component_type
            ? `<span class="badge bg-${getComponentColor(result.component_type)}">${getComponentLabel(result.component_type)}</span>`
            : '<span class="badge bg-secondary">-</span>';
        
        return `
            <tr>
                <td>
                    <small><strong>${result.external_subject.name}</strong></small><br>
                    <small class="text-muted">${result.external_subject.code}</small>
                </td>
                <td>
                    ${result.internal_subject ? `
                        <small><strong>${result.internal_subject.name}</strong></small><br>
                        <small class="text-muted">${result.internal_subject.code}</small>
                    ` : '<small class="text-muted">-</small>'}
                </td>
                <td>${componentBadge}</td>
                <td>${methodBadge}</td>
                <td>${statusIcon}</td>
            </tr>
        `;
    }).join('');
}
