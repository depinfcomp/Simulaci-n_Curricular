let currentExternalSubjectId = null;
let currentInternalSubjectCode = null; // Store current code when editing
let modalIsOpen = false; // Flag to prevent multiple modal openings
let currentImpactResults = null; // Store impact analysis results for PDF generation

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

/**
 * Filter internal subject options based on convalidation type
 * For 'direct' convalidations: Hide elective subjects (optativas y libre elección)
 */
function filterInternalSubjectOptions(convalidationType) {
    const internalSubjectSelect = document.getElementById('internal_subject_code');
    if (!internalSubjectSelect) return;
    
    const options = internalSubjectSelect.querySelectorAll('option');
    let hiddenCount = 0;
    let shownCount = 0;
    
    options.forEach(option => {
        if (option.value === '') {
            // Always show placeholder
            option.style.display = '';
            option.disabled = false;
        } else if (convalidationType === 'direct' && option.dataset.subjectCategory === 'elective') {
            // For direct convalidations: Hide elective subjects (optional_*, free_elective)
            option.style.display = 'none';
            option.disabled = true;
            hiddenCount++;
        } else {
            // Show all other subjects (fundamental_required, professional_required, thesis, leveling)
            option.style.display = '';
            option.disabled = false;
            if (option.value !== '') shownCount++;
        }
    });
    
    console.log(`[Filter Subjects] Type: ${convalidationType}, Hidden: ${hiddenCount}, Shown: ${shownCount}`);
}

/**
 * Filter component type options based on convalidation type
 * For 'direct': Only required components (fundamental, professional, thesis, leveling)
 * For 'flexible_component': Only elective components (optional_*, free_elective)
 * For 'not_convalidated': Only required components (new subjects are only obligatory)
 */
function filterComponentTypeOptions(convalidationType) {
    const componentTypeSelect = document.getElementById('component_type');
    const componentHint = document.getElementById('component_type_hint');
    if (!componentTypeSelect) return;
    
    const options = componentTypeSelect.querySelectorAll('option');
    let hiddenCount = 0;
    let shownCount = 0;
    
    options.forEach(option => {
        if (option.value === '') {
            // Always show placeholder
            option.style.display = '';
            option.disabled = false;
        } else if (convalidationType === 'direct' || convalidationType === 'not_convalidated') {
            // For "Convalidación Directa" and "Materia Nueva": Only show REQUIRED components
            // Elective components should use "Componente Electivo" type exclusively
            if (option.dataset.componentCategory === 'required') {
                option.style.display = '';
                option.disabled = false;
                shownCount++;
            } else {
                option.style.display = 'none';
                option.disabled = true;
                hiddenCount++;
            }
        } else if (convalidationType === 'flexible_component') {
            // For "Componente Electivo": Only show ELECTIVE components
            if (option.dataset.componentCategory === 'elective') {
                option.style.display = '';
                option.disabled = false;
                shownCount++;
            } else {
                option.style.display = 'none';
                option.disabled = true;
                hiddenCount++;
            }
        } else {
            // Fallback: Show all (shouldn't reach here)
            option.style.display = '';
            option.disabled = false;
            if (option.value !== '') shownCount++;
        }
    });
    
    // Update hint text
    if (componentHint) {
        if (convalidationType === 'direct') {
            componentHint.textContent = 'Selecciona el componente obligatorio (fundamental, profesional, trabajo de grado o nivelación)';
        } else if (convalidationType === 'flexible_component') {
            componentHint.textContent = 'Selecciona el tipo de componente electivo (optativas o libre elección)';
        } else if (convalidationType === 'not_convalidated') {
            componentHint.textContent = 'Selecciona el componente obligatorio de la materia nueva (las electivas usan "Componente Electivo")';
        } else {
            componentHint.textContent = 'Indica el tipo de componente académico al que pertenece esta materia';
        }
    }
    
    console.log(`[Filter Components] Type: ${convalidationType}, Hidden: ${hiddenCount}, Shown: ${shownCount}`);
}


