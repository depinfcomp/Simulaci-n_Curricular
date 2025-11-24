let currentExternalSubjectId = null;
let currentInternalSubjectCode = null; // Store current code when editing

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

function showConvalidationModal(externalSubjectId, existingData = null) {
    currentExternalSubjectId = externalSubjectId;
    
    // Get subject info and show modal
    const row = document.getElementById(`subject-row-${externalSubjectId}`);
    if (!row) {
        console.error('Row not found for subject:', externalSubjectId);
        return;
    }
    
    const subjectCodeElement = row.querySelector('code');
    const subjectNameElement = row.querySelector('h6');
    const subjectCreditsElement = row.querySelector('.badge');
    
    if (!subjectCodeElement || !subjectNameElement || !subjectCreditsElement) {
        console.error('Subject info elements not found');
        return;
    }
    
    const subjectCode = subjectCodeElement.textContent;
    const subjectName = subjectNameElement.textContent;
    const subjectCredits = subjectCreditsElement.textContent;
    
    const externalSubjectIdInput = document.getElementById('external_subject_id');
    const externalSubjectInfo = document.getElementById('external_subject_info');
    const convalidationForm = document.getElementById('convalidationForm');
    
    if (!externalSubjectIdInput || !externalSubjectInfo || !convalidationForm) {
        console.error('Form elements not found');
        return;
    }
    
    externalSubjectIdInput.value = externalSubjectId;
    externalSubjectInfo.innerHTML = 
        `<strong>${subjectName}</strong> (${subjectCode}) - ${subjectCredits} créditos`;
    
    // Reset form
    convalidationForm.reset();
    externalSubjectIdInput.value = externalSubjectId;
    
    // If editing existing convalidation, load data
    if (existingData && existingData.convalidationType) {
        // Set convalidation type
        const typeDirectRadio = document.getElementById('type_direct');
        const typeNotConvalidatedRadio = document.getElementById('type_not_convalidated');
        const internalSubjectSelection = document.getElementById('internal_subject_selection');
        
        if (existingData.convalidationType === 'direct') {
            if (typeDirectRadio) typeDirectRadio.checked = true;
            if (internalSubjectSelection) internalSubjectSelection.style.display = 'block';
        } else {
            if (typeNotConvalidatedRadio) typeNotConvalidatedRadio.checked = true;
            if (internalSubjectSelection) internalSubjectSelection.style.display = 'none';
        }
        
        // Set component type
        if (existingData.componentType) {
            const componentTypeSelect = document.getElementById('component_type');
            if (componentTypeSelect) {
                componentTypeSelect.value = existingData.componentType;
                
                // Store current code for filtering
                currentInternalSubjectCode = existingData.internalSubjectCode || null;
                
                // Trigger component type change to show/hide checkbox if needed
                const event = new Event('change');
                componentTypeSelect.dispatchEvent(event);
            }
        }
        
        // Set internal subject code
        if (existingData.internalSubjectCode) {
            const internalSubjectSelect = document.getElementById('internal_subject_code');
            if (internalSubjectSelect) {
                internalSubjectSelect.value = existingData.internalSubjectCode;
            }
        }
        
        // Set notes
        if (existingData.notes) {
            const notesTextarea = document.getElementById('convalidation_notes');
            if (notesTextarea) {
                notesTextarea.value = existingData.notes;
            }
        }
        
        // Change modal title
        const modalTitle = document.querySelector('#convalidationModal .modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Editar Convalidación';
        }
    } else {
        // New convalidation - default values
        // Reset current code variable
        currentInternalSubjectCode = null;
        
        const typeDirectRadio = document.getElementById('type_direct');
        const internalSubjectSelection = document.getElementById('internal_subject_selection');
        
        if (typeDirectRadio) typeDirectRadio.checked = true;
        if (internalSubjectSelection) internalSubjectSelection.style.display = 'block';
        
        // Reset checkbox and related elements
        const createNewCodeContainer = document.getElementById('create_new_code_container');
        const createNewCodeCheckbox = document.getElementById('create_new_code');
        const newCodeMessage = document.getElementById('new_code_message');
        const internalSubjectSelect = document.getElementById('internal_subject_code');
        
        if (createNewCodeContainer) createNewCodeContainer.style.display = 'none';
        if (createNewCodeCheckbox) createNewCodeCheckbox.checked = false;
        if (newCodeMessage) newCodeMessage.style.display = 'none';
        if (internalSubjectSelect) internalSubjectSelect.disabled = false;
        
        // Reset all option states
        if (internalSubjectSelect) {
            const options = internalSubjectSelect.querySelectorAll('option');
            options.forEach(option => {
                if (option.value !== '') {
                    option.disabled = false;
                }
            });
        }
        
        // Reset label text
        const label = document.querySelector('label[for="internal_subject_code"]');
        if (label) {
            label.textContent = 'Materia Interna';
        }
        
        // Reset modal title
        const modalTitle = document.querySelector('#convalidationModal .modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Configurar Convalidación';
        }
    }
    
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

// Handle component type change - show/hide "Crear nuevo código" checkbox
document.getElementById('component_type').addEventListener('change', function() {
    const componentType = this.value;
    const createNewCodeContainer = document.getElementById('create_new_code_container');
    const createNewCodeCheckbox = document.getElementById('create_new_code');
    
    // Show checkbox only for optativas and libre elección
    const showCheckbox = ['optional_fundamental', 'optional_professional', 'free_elective'].includes(componentType);
    createNewCodeContainer.style.display = showCheckbox ? 'block' : 'none';
    
    // Reset checkbox if hidden
    if (!showCheckbox) {
        createNewCodeCheckbox.checked = false;
        document.getElementById('new_code_message').style.display = 'none';
    }
    
    // Filter available subjects based on component type and already used subjects
    if (showCheckbox) {
        // Pass current code if editing, null if creating new
        filterAvailableSubjects(componentType, currentInternalSubjectCode);
    }
});

// Handle "Crear nuevo código" checkbox change
document.getElementById('create_new_code').addEventListener('change', function() {
    const newCodeMessage = document.getElementById('new_code_message');
    const internalSubjectSelect = document.getElementById('internal_subject_code');
    
    if (this.checked) {
        // Show message and disable/hide select
        newCodeMessage.style.display = 'block';
        internalSubjectSelect.disabled = true;
        internalSubjectSelect.value = ''; // Clear selection
    } else {
        // Hide message and enable select
        newCodeMessage.style.display = 'none';
        internalSubjectSelect.disabled = false;
    }
});

function filterAvailableSubjects(componentType, currentCode = null) {
    const externalCurriculumId = document.querySelector('[data-external-curriculum-id]')?.dataset.externalCurriculumId;
    
    if (!externalCurriculumId) return;
    
    // Fetch used subjects for this component type
    fetch(`/convalidation/${externalCurriculumId}/used-subjects?component_type=${componentType}`)
        .then(response => response.json())
        .then(data => {
            const internalSubjectSelect = document.getElementById('internal_subject_code');
            const options = internalSubjectSelect.querySelectorAll('option');
            
            let availableCount = 0;
            
            options.forEach(option => {
                if (option.value === '') return; // Skip empty option
                
                // If this is the current code (editing mode), don't disable it
                const isCurrentCode = currentCode && option.value === currentCode;
                const isUsed = data.usedSubjects.includes(option.value);
                
                option.disabled = isUsed && !isCurrentCode;
                
                if (!isUsed || isCurrentCode) availableCount++;
            });
            
            // Update label to show count
            const label = document.querySelector('label[for="internal_subject_code"]');
            if (label) {
                const originalText = label.textContent.replace(/ \(\d+ disponibles?\)/, '');
                label.textContent = `${originalText} (${availableCount} disponible${availableCount !== 1 ? 's' : ''})`;
            }
            
            // Show/hide checkbox container based on availability
            const createNewCodeContainer = document.getElementById('create_new_code_container');
            if (availableCount === 0) {
                createNewCodeContainer.style.display = 'block';
                // Auto-check the checkbox since there are no available subjects
                document.getElementById('create_new_code').checked = true;
                document.getElementById('new_code_message').style.display = 'block';
                internalSubjectSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error al cargar materias disponibles:', error);
        });
}

function saveConvalidation() {
    const formData = new FormData(document.getElementById('convalidationForm'));
    
    // Validate component type is selected
    const componentType = formData.get('component_type');
    if (!componentType) {
        showAlert('danger', 'Por favor seleccione un componente académico');
        return;
    }
    
    // Check if "Crear nuevo código" is checked
    const createNewCodeCheckbox = document.getElementById('create_new_code');
    if (createNewCodeCheckbox && createNewCodeCheckbox.checked) {
        // Add a flag to tell the backend to generate a new code
        formData.append('create_new_code', '1');
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
            // Attach component_assignment to convalidation object for display
            const convalidationWithComponent = {
                ...data.convalidation,
                component_assignment: data.component_assignment
            };
            
            // Update the convalidation display
            updateConvalidationDisplay(currentExternalSubjectId, convalidationWithComponent);
            
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
    
    // Update action buttons (6th column - Acciones)
    const actionsCell = row.querySelector('td:nth-child(6)');
    const btnGroup = actionsCell.querySelector('.btn-group');
    
    // Check if delete button already exists
    const existingDeleteBtn = btnGroup.querySelector('.btn-outline-danger');
    
    // If convalidation exists and delete button doesn't exist, add it
    if (convalidation.id && !existingDeleteBtn) {
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-outline-danger';
        deleteBtn.title = 'Eliminar convalidación';
        deleteBtn.onclick = () => removeConvalidation(convalidation.id);
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        btnGroup.appendChild(deleteBtn);
    }
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

// Add event listener for all convalidation config buttons
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.convalidation-config-btn');
        if (btn) {
            const externalSubjectId = btn.dataset.externalSubjectId;
            const convalidationType = btn.dataset.convalidationType;
            const internalSubjectCode = btn.dataset.internalSubjectCode;
            const componentType = btn.dataset.componentType;
            const notes = btn.dataset.notes;
            
            showConvalidationModal(externalSubjectId, {
                convalidationType,
                internalSubjectCode,
                componentType,
                notes
            });
        }
    });
});