function showConvalidationModal(externalSubjectId, existingData = null) {
    // Prevent opening multiple modals simultaneously
    if (modalIsOpen) {
        console.warn('Modal is already open, ignoring duplicate click');
        return;
    }
    
    modalIsOpen = true;
    currentExternalSubjectId = externalSubjectId;
    
    // Get subject info
    const row = document.getElementById(`subject-row-${externalSubjectId}`);
    if (!row) {
        console.error('Row not found for subject:', externalSubjectId);
        modalIsOpen = false;
        return;
    }
    
    const subjectCodeElement = row.querySelector('code');
    const subjectNameElement = row.querySelector('h6');
    const subjectCreditsElement = row.querySelector('.badge');
    
    if (!subjectCodeElement || !subjectNameElement || !subjectCreditsElement) {
        console.error('Subject info elements not found');
        modalIsOpen = false;
        return;
    }
    
    const subjectCode = subjectCodeElement.textContent;
    const subjectName = subjectNameElement.textContent;
    const subjectCredits = subjectCreditsElement.textContent;
    
    // Get modal element
    const modalElement = document.getElementById('convalidationModal');
    if (!modalElement) {
        console.error('Modal element not found');
        modalIsOpen = false;
        return;
    }
    
    // Get or create modal instance
    let modal = bootstrap.Modal.getInstance(modalElement);
    if (!modal) {
        modal = new bootstrap.Modal(modalElement);
    }
    
    // Get form elements (they should always be in the DOM)
    const externalSubjectIdInput = document.getElementById('external_subject_id');
    const externalSubjectInfo = document.getElementById('external_subject_info');
    const convalidationForm = document.getElementById('convalidationForm');
    
    // If elements not found, reload page to recover from broken state
    if (!externalSubjectIdInput || !externalSubjectInfo || !convalidationForm) {
        console.error('CRITICAL: Form elements not found, reloading page...');
        modalIsOpen = false;
        location.reload();
        return;
    }
    
    // Set basic info
    externalSubjectIdInput.value = externalSubjectId;
    externalSubjectInfo.innerHTML = 
        `<strong>${subjectName}</strong> (${subjectCode}) - ${subjectCredits} créditos`;
    
    // Reset form completely - clear all fields explicitly first
    convalidationForm.reset();
    
    // Manual cleanup to ensure everything is cleared (browser cache issue)
    const typeDirectRadio = document.getElementById('type_direct');
    const typeNotConvalidatedRadio = document.getElementById('type_not_convalidated');
    const internalSubjectSelection = document.getElementById('internal_subject_selection');
    const internalSubjectSelect = document.getElementById('internal_subject_code');
    const componentTypeSelect = document.getElementById('component_type');
    const notesTextarea = document.getElementById('convalidation_notes');
    const createNewCodeCheckbox = document.getElementById('create_new_code');
    const createNewCodeContainer = document.getElementById('create_new_code_container');
    const newCodeMessage = document.getElementById('new_code_message');
    
    // Clear ALL fields first (before checking existingData)
    if (typeDirectRadio) typeDirectRadio.checked = false;
    if (typeNotConvalidatedRadio) typeNotConvalidatedRadio.checked = false;
    if (internalSubjectSelection) internalSubjectSelection.style.display = 'none';
    if (internalSubjectSelect) {
        internalSubjectSelect.value = '';
        internalSubjectSelect.disabled = false;
    }
    if (componentTypeSelect) componentTypeSelect.value = '';
    if (notesTextarea) notesTextarea.value = '';
    if (createNewCodeCheckbox) createNewCodeCheckbox.checked = false;
    if (createNewCodeContainer) createNewCodeContainer.style.display = 'none';
    if (newCodeMessage) newCodeMessage.style.display = 'none';
    
    // Restore the external_subject_id after reset
    externalSubjectIdInput.value = externalSubjectId;
    
    // If editing existing convalidation, load data
    if (existingData && existingData.convalidationType) {
        // Set convalidation type
        const typeDirectRadio = document.getElementById('type_direct');
        const typeNotConvalidatedRadio = document.getElementById('type_not_convalidated');
        const typeFlexibleRadio = document.getElementById('type_flexible_component');
        const internalSubjectSelection = document.getElementById('internal_subject_selection');
        
        if (existingData.convalidationType === 'direct') {
            if (typeDirectRadio) typeDirectRadio.checked = true;
            if (internalSubjectSelection) internalSubjectSelection.style.display = 'block';
            // Apply filters to hide elective subjects and components
            filterInternalSubjectOptions('direct');
            filterComponentTypeOptions('direct');
        } else if (existingData.convalidationType === 'flexible_component') {
            if (typeFlexibleRadio) typeFlexibleRadio.checked = true;
            if (internalSubjectSelection) internalSubjectSelection.style.display = 'none';
            // Apply filter to show only elective components
            filterComponentTypeOptions('flexible_component');
        } else {
            if (typeNotConvalidatedRadio) typeNotConvalidatedRadio.checked = true;
            if (internalSubjectSelection) internalSubjectSelection.style.display = 'none';
            // Show all components
            filterComponentTypeOptions('not_convalidated');
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
        // New convalidation - fields already cleared above
        // Just need to reset current code variable and set modal title
        currentInternalSubjectCode = null;
        
        // The default type is 'direct' (checked by default in HTML)
        // Apply filters to hide elective subjects and components for direct convalidations
        filterInternalSubjectOptions('direct');
        filterComponentTypeOptions('direct');
        
        // Reset label text
        const label = document.querySelector('label[for="internal_subject_code"]');
        if (label) {
            label.textContent = 'Materia Interna';
        }
        
        // Set modal title for new convalidation
        const modalTitle = document.querySelector('#convalidationModal .modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Configurar Convalidación';
        }
    }
    
    // ALWAYS block optativas/libres already used, regardless of component selection
    // This prevents selecting #LIBRE-01 or used optativas when configuring other components
    blockUsedOptativesAndFreeElectives(currentInternalSubjectCode);
    
    // Show modal
    modal.show();
}

// Handle convalidation type change
document.querySelectorAll('input[name="convalidation_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const internalSubjectSelection = document.getElementById('internal_subject_selection');
        const internalSubjectSelect = document.getElementById('internal_subject_code');
        const componentTypeSelect = document.getElementById('component_type');
        const createNewCodeCheckbox = document.getElementById('create_new_code');
        const createNewCodeContainer = document.getElementById('create_new_code_container');
        const newCodeMessage = document.getElementById('new_code_message');
        const notesTextarea = document.getElementById('convalidation_notes');
        const componentHint = document.getElementById('component_type_hint');
        
        // ALWAYS clear all fields when changing type to avoid confusion
        // User must re-select everything
        if (internalSubjectSelect) {
            internalSubjectSelect.value = '';
        }
        
        if (componentTypeSelect) {
            componentTypeSelect.value = '';
        }
        
        if (createNewCodeContainer) {
            createNewCodeContainer.style.display = 'none';
        }
        
        if (createNewCodeCheckbox) {
            createNewCodeCheckbox.checked = false;
        }
        
        if (newCodeMessage) {
            newCodeMessage.style.display = 'none';
        }
        
        if (notesTextarea) {
            notesTextarea.value = '';
        }
        
        // Filter component type options based on convalidation type
        filterComponentTypeOptions(this.value);
        
        // Show/hide internal subject selection based on type
        // - 'direct': Show internal subject selector (filtered to hide electives)
        // - 'flexible_component': Hide internal subject selector (only component type matters)
        // - 'not_convalidated': Hide internal subject selector
        if (this.value === 'direct') {
            internalSubjectSelection.style.display = 'block';
            // Apply filter to hide elective subjects
            filterInternalSubjectOptions('direct');
        } else {
            internalSubjectSelection.style.display = 'none';
            // Reset filter when hiding
            filterInternalSubjectOptions('other');
        }
    });
});

// Handle component type change - show/hide "Crear nuevo código" checkbox
document.getElementById('component_type').addEventListener('change', function() {
    const componentType = this.value;
    const createNewCodeContainer = document.getElementById('create_new_code_container');
    const createNewCodeCheckbox = document.getElementById('create_new_code');
    const newCodeMessage = document.getElementById('new_code_message');
    
    // Null-safe checks
    if (!createNewCodeContainer || !createNewCodeCheckbox) {
        return;
    }
    
    // Show checkbox only for optativas and libre elección
    const showCheckbox = ['optional_fundamental', 'optional_professional', 'free_elective'].includes(componentType);
    createNewCodeContainer.style.display = showCheckbox ? 'block' : 'none';
    
    // Reset checkbox if hidden
    if (!showCheckbox) {
        createNewCodeCheckbox.checked = false;
        if (newCodeMessage) {
            newCodeMessage.style.display = 'none';
        }
    }
    
    // Filter available subjects based on component type and already used subjects
    if (showCheckbox) {
        // Pass current code if editing, null if creating new
        filterAvailableSubjects(componentType, currentInternalSubjectCode);
    }
});

function blockUsedOptativesAndFreeElectives(currentCode = null) {
    const externalCurriculumId = document.querySelector('[data-external-curriculum-id]')?.dataset.externalCurriculumId;
    
    if (!externalCurriculumId) return;
    
    // Fetch ONLY optativas and libres used (not all subjects)
    fetch(`/convalidation/${externalCurriculumId}/used-optatives-and-free`)
        .then(response => response.json())
        .then(data => {
            const internalSubjectSelect = document.getElementById('internal_subject_code');
            if (!internalSubjectSelect) return;
            
            const options = internalSubjectSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') return; // Skip empty option
                
                // If this is the current code (editing mode), don't disable it
                const isCurrentCode = currentCode && option.value === currentCode;
                const isUsedOptativeOrFree = data.usedOptativesAndFree.includes(option.value);
                
                // Block ONLY optativas/libres already used (like #LIBRE-01, #OPT-01)
                if (isUsedOptativeOrFree && !isCurrentCode) {
                    option.disabled = true;
                }
            });
        })
        .catch(error => {
            console.error('Error al cargar optativas/libres usadas:', error);
        });
}

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
    
    // First, get globally blocked subjects (optativas/libres)
    fetch(`/convalidation/${externalCurriculumId}/used-optatives-and-free`)
        .then(response => response.json())
        .then(globalData => {
            const globallyBlocked = globalData.usedOptativesAndFree;
            
            // Then, fetch used subjects for this specific component type
            return fetch(`/convalidation/${externalCurriculumId}/used-subjects?component_type=${componentType}`)
                .then(response => response.json())
                .then(data => {
                    const internalSubjectSelect = document.getElementById('internal_subject_code');
                    if (!internalSubjectSelect) return;
                    
                    const options = internalSubjectSelect.querySelectorAll('option');
                    
                    let availableCount = 0;
                    
                    options.forEach(option => {
                        if (option.value === '') return; // Skip empty option
                        
                        const isCurrentCode = currentCode && option.value === currentCode;
                        
                        // Check if it's globally blocked (optativa/libre used)
                        const isGloballyBlocked = globallyBlocked.includes(option.value);
                        
                        // Check if it's used for this component type
                        const isUsedForThisType = data.usedSubjects.includes(option.value);
                        
                        // Block if: (globally blocked OR used for this type) AND not current code
                        option.disabled = (isGloballyBlocked || isUsedForThisType) && !isCurrentCode;
                        
                        // Count as available if not disabled
                        if (!option.disabled) availableCount++;
                    });
                    
                    // Update label to show count
                    const label = document.querySelector('label[for="internal_subject_code"]');
                    if (label) {
                        const originalText = label.textContent.replace(/ \(\d+ disponibles?\)/, '');
                        label.textContent = `${originalText} (${availableCount} disponible${availableCount !== 1 ? 's' : ''})`;
                    }
                    
                    // Show/hide checkbox container based on availability (only for optativas/libres)
                    const isOptionalOrFree = ['optional_fundamental', 'optional_professional', 'free_elective'].includes(componentType);
                    const createNewCodeContainer = document.getElementById('create_new_code_container');
                    
                    if (isOptionalOrFree && availableCount === 0) {
                        createNewCodeContainer.style.display = 'block';
                        // Auto-check the checkbox since there are no available subjects
                        document.getElementById('create_new_code').checked = true;
                        document.getElementById('new_code_message').style.display = 'block';
                        internalSubjectSelect.disabled = true;
                    }
                });
        })
        .catch(error => {
            console.error('Error al cargar materias disponibles:', error);
        });
}

function saveConvalidation() {
    const formData = new FormData(document.getElementById('convalidationForm'));
    
    // Validate convalidation type is selected
    const convalidationType = formData.get('convalidation_type');
    if (!convalidationType) {
        showAlert('danger', 'Por favor seleccione el tipo de convalidación');
        return;
    }
    
    // Validate component type is selected
    const componentType = formData.get('component_type');
    if (!componentType) {
        showAlert('danger', 'Por favor seleccione un componente académico');
        return;
    }
    
    // Validate that direct convalidations have an internal subject (unless creating new code)
    const createNewCodeCheckbox = document.getElementById('create_new_code');
    const internalSubjectCode = formData.get('internal_subject_code');
    
    if (convalidationType === 'direct' && !internalSubjectCode && !(createNewCodeCheckbox && createNewCodeCheckbox.checked)) {
        showAlert('danger', 'Las convalidaciones directas requieren seleccionar una materia interna o marcar "Crear nuevo código"');
        return;
    }
    
    // Validate that flexible_component has a flexible component type
    if (convalidationType === 'flexible_component') {
        const flexibleComponents = ['optional_fundamental', 'optional_professional', 'free_elective'];
        if (!flexibleComponents.includes(componentType)) {
            showAlert('danger', 'Los componentes electivos deben ser Optativa Fundamental, Optativa Profesional o Libre Elección');
            return;
        }
    }
    
    // Check if "Crear nuevo código" is checked
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
            // Success - reload page to get fresh data and avoid modal state issues
            console.log('[Save] Convalidación guardada exitosamente, recargando página...');
            location.reload();
        } else {
            // Show error (modal stays open, flag stays true)
            showAlert('danger', data.error || 'Error al guardar la convalidación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error (modal stays open, flag stays true)
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
    } else if (convalidation.convalidation_type === 'flexible_component') {
        // Get component type label
        const componentType = convalidation.component_assignment?.component_type;
        const componentLabel = componentType ? getComponentLabel(componentType) : 'Componente Flexible';
        
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-layer-group text-info me-2"></i>
                <span class="fw-bold text-info">${componentLabel}</span>
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
    } else if (convalidation.convalidation_type === 'flexible_component') {
        statusHtml += '<span class="badge bg-info"><i class="fas fa-layer-group me-1"></i>Componente Electivo</span>';
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
    // Remove previous alerts to prevent accumulation
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at top of page
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 2 seconds
    const alertElement = container.querySelector('.alert');
    setTimeout(() => {
        if (alertElement && alertElement.parentElement) {
            // Use Bootstrap's fade out animation
            alertElement.classList.remove('show');
            // Remove from DOM after animation completes
            setTimeout(() => {
                if (alertElement.parentElement) {
                    alertElement.remove();
                }
            }, 150); // Bootstrap fade transition is 150ms
        }
    }, 2000); // 2 seconds
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
    // Fix aria-hidden warning by removing focus before hiding modal
    const modalElement = document.getElementById('convalidationModal');
    if (modalElement) {
        // Use 'hide.bs.modal' event (fires immediately when hide is called)
        modalElement.addEventListener('hide.bs.modal', function(e) {
            // Remove focus from the modal and its children immediately
            if (document.activeElement && (modalElement.contains(document.activeElement) || document.activeElement === modalElement)) {
                document.activeElement.blur();
                // Force focus to body
                setTimeout(() => {
                    if (document.body.focus) {
                        document.body.focus();
                    }
                }, 0);
            }
        });
        
        // Also handle the 'hidden.bs.modal' event (fires after modal is completely hidden)
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Double-check focus is moved away from modal after it's hidden
            if (document.activeElement === modalElement || (modalElement.contains && modalElement.contains(document.activeElement))) {
                if (document.body.focus) {
                    document.body.focus();
                }
            }
        });
    }
    
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.convalidation-config-btn');
        if (btn) {
            const externalSubjectId = btn.dataset.externalSubjectId;
            const convalidationType = btn.dataset.convalidationType;
            const internalSubjectCode = btn.dataset.internalSubjectCode;
            const componentType = btn.dataset.componentType;
            const notes = btn.dataset.notes;
            
            // Only pass existingData if we have convalidation data
            const existingData = (convalidationType || internalSubjectCode || componentType) ? {
                convalidationType,
                internalSubjectCode,
                componentType,
                notes
            } : null;
            
            showConvalidationModal(externalSubjectId, existingData);
        }
    });
    
    // Save active semester to localStorage when user switches tabs
    const semesterTabs = document.querySelectorAll('[data-bs-target^="#semester"]');
    semesterTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            const semesterNumber = target.replace('#semester-', '');
            
            // Get external curriculum ID from the page
            const curriculumContainer = document.querySelector('[data-external-curriculum-id]');
            if (curriculumContainer) {
                const curriculumId = curriculumContainer.getAttribute('data-external-curriculum-id');
                const storageKey = `convalidation_active_semester_${curriculumId}`;
                localStorage.setItem(storageKey, semesterNumber);
            }
        });
    });
    
    // Restore active semester from localStorage on page load
    const curriculumContainer = document.querySelector('[data-external-curriculum-id]');
    if (curriculumContainer) {
        const curriculumId = curriculumContainer.getAttribute('data-external-curriculum-id');
        const storageKey = `convalidation_active_semester_${curriculumId}`;
        const savedSemester = localStorage.getItem(storageKey);
        
        if (savedSemester) {
            // Restore the saved semester
            restoreActiveSemester(savedSemester);
        }
    }
});

// Impact Analysis Functions
function showImpactAnalysisModal() {
    const modal = new bootstrap.Modal(document.getElementById('impactAnalysisModal'));
    modal.show();
    
    // Load impact analysis immediately
    loadImpactAnalysis();
}

function loadImpactAnalysis() {
    // Show loading state
    document.getElementById('impact-analysis-loading').style.display = 'block';
    document.getElementById('impact-analysis-results').style.display = 'none';
    document.getElementById('impact-analysis-error').style.display = 'none';
    
    // Get curriculum ID
    const curriculumId = window.externalCurriculumId;
    
    // Build URL - use the existing analyzeConvalidationImpact endpoint
    const url = `/convalidation/${curriculumId}/analyze-impact`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderImpactAnalysis(data.results);
        } else {
            showImpactError(data.message || 'Error al cargar el análisis');
        }
    })
    .catch(error => {
        console.error('Error loading impact analysis:', error);
        showImpactError('Error de conexión al cargar el análisis');
    });
}

function renderImpactAnalysis(results) {
    // Store results for PDF generation
    currentImpactResults = results;
    
    // Hide loading, show results
    document.getElementById('impact-analysis-loading').style.display = 'none';
    document.getElementById('impact-analysis-results').style.display = 'block';
    document.getElementById('export-impact-pdf-btn').style.display = 'inline-block';
    
    console.log('=== INICIO DEBUG ANÁLISIS DE IMPACTO ===');
    console.log('Datos completos recibidos del backend:', results);
    
    // Get pre-calculated stats (same values as "Progreso de Carrera" progress bars)
    // These are the exact values shown in the dual progress bars:
    // - originalAssigned: credits from Original UNAL curriculum (left bar)
    // - newConvalidated: credits convalidated in Nueva Importada curriculum (right bar)
    const originalAssigned = parseFloat(results.original_assigned_credits) || 0;
    const newConvalidated = parseFloat(results.new_convalidated_credits) || 0;
    
    // Calculate difference (new - original, can be positive or negative)
    // This is the SAME calculation shown in "Diferencia de Créditos" message
    const creditDifference = newConvalidated - originalAssigned;
    
    // Credits lost is the absolute value of the difference
    const creditsLost = Math.abs(creditDifference);
    
    console.log('\n--- CRÉDITOS DEL PROGRESO DE CARRERA ---');
    console.log('Original UNAL (assigned_credits - barra izquierda):', originalAssigned);
    console.log('Nueva Importada (convalidated_credits - barra derecha):', newConvalidated);
    console.log('Diferencia (nueva - original):', creditDifference);
    console.log('Créditos Perdidos (|diferencia|):', creditsLost);
    
    // Get component breakdown for the table
    const convalidatedCredits = results.convalidated_credits_by_component || {};
    const originalCredits = results.original_curriculum_credits || {};
    
    console.log('\n--- CRÉDITOS POR COMPONENTE (para tabla) ---');
    console.log('Convalidados de malla externa:', JSON.stringify(convalidatedCredits, null, 2));
    console.log('Totales de malla original UNAL:', JSON.stringify(originalCredits, null, 2));
    console.log('¿Es correcto que sea 45?', creditsLost === 45 ? '✓ SÍ' : '✗ NO, es ' + creditsLost);
    
    // Summary cards
    document.getElementById('impact-convalidated-credits').textContent = Math.round(newConvalidated);
    document.getElementById('impact-lost-credits').textContent = Math.round(creditsLost);
    document.getElementById('impact-new-subjects').textContent = results.additional_subjects_required || 0;
    document.getElementById('impact-progress-percentage').textContent = 
        (results.average_progress_change || 0).toFixed(1) + '%';
    
    // Credits by component - use new data structure
    renderCreditsByComponent(
        results.convalidated_credits_by_component || {}, 
        results.original_curriculum_credits || {}
    );
    
    // Subject mapping - show configured convalidations
    renderSubjectMapping(results);
}

function renderCreditsByComponent(convalidatedCredits, originalCredits) {
    const tbody = document.getElementById('impact-credits-by-component');
    tbody.innerHTML = '';
    
    const componentNames = {
        'fundamental_required': 'Obligatoria Fundamental',
        'professional_required': 'Obligatoria Profesional',
        'optional_fundamental': 'Optativa Fundamental',
        'optional_professional': 'Optativa Profesional',
        'free_elective': 'Libre Elección',
        'thesis': 'Trabajo de Grado',
        'leveling': 'Nivelación'
    };
    
    // Process each component
    Object.keys(componentNames).forEach(component => {
        const convalidated = convalidatedCredits[component] || 0;
        const original = originalCredits[component] || 0;
        const difference = convalidated - original;
        
        // Determine status color and badge
        let badgeClass = 'bg-secondary';
        let statusIcon = 'fa-minus';
        let statusText = '0';
        
        if (difference > 0) {
            badgeClass = 'bg-success';
            statusIcon = 'fa-arrow-up';
            statusText = `+${difference}`;
        } else if (difference < 0) {
            badgeClass = 'bg-danger';
            statusIcon = 'fa-arrow-down';
            statusText = `${difference}`;
        }
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${componentNames[component]}</td>
            <td class="text-center"><strong>${original}</strong> créditos</td>
            <td class="text-center"><strong>${convalidated}</strong> créditos</td>
            <td class="text-center">
                <span class="badge ${badgeClass}">
                    <i class="fas ${statusIcon} me-1"></i>
                    ${statusText} créditos
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function renderSubjectMapping(results) {
    const tbody = document.getElementById('impact-subject-mapping');
    tbody.innerHTML = '';
    
    console.log('\n=== INICIO DEBUG MAPEO DETALLADO ===');
    
    // Get all convalidations from current page (from the DOM)
    const convalidationRows = document.querySelectorAll('[data-external-subject-id][data-convalidation-type]');
    
    console.log('Total de filas encontradas con data-external-subject-id:', convalidationRows.length);
    
    if (convalidationRows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle me-2"></i>
                    No hay convalidaciones configuradas aún
                </td>
            </tr>
        `;
        console.log('No se encontraron filas de convalidación');
        return;
    }
    
    let rowIndex = 0;
    const processedIds = new Set(); // Para evitar duplicados
    
    // Parse and display each convalidation
    convalidationRows.forEach(row => {
        rowIndex++;
        
        // Read external subject data from data-attributes
        const externalSubjectId = row.dataset.externalSubjectId;
        const externalSubjectName = row.dataset.subjectName || 'Sin nombre';
        const externalSubjectCode = row.dataset.subjectCode || '-';
        const externalCredits = row.dataset.subjectCredits || '0';
        const convalidationType = row.dataset.convalidationType;
        
        console.log(`\n--- Fila ${rowIndex} ---`);
        console.log('  ID:', externalSubjectId);
        console.log('  Nombre externo:', externalSubjectName);
        console.log('  Código externo:', externalSubjectCode);
        console.log('  Créditos externos:', externalCredits);
        console.log('  Tipo convalidación:', convalidationType);
        
        // Skip rows without convalidation type (not convalidated yet)
        if (!convalidationType) {
            console.log('  → OMITIDA (sin tipo de convalidación)');
            return;
        }
        
        // Skip duplicate rows (same ID already processed)
        if (processedIds.has(externalSubjectId)) {
            console.log('  → OMITIDA (ID duplicado)');
            return;
        }
        
        // Skip rows with no name and no credits (invalid data)
        if (externalSubjectName === 'Sin nombre' && externalCredits === '0') {
            console.log('  → OMITIDA (sin datos válidos)');
            return;
        }
        
        processedIds.add(externalSubjectId);
        
        let convalidationInfo = '';
        let componentInfo = '';
        
        if (convalidationType === 'direct') {
            // Direct convalidation - show internal UNAL subject
            const internalSubjectName = row.dataset.internalSubjectName || 'Sin materia';
            const internalSubjectCode = row.dataset.internalSubjectCode || '-';
            const internalCredits = row.dataset.internalCredits || '0';
            
            console.log('  Tipo: DIRECTA');
            console.log('    → Materia UNAL:', internalSubjectName);
            console.log('    → Código UNAL:', internalSubjectCode);
            console.log('    → Créditos UNAL:', internalCredits);
            
            convalidationInfo = `
                <div>
                    <strong class="text-success">${internalSubjectName}</strong><br>
                    <small class="text-muted">
                        <code>${internalSubjectCode}</code> - 
                        <span class="badge bg-success">${internalCredits} créditos</span>
                    </small>
                </div>
            `;
            
            // Get component from row dataset
            const componentType = row.dataset.componentType;
            console.log('    → Componente:', componentType);
            if (componentType) {
                componentInfo = `<span class="badge bg-${getComponentColor(componentType)}">${getComponentLabel(componentType)}</span>`;
            }
            
        } else if (convalidationType === 'flexible_component') {
            // Flexible component - show component only
            const componentType = row.dataset.componentType;
            
            console.log('  Tipo: COMPONENTE FLEXIBLE');
            console.log('    → Componente:', componentType);
            
            convalidationInfo = `
                <div class="text-info">
                    <i class="fas fa-layer-group me-1"></i>
                    <strong>Componente Electivo</strong><br>
                    <small class="text-muted fst-italic">Créditos flexibles (sin materia específica)</small>
                </div>
            `;
            
            if (componentType) {
                componentInfo = `<span class="badge bg-${getComponentColor(componentType)}">${getComponentLabel(componentType)}</span>`;
            }
            
        } else if (convalidationType === 'not_convalidated') {
            // Not convalidated - new subject
            const componentType = row.dataset.componentType;
            
            console.log('  Tipo: NO CONVALIDADA (materia nueva)');
            console.log('    → Componente:', componentType);
            
            convalidationInfo = `
                <div class="text-warning">
                    <i class="fas fa-plus-circle me-1"></i>
                    <strong>Materia Nueva</strong><br>
                    <small class="text-muted">No tiene equivalencia en malla UNAL</small>
                </div>
            `;
            
            if (componentType) {
                componentInfo = `<span class="badge bg-${getComponentColor(componentType)}">${getComponentLabel(componentType)}</span>`;
            }
        }
        
        console.log('  → AÑADIDA a la tabla');
        
        const tableRow = document.createElement('tr');
        tableRow.innerHTML = `
            <td>
                <strong>${externalSubjectName}</strong><br>
                <small class="text-muted">
                    <code>${externalSubjectCode}</code> - 
                    <span class="badge bg-primary">${externalCredits} créditos</span>
                </small>
            </td>
            <td class="text-center">
                <i class="fas fa-arrow-right text-primary fa-lg"></i>
            </td>
            <td>${convalidationInfo}</td>
            <td class="text-center">${componentInfo || '-'}</td>
        `;
        tbody.appendChild(tableRow);
    });
    
    console.log('\n=== FIN DEBUG MAPEO DETALLADO ===');
    console.log(`Total de filas añadidas a la tabla: ${tbody.children.length}`);
    console.log(`IDs procesados únicos: ${processedIds.size}`);
}

function showImpactError(message) {
    document.getElementById('impact-analysis-loading').style.display = 'none';
    document.getElementById('impact-analysis-results').style.display = 'none';
    document.getElementById('impact-analysis-error').style.display = 'block';
    document.getElementById('impact-error-message').textContent = message;
}

function exportImpactAnalysis() {
    // TODO: Implement export functionality
    alert('Funcionalidad de exportación en desarrollo');
}

/**
 * Generate PDF report from the impact analysis in show view
 */
function generateImpactPdfReportFromShow() {
    if (!currentImpactResults || !window.externalCurriculumId) {
        alert('No hay resultados para generar el reporte');
        return;
    }

    // Show loading state
    const button = document.getElementById('export-impact-pdf-btn');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generando reporte...';

    // Send request to generate PDF report view
    fetch(`/convalidation/${window.externalCurriculumId}/impact-report-pdf`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        },
        body: JSON.stringify({
            results: currentImpactResults,
            credit_limits: {} // Empty for now, can be added later if needed
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al generar el reporte');
        }
        return response.text();
    })
    .then(html => {
        // Open the report in a new window
        const newWindow = window.open('', '_blank');
        if (newWindow) {
            newWindow.document.write(html);
            newWindow.document.close();
            
            // Show success message
            alert('✓ Reporte generado. Use Ctrl+P o Cmd+P en la nueva ventana para imprimir como PDF');
        } else {
            throw new Error('No se pudo abrir la ventana del reporte. Verifique que no esté bloqueando ventanas emergentes');
        }

        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('✗ Error al generar el reporte PDF: ' + error.message);
        
        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

/**
 * Generate PDF report of impact analysis
 * This triggers the impact analysis and then generates the PDF report
 */
function generateConvalidationReportPdf() {
    if (!window.externalCurriculumId) {
        alert('Error: No se pudo identificar la malla curricular');
        return;
    }

    // Show loading modal
    const loadingHtml = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Analizando...</span>
            </div>
            <h5>Analizando impacto en estudiantes...</h5>
            <p class="text-muted">Esto puede tomar unos momentos</p>
        </div>
    `;
    
    // Create a temporary modal to show loading
    const modalHtml = `
        <div class="modal fade" id="loadingAnalysisModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        ${loadingHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page and show it
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingAnalysisModal'));
    loadingModal.show();

    // Perform the impact analysis first
    fetch(`/convalidation/${window.externalCurriculumId}/analyze-impact`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al analizar el impacto');
        }
        return response.json();
    })
    .then(data => {
        if (!data.results || data.results.length === 0) {
            throw new Error('No se encontraron resultados de impacto');
        }

        // Store results for PDF generation
        currentImpactResults = data.results;

        // Now generate the PDF report
        return fetch(`/convalidation/${window.externalCurriculumId}/impact-report-pdf`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({
                results: data.results,
                credit_limits: data.credit_limits || {}
            })
        });
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al generar el reporte PDF');
        }
        return response.text();
    })
    .then(html => {
        // Hide loading modal
        loadingModal.hide();
        document.getElementById('loadingAnalysisModal').remove();
        document.querySelector('.modal-backdrop')?.remove();

        // Open the report in a new window
        const newWindow = window.open('', '_blank');
        if (newWindow) {
            newWindow.document.write(html);
            newWindow.document.close();
            
            // Show success message
            alert('✓ Reporte de impacto generado. Use Ctrl+P o Cmd+P en la nueva ventana para imprimir como PDF');
        } else {
            throw new Error('No se pudo abrir la ventana del reporte. Verifique que no esté bloqueando ventanas emergentes');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Hide loading modal if it exists
        try {
            loadingModal.hide();
            document.getElementById('loadingAnalysisModal')?.remove();
            document.querySelector('.modal-backdrop')?.remove();
        } catch (e) {
            // Ignore errors when hiding modal
        }
        
        alert('✗ Error al generar el reporte: ' + error.message);
    });
}

// Reset modalIsOpen flag when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('convalidationModal');
    if (modalElement) {
        // Reset flag when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            modalIsOpen = false;
        });
        
        // Also reset on shown in case of issues
        modalElement.addEventListener('shown.bs.modal', function() {
            // Modal is now fully visible and initialized
            // Flag is already true from showConvalidationModal
        });
    }
});
