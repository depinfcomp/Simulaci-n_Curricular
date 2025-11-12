// Simulation View JavaScript

/**
 * Check if a subject is a leveling subject
 * @param {string} code - Subject code
 * @param {string} type - Subject type from data-type attribute
 * @returns {boolean} - True if subject is leveling
 */
function isLevelingSubject(code, type = null) {
    // Check if type is explicitly 'nivelacion'
    if (type === 'nivelacion') {
        return true;
    }
    
    // Check if code is in leveling subjects list (loaded from database)
    if (window.levelingSubjectsCodes && Array.isArray(window.levelingSubjectsCodes)) {
        return window.levelingSubjectsCodes.includes(code);
    }
    
    return false;
}

/**
 * Show a professional modal instead of alert()
 * @param {string} message - The message to display
 * @param {string} type - Type of modal: 'error', 'warning', 'info', 'success'
 * @param {string} title - Optional custom title
 */
function showAlertModal(message, type = 'info', title = null) {
    const typeConfig = {
        error: {
            icon: 'fas fa-exclamation-circle',
            color: 'danger',
            defaultTitle: 'Error'
        },
        warning: {
            icon: 'fas fa-exclamation-triangle',
            color: 'warning',
            defaultTitle: 'Advertencia'
        },
        info: {
            icon: 'fas fa-info-circle',
            color: 'info',
            defaultTitle: 'Información'
        },
        success: {
            icon: 'fas fa-check-circle',
            color: 'success',
            defaultTitle: 'Éxito'
        }
    };

    const config = typeConfig[type] || typeConfig.info;
    const modalTitle = title || config.defaultTitle;
    
    const modalHtml = `
        <div class="modal fade" id="alertModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-${config.color} text-white">
                        <h5 class="modal-title">
                            <i class="${config.icon} me-2"></i>
                            ${modalTitle}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-${config.color}" data-bs-dismiss="modal">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing alert modal if any
    const existingModal = document.getElementById('alertModal');
    if (existingModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
        if (existingModalInstance) {
            existingModalInstance.dispose();
        }
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modalElement = document.getElementById('alertModal');
    const modal = new bootstrap.Modal(modalElement);
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalElement.remove();
    });
    
    modal.show();
}

/**
 * Show a professional confirmation modal instead of confirm()
 * @param {string} message - The message to display
 * @param {function} onConfirm - Callback function to execute when user confirms
 * @param {string} type - Type of modal: 'danger', 'warning', 'info', 'primary'
 * @param {string} title - Optional custom title
 * @param {string} confirmText - Text for confirm button (default: 'Aceptar')
 * @param {string} cancelText - Text for cancel button (default: 'Cancelar')
 */
function showConfirmModal(message, onConfirm, type = 'warning', title = null, confirmText = 'Aceptar', cancelText = 'Cancelar') {
    const typeConfig = {
        danger: {
            icon: 'fas fa-exclamation-triangle',
            defaultTitle: 'Confirmar Acción'
        },
        warning: {
            icon: 'fas fa-exclamation-circle',
            defaultTitle: 'Confirmación'
        },
        info: {
            icon: 'fas fa-question-circle',
            defaultTitle: 'Confirmar'
        },
        primary: {
            icon: 'fas fa-check-circle',
            defaultTitle: 'Confirmación'
        }
    };

    const config = typeConfig[type] || typeConfig.warning;
    const modalTitle = title || config.defaultTitle;
    
    const modalHtml = `
        <div class="modal fade" id="confirmModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-${type} text-white">
                        <h5 class="modal-title">
                            <i class="${config.icon} me-2"></i>
                            ${modalTitle}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" style="white-space: pre-line;">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            ${cancelText}
                        </button>
                        <button type="button" class="btn btn-${type}" id="confirmModalBtn">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing confirm modal if any
    const existingModal = document.getElementById('confirmModal');
    if (existingModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
        if (existingModalInstance) {
            existingModalInstance.dispose();
        }
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modalElement = document.getElementById('confirmModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Handle confirm button
    document.getElementById('confirmModalBtn').addEventListener('click', function() {
        modal.hide();
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalElement.remove();
    });
    
    modal.show();
}

/**
 * Show a professional prompt modal instead of prompt()
 * @param {string} message - The message to display
 * @param {function} onSubmit - Callback function with the input value
 * @param {string} title - Modal title
 * @param {string} placeholder - Input placeholder
 * @param {string} defaultValue - Default input value
 */
function showPromptModal(message, onSubmit, title = 'Entrada Requerida', placeholder = '', defaultValue = '') {
    const modalHtml = `
        <div class="modal fade" id="promptModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>
                            ${title}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3" style="white-space: pre-line;">${message}</p>
                        <div class="form-group">
                            <textarea class="form-control" id="promptModalInput" rows="3" 
                                      placeholder="${placeholder}">${defaultValue}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="promptModalSubmitBtn">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing prompt modal if any
    const existingModal = document.getElementById('promptModal');
    if (existingModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
        if (existingModalInstance) {
            existingModalInstance.dispose();
        }
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modalElement = document.getElementById('promptModal');
    const modal = new bootstrap.Modal(modalElement);
    const inputElement = document.getElementById('promptModalInput');
    
    // Handle submit button
    document.getElementById('promptModalSubmitBtn').addEventListener('click', function() {
        const value = inputElement.value.trim();
        modal.hide();
        if (typeof onSubmit === 'function') {
            onSubmit(value);
        }
    });
    
    // Handle Enter key
    inputElement.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('promptModalSubmitBtn').click();
        }
    });
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalElement.remove();
    });
    
    modal.show();
    
    // Focus input after modal is shown
    modalElement.addEventListener('shown.bs.modal', function () {
        inputElement.focus();
    });
}

// Make sure key functions are available immediately
window.addNewSubject = function() {
    console.log('addNewSubject called');
    if (typeof showAddSubjectModal === 'function') {
        showAddSubjectModal();
    } else {
        showAlertModal('Por favor espere a que la página cargue completamente', 'warning', 'Función no disponible');
    }
};

window.exportModifiedCurriculum = function() {
    console.log('exportModifiedCurriculum called');
    if (typeof getCurrentCurriculumState === 'function' && typeof showExportModal === 'function') {
        const modifiedCurriculum = getCurrentCurriculumState();
        showExportModal(modifiedCurriculum);
    } else {
        showAlertModal('Por favor espere a que la página cargue completamente', 'warning', 'Función no disponible');
    }
};

window.showComponentCredits = function() {
    console.log('showComponentCredits called');
    
    // Calculate credits by component from current curriculum
    const subjects = document.querySelectorAll('.subject-card');
    
    const credits = {
        'optativa_profesional': 0,
        'fundamental': 0,
        'optativa_fundamentacion': 0,
        'profesional': 0,
        'libre_eleccion': 0,
        'trabajo_grado': 0,
        'nivelacion': 0
    };
    
    subjects.forEach(card => {
        const type = card.dataset.type;
        const creditsElement = card.querySelector('.info-box:nth-child(1)');
        if (creditsElement && type) {
            const creditValue = parseInt(creditsElement.textContent) || 0;
            
            // Map types to components
            if (type === 'optativa_profesional') {
                credits.optativa_profesional += creditValue;
            } else if (type === 'fundamental') {
                credits.fundamental += creditValue;
            } else if (type === 'optativa_fundamentacion') {
                credits.optativa_fundamentacion += creditValue;
            } else if (type === 'profesional') {
                credits.profesional += creditValue;
            } else if (type === 'libre_eleccion') {
                credits.libre_eleccion += creditValue;
            } else if (type === 'trabajo_grado') {
                credits.trabajo_grado += creditValue;
            } else if (type === 'nivelacion') {
                credits.nivelacion += creditValue;
            }
        }
    });
    
    // Calculate totals
    const total = credits.optativa_profesional + credits.fundamental + 
                 credits.optativa_fundamentacion + credits.profesional + 
                 credits.libre_eleccion + credits.trabajo_grado;
    const grandTotal = total + credits.nivelacion;
    
    // Update modal content
    document.getElementById('credit-optativa-profesional').textContent = credits.optativa_profesional;
    document.getElementById('credit-fundamental').textContent = credits.fundamental;
    document.getElementById('credit-optativa-fundamentacion').textContent = credits.optativa_fundamentacion;
    document.getElementById('credit-profesional').textContent = credits.profesional;
    document.getElementById('credit-libre-eleccion').textContent = credits.libre_eleccion;
    document.getElementById('credit-trabajo-grado').textContent = credits.trabajo_grado;
    document.getElementById('credit-total').textContent = total;
    document.getElementById('credit-nivelacion').textContent = credits.nivelacion;
    document.getElementById('credit-grand-total').textContent = grandTotal;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('componentCreditsModal'));
    modal.show();
};

document.addEventListener('DOMContentLoaded', function() {
    const subjectCards = document.querySelectorAll('.subject-card');
    let selectedCard = null;
    let selectedSubjectId = null; // Track by ID instead of element reference
    let draggedCard = null;
    let simulationChanges = [];
    let originalCurriculum = {};
    
    // Global variables for credits
    let careerCredits = 0;  // Credits excluding leveling subjects
    let totalCredits = 0;   // All credits including leveling
    
    // ============================================
    // PERSISTENT STORAGE SYSTEM (localStorage)
    // ============================================
    
    const STORAGE_KEY = 'simulation_temporary_changes';
    const CURRICULUM_ID = 'simulation'; // Fixed ID for the main simulation
    
    // Save changes to localStorage
    function saveChangesToStorage() {
        try {
            const storageData = {
                changes: simulationChanges,
                timestamp: new Date().toISOString(),
                curriculumId: CURRICULUM_ID
            };
            const jsonData = JSON.stringify(storageData);
            localStorage.setItem(STORAGE_KEY, jsonData);
        } catch (error) {
            console.error('Error guardando cambios:', error);
        }
    }
    
    // Load changes from localStorage
    function loadChangesFromStorage() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (!stored) {
                return null;
            }
            
            const storageData = JSON.parse(stored);
            
            // Verify it's for the same curriculum
            if (storageData.curriculumId !== CURRICULUM_ID) {
                return null;
            }
            
            return storageData.changes;
        } catch (error) {
            console.error('Error cargando cambios:', error);
            return null;
        }
    }
    
    // Clear changes from localStorage
    function clearStoredChanges() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (error) {
            console.error('Error limpiando cambios:', error);
        }
    }
    
    // Initialize total credits from all visible cards
    function initializeTotalCredits() {
        careerCredits = 0;
        totalCredits = 0;
        document.querySelectorAll('.subject-card').forEach(card => {
            const creditsElement = card.querySelector('.info-box:first-child .info-value');
            if (creditsElement) {
                const credits = parseInt(creditsElement.textContent) || 0;
                totalCredits += credits;
                
                // Check if it's a leveling subject
                const subjectCode = card.dataset.subjectId;
                const subjectType = card.dataset.type;
                const isLeveling = isLevelingSubject(subjectCode, subjectType);
                if (!isLeveling) {
                    careerCredits += credits;
                }
            }
        });
        updateCreditsDisplay();
    }
    
    // Update credits display
    function updateCreditsDisplay() {
        const careerCreditsElement = document.getElementById('career-credits');
        const totalCreditsElement = document.getElementById('total-credits');
        
        if (careerCreditsElement) {
            careerCreditsElement.textContent = careerCredits;
        }
        if (totalCreditsElement) {
            totalCreditsElement.textContent = totalCredits;
        }
    }
    
    // Initialize on page load
    initializeTotalCredits();
    
    // Restore temporary changes from localStorage
    restoreTemporaryChanges();
    
    // Function to restore changes from localStorage
    function restoreTemporaryChanges() {
        const storedChanges = loadChangesFromStorage();
        if (!storedChanges || storedChanges.length === 0) {
            return;
        }
        
        simulationChanges = storedChanges;
        
        // Apply visual changes to the DOM
        storedChanges.forEach(change => {
            const card = document.querySelector(`[data-subject-id="${change.subject_code}"]`);
            
            switch(change.type) {
                case 'added':
                    // Mark card as added (green border)
                    if (card) {
                        card.classList.add('added-subject');
                    } else {
                        // Card doesn't exist, need to recreate it
                        if (change.new_value && change.new_value.semester) {
                            const data = change.new_value;
                            
                            // Recreate the card
                            const newCard = createSubjectCard(
                                change.subject_code,
                                data.name || change.subject_name,
                                data.semester,
                                Array.isArray(data.prerequisites) ? data.prerequisites.join(',') : (data.prerequisites || ''),
                                data.description || '',
                                data.credits || 3,
                                data.classroomHours || 3,
                                data.studentHours || 6,
                                data.type || 'profesional',
                                data.isRequired !== false
                            );
                            
                            // Add to the appropriate semester
                            const semesterColumn = document.querySelector(`[data-semester="${data.semester}"] .subject-list`);
                            if (semesterColumn) {
                                semesterColumn.appendChild(newCard);
                                
                                // Enable drag and drop
                                enableDragAndDropForCard(newCard);
                                
                                // Update credits
                                const isLeveling = isLevelingSubject(change.subject_code, data.type);
                                totalCredits += (data.credits || 0);
                                if (!isLeveling) {
                                    careerCredits += (data.credits || 0);
                                }
                            }
                        }
                    }
                    break;
                    
                case 'removed':
                    // Apply removal preview style
                    if (card) {
                        applyRemovedStyle(card);
                    }
                    break;
                    
                case 'semester':
                    // Move card to new semester
                    if (card && change.new_value && change.old_value) {
                        const newSemester = change.new_value;
                        const oldSemester = change.old_value;
                        
                        const newSemesterColumn = document.querySelector(`[data-semester="${newSemester}"] .subject-list`);
                        const oldSemesterColumn = document.querySelector(`[data-semester="${oldSemester}"] .subject-list`);
                        
                        if (newSemesterColumn) {
                            // Move card to new location
                            newSemesterColumn.appendChild(card);
                            card.classList.add('moved-subject'); // Mark as moved to different semester
                            
                            // Create ghost copy in original location
                            if (oldSemesterColumn) {
                                const subjectCode = change.subject_code;
                                
                                // Remove any existing ghost for this subject
                                const existingGhost = oldSemesterColumn.querySelector(`[data-ghost-of="${subjectCode}"]`);
                                if (existingGhost) {
                                    existingGhost.remove();
                                }
                                
                                // Clone the card to create a ghost
                                const ghostCard = card.cloneNode(true);
                                ghostCard.classList.add('moved-ghost');
                                ghostCard.classList.remove('moved-subject'); // Remove blue style from ghost
                                ghostCard.dataset.ghostOf = subjectCode; // Mark as ghost
                                ghostCard.draggable = false; // Can't drag the ghost
                                
                                // Make the semester badge semi-transparent
                                const ghostBadge = ghostCard.querySelector('.semester-badge');
                                if (ghostBadge) {
                                    ghostBadge.style.opacity = '0.3';
                                    // Keep original semester number in ghost
                                    ghostBadge.textContent = `Semestre ${oldSemester}`;
                                }
                                
                                // Insert the ghost in old location
                                oldSemesterColumn.appendChild(ghostCard);
                            }
                        }
                    }
                    break;
                    
                case 'prerequisites':
                    // Update prerequisites display
                    if (card) {
                        card.classList.add('added-subject'); // Mark as changed
                        card.dataset.prerequisites = change.new_value;
                    }
                    break;
                    
                case 'edit':
                    // Subject was edited (name, credits, hours, etc.)
                    if (card && change.new_value) {
                        const data = change.new_value;
                        
                        // Update card UI with edited values
                        if (data.name) {
                            const nameElement = card.querySelector('.subject-name');
                            if (nameElement) nameElement.textContent = data.name;
                        }
                        
                        if (data.credits) {
                            const creditsElement = card.querySelector('.subject-card-header .info-box:nth-child(1) .info-value');
                            if (creditsElement) creditsElement.textContent = data.credits;
                        }
                        
                        if (data.classroom_hours !== undefined) {
                            const classroomElement = card.querySelector('.subject-card-header .info-box:nth-child(2) .info-value');
                            if (classroomElement) classroomElement.textContent = data.classroom_hours;
                        }
                        
                        if (data.student_hours !== undefined) {
                            const studentElement = card.querySelector('.subject-card-header .info-box:nth-child(3) .info-value');
                            if (studentElement) studentElement.textContent = data.student_hours;
                        }
                        
                        if (data.description !== undefined) {
                            card.title = data.description;
                        }
                        
                        // Mark as edited
                        card.classList.add('edited-subject');
                    }
                    break;
            }
        });
        
        // Update credits display
        updateCreditsDisplay();
        
        // Update status display
        updateSimulationStatus();
        
        // Update unlocks relationships
        updateUnlocksRelationships();
    }
    
    // Robust modal cleanup function
    function cleanupModal(modalElement) {
        if (!modalElement) return;
        
        try {
            // Clean up Bootstrap modal instance
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.dispose();
            }
            
            // Remove the modal element
            modalElement.remove();
            
            // Additional cleanup: remove any orphaned backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                try {
                    backdrop.remove();
                } catch (e) {
                    console.warn('Error removing backdrop:', e);
                }
            });
            
            // Clean up body classes if no more modals exist
            const remainingModals = document.querySelectorAll('.modal');
            if (remainingModals.length === 0) {
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }
            
        } catch (e) {
            console.warn('Error during modal cleanup:', e);
        }
    }
    
    // Store original curriculum state
    function storeOriginalCurriculum() {
        // First, get the original order from server
        fetch('/simulation/original-order')
            .then(response => response.json())
            .then(originalOrder => {
                // Store the original order for reset
                window.originalOrder = originalOrder;
                
                // Store current state
                subjectCards.forEach(card => {
                    const subjectId = card.dataset.subjectId;
                    const semester = card.closest('.semester-column').dataset.semester;
                    const prerequisites = card.dataset.prerequisites.split(',').filter(p => p.trim());
                    
                    originalCurriculum[subjectId] = {
                        semester: semester,
                        prerequisites: prerequisites,
                        element: card
                    };
                });
                
                console.log('Original curriculum stored with proper order');
            })
            .catch(error => {
                console.error('Error loading original order:', error);
                // Fallback to current state
                subjectCards.forEach(card => {
                    const subjectId = card.dataset.subjectId;
                    const semester = card.closest('.semester-column').dataset.semester;
                    const prerequisites = card.dataset.prerequisites.split(',').filter(p => p.trim());
                    
                    originalCurriculum[subjectId] = {
                        semester: semester,
                        prerequisites: prerequisites,
                        element: card
                    };
                });
            });
    }
    
    // Initialize simulation system
    function initializeSimulation() {
        storeOriginalCurriculum();
        enableDragAndDrop();
        
        // Debug: Log initialization
        console.log('Simulation initialized');
        console.log('Subject cards found:', subjectCards.length);
        console.log('Semester columns found:', document.querySelectorAll('.semester-column').length);
        console.log('Original curriculum stored:', Object.keys(originalCurriculum).length);
    }
    
    // Function to clear all highlights
    function clearHighlights() {
        subjectCards.forEach(card => {
            card.classList.remove('prerequisite', 'unlocks', 'selected');
            // Reset transform to avoid visual issues
            card.style.transform = '';
        });
        
        // Also clear highlights from dynamically added cards
        document.querySelectorAll('.subject-card').forEach(card => {
            card.classList.remove('prerequisite', 'unlocks', 'selected');
            card.style.transform = '';
        });
    }
    
    // Function to highlight prerequisites and unlocks
    function highlightRelated(card) {
        clearHighlights();
        
        const subjectId = card.dataset.subjectId;
        const prerequisites = card.dataset.prerequisites.split(',').filter(p => p.trim());
        const unlocks = card.dataset.unlocks.split(',').filter(u => u.trim());
        
        // Highlight the selected card
        card.classList.add('selected');
        
        // Highlight prerequisites (yellow)
        prerequisites.forEach(prereqCode => {
            const prereqCard = document.querySelector(`[data-subject-id="${prereqCode}"]`);
            if (prereqCard) {
                prereqCard.classList.add('prerequisite');
            }
        });
        
        // Highlight unlocks (blue)
        unlocks.forEach(unlockCode => {
            const unlockCard = document.querySelector(`[data-subject-id="${unlockCode}"]`);
            if (unlockCard) {
                unlockCard.classList.add('unlocks');
            }
        });
        
        console.log(`Selected: ${subjectId}`);
        console.log(`Prerequisites: ${prerequisites.join(', ')}`);
        console.log(`Unlocks: ${unlocks.join(', ')}`);
    }
    
    // Enable drag and drop functionality using event delegation
    function enableDragAndDrop() {
        // Set ALL subject cards as draggable (original and new ones)
        document.querySelectorAll('.subject-card').forEach(card => {
            card.draggable = true;
            console.log('Made draggable:', card.dataset.subjectId);
        });
        
        console.log('Total cards made draggable:', document.querySelectorAll('.subject-card').length);
        
        let currentPlaceholder = null;
        
        // Use event delegation for drag events
        document.addEventListener('dragstart', function(e) {
            if (e.target.classList.contains('subject-card')) {
                draggedCard = e.target;
                e.target.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', e.target.outerHTML);
                console.log('Drag started:', e.target.dataset.subjectId);
            }
        });
        
        document.addEventListener('dragend', function(e) {
            if (e.target.classList.contains('subject-card')) {
                e.target.classList.remove('dragging');
                console.log('Drag ended:', e.target.dataset.subjectId);
                
                // Remove any existing placeholder
                if (currentPlaceholder) {
                    currentPlaceholder.remove();
                    currentPlaceholder = null;
                }
                
                // Remove all shift classes
                document.querySelectorAll('.subject-card').forEach(card => {
                    card.classList.remove('drag-shift-up', 'drag-shift-down');
                    card.style.transform = '';
                });
                
                draggedCard = null;
            }
        });
        
        // Add drop zones to semester columns
        const semesterColumns = document.querySelectorAll('.semester-column');
        console.log('Found semester columns:', semesterColumns.length);
        
        semesterColumns.forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
                
                if (!draggedCard) return;
                
                // Get the semester of the dragged card and current column
                const draggedSemester = draggedCard.closest('.semester-column')?.dataset.semester;
                const currentSemester = this.dataset.semester;
                
                // Only show placeholder if moving within the same semester
                if (draggedSemester === currentSemester) {
                    // Find the subject list
                    const subjectList = this.querySelector('.subject-list');
                    if (!subjectList) return;
                    
                    // Get all cards in this column (excluding dragged card)
                    const cards = Array.from(subjectList.querySelectorAll('.subject-card:not(.dragging)'));
                    
                    // Find which card we're hovering over
                    const afterCard = cards.reduce((closest, child) => {
                        const box = child.getBoundingClientRect();
                        const offset = e.clientY - box.top - box.height / 2;
                        
                        if (offset < 0 && offset > closest.offset) {
                            return { offset: offset, element: child };
                        } else {
                            return closest;
                        }
                    }, { offset: Number.NEGATIVE_INFINITY }).element;
                    
                    // Remove existing placeholder
                    if (currentPlaceholder) {
                        currentPlaceholder.remove();
                    }
                    
                    // Create new placeholder
                    const placeholder = document.createElement('div');
                    placeholder.className = 'drag-placeholder';
                    currentPlaceholder = placeholder;
                    
                    // Insert placeholder at appropriate position
                    if (afterCard) {
                        subjectList.insertBefore(placeholder, afterCard);
                    } else {
                        subjectList.appendChild(placeholder);
                    }
                } else {
                    // Different semester - remove any existing placeholder
                    if (currentPlaceholder) {
                        currentPlaceholder.remove();
                        currentPlaceholder = null;
                    }
                }
            });
            
            column.addEventListener('dragleave', function(e) {
                // Only remove drag-over if we're actually leaving the column
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
                    
                    // Remove placeholder when leaving column
                    if (currentPlaceholder && !this.contains(e.relatedTarget)) {
                        setTimeout(() => {
                            if (currentPlaceholder) {
                                currentPlaceholder.remove();
                                currentPlaceholder = null;
                            }
                        }, 50);
                    }
                }
            });
            
            column.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                console.log('Drop event:', {
                    draggedCard: draggedCard?.dataset.subjectId,
                    targetSemester: this.dataset.semester
                });
                
                if (draggedCard) {
                    const newSemester = this.dataset.semester;
                    const subjectId = draggedCard.dataset.subjectId;
                    const oldSemester = draggedCard.closest('.semester-column').dataset.semester;
                    const subjectList = this.querySelector('.subject-list');
                    
                    // Get the position where placeholder is
                    let insertBeforeCard = null;
                    if (currentPlaceholder && currentPlaceholder.nextElementSibling) {
                        insertBeforeCard = currentPlaceholder.nextElementSibling;
                    }
                    
                    // Remove placeholder
                    if (currentPlaceholder) {
                        currentPlaceholder.remove();
                        currentPlaceholder = null;
                    }
                    
                    console.log('Moving subject:', {
                        subjectId,
                        from: oldSemester,
                        to: newSemester,
                        insertBefore: insertBeforeCard?.dataset.subjectId
                    });
                    
                    if (newSemester !== oldSemester) {
                        // Moving to different semester
                        console.log('*** CALLING MODAL FUNCTION ***');
                        console.log('Subject:', subjectId, 'From:', oldSemester, 'To:', newSemester);
                        
                        // Store target position for later
                        window.tempMoveTargetCard = insertBeforeCard;
                        
                        // Show modal to optionally edit prerequisites
                        showMoveSubjectModal(draggedCard, this, newSemester, oldSemester);
                    } else {
                        // Reordering within same semester
                        console.log('Same semester, reordering...');
                        
                        if (insertBeforeCard && insertBeforeCard !== draggedCard) {
                            // Insert before the target card
                            subjectList.insertBefore(draggedCard, insertBeforeCard);
                        } else {
                            // Append to end
                            subjectList.appendChild(draggedCard);
                        }
                        
                        // Recalculate display_order
                        recalculateDisplayOrder(newSemester);
                    }
                }
            });
        });
    }
    
    // Move subject to new semester
    function moveSubjectToSemester(card, newColumn, newSemester) {
        console.log('=== moveSubjectToSemester CALLED ===');
        console.log('Card:', card.dataset.subjectId);
        console.log('New Semester:', newSemester);
        
        // Get the original column (before moving)
        const oldColumn = card.closest('.semester-column');
        console.log('Old Column:', oldColumn ? oldColumn.dataset.semester : 'null');
        
        // Get the original semester from the column data attribute (more reliable than badge)
        const oldSemester = oldColumn ? parseInt(oldColumn.dataset.semester) : null;
        console.log('Old Semester (from column):', oldSemester);
        
        // Save original position BEFORE moving the card
        const oldSubjectList = oldColumn ? oldColumn.querySelector('.subject-list') : null;
        const originalPosition = oldSubjectList ? Array.from(oldSubjectList.children).indexOf(card) : -1;
        console.log('Original Position:', originalPosition);
        
        const subjectList = newColumn.querySelector('.subject-list');
        const targetCard = window.tempMoveTargetCard;
        
        // Move the actual card to new location FIRST
        if (targetCard && targetCard !== card) {
            // Insert before the target card
            subjectList.insertBefore(card, targetCard);
        } else {
            // Append to end
            subjectList.appendChild(card);
        }
        
        console.log('Card moved to new location');
        
        // Clean up temp variable
        delete window.tempMoveTargetCard;
        
        // Update semester display
        const semesterBadge = card.querySelector('.semester-badge');
        if (semesterBadge) {
            semesterBadge.textContent = `Semestre ${newSemester}`;
        }
        
        // Add visual indicator for moved subject (blue style)
        card.classList.add('moved-subject');
        console.log('Added moved-subject class');
        
        // Create a ghost copy in the original location (AFTER moving the card)
        if (oldColumn && oldColumn !== newColumn && oldSemester && oldSubjectList) {
            const subjectCode = card.dataset.subjectId;
            
            console.log('=== CREATING GHOST ===');
            console.log('Subject Code:', subjectCode);
            console.log('Original Position:', originalPosition);
            console.log('Old Column !== New Column:', oldColumn !== newColumn);
            
            // Remove any existing ghost for this subject in the old location
            const existingGhost = oldColumn.querySelector(`[data-ghost-of="${subjectCode}"]`);
            if (existingGhost) {
                console.log('Removing existing ghost');
                existingGhost.remove();
            }
            
            // Clone the card to create a ghost (clone from the moved card)
            const ghostCard = card.cloneNode(true);
            
            // Remove all IDs to avoid conflicts
            ghostCard.removeAttribute('id');
            ghostCard.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));
            
            // Add ghost styling
            ghostCard.classList.add('moved-ghost');
            ghostCard.classList.remove('moved-subject'); // Remove the blue style from ghost
            ghostCard.classList.remove('dragging'); // Remove any drag state
            
            // Mark as ghost
            ghostCard.dataset.ghostOf = subjectCode;
            
            // Disable drag and drop on ghost
            ghostCard.draggable = false;
            ghostCard.removeAttribute('draggable');
            
            // Remove all event listeners by replacing with clone (this removes attached listeners)
            const cleanGhost = ghostCard.cloneNode(true);
            
            // Restore original semester badge in ghost
            const ghostBadge = cleanGhost.querySelector('.semester-badge');
            if (ghostBadge) {
                ghostBadge.textContent = `Semestre ${oldSemester}`;
                ghostBadge.style.opacity = '0.3';
            }
            
            // Insert the ghost at the original position
            if (originalPosition >= 0 && originalPosition < oldSubjectList.children.length) {
                oldSubjectList.insertBefore(cleanGhost, oldSubjectList.children[originalPosition]);
                console.log('✅ Ghost inserted at position:', originalPosition);
            } else {
                // If position not found, append to end
                oldSubjectList.appendChild(cleanGhost);
                console.log('✅ Ghost appended to end');
            }
            
            console.log('=== GHOST CREATED SUCCESSFULLY ===');
        } else {
            console.log('❌ Ghost NOT created. Conditions:');
            console.log('  oldColumn:', !!oldColumn);
            console.log('  oldColumn !== newColumn:', oldColumn !== newColumn);
            console.log('  oldSemester:', oldSemester);
            console.log('  oldSubjectList:', !!oldSubjectList);
        }
        
        // Recalculate display_order for the new semester WITHOUT tracking
        // (moving between semesters is already tracked as 'semester' change)
        recalculateDisplayOrder(newSemester, false);
        
        console.log('=== moveSubjectToSemester COMPLETED ===');
    }
    
    // Recalculate display_order for all subjects in a semester
    // trackChanges: if true, records changes (user action); if false, silent update (load/init)
    function recalculateDisplayOrder(semester, trackChanges = true) {
        const column = document.querySelector(`.semester-column[data-semester="${semester}"]`);
        if (!column) return;
        
        const cards = Array.from(column.querySelectorAll('.subject-card'));
        cards.forEach((card, index) => {
            const subjectCode = card.dataset.subjectId;
            const newOrder = index + 1;
            const currentOrder = card.dataset.displayOrder;
            
            // Update the display order in the dataset
            card.dataset.displayOrder = newOrder;
            
            // Only record the change if tracking is enabled AND order actually changed
            if (trackChanges && currentOrder && parseInt(currentOrder) !== newOrder) {
                recordSimulationChange(subjectCode, 'display_order', newOrder, parseInt(currentOrder));
            }
        });
    }
    
    // Record simulation changes
    // Record simulation changes with enhanced metadata
    function recordSimulationChange(subjectId, changeType, newValue, oldValue) {
        // Get subject name for better display
        const card = document.querySelector(`[data-subject-id="${subjectId}"]`);
        const subjectName = card ? card.querySelector('.subject-name')?.textContent.trim() : subjectId;
        
        // Remove existing change for this subject and type
        simulationChanges = simulationChanges.filter(change => 
            !(change.subject_code === subjectId && change.type === changeType)
        );
        
        // Add new change with timestamp and metadata
        simulationChanges.push({
            subject_code: subjectId,
            subject_name: subjectName,
            type: changeType,
            new_value: newValue,
            old_value: oldValue,
            timestamp: new Date().toISOString()
        });
        
        // Save to localStorage for persistence
        saveChangesToStorage();
        
        updateSimulationStatus();
    }
    
    // Update simulation status display
    function updateSimulationStatus() {
        // Count meaningful changes (exclude display_order)
        const meaningfulChanges = simulationChanges.filter(c => c.type !== 'display_order');
        const changesByType = {
            added: simulationChanges.filter(c => c.type === 'added').length,
            removed: simulationChanges.filter(c => c.type === 'removed').length,
            semester: simulationChanges.filter(c => c.type === 'semester').length,
            prerequisites: simulationChanges.filter(c => c.type === 'prerequisites').length,
        };
        
        const statusDiv = document.getElementById('simulation-status');
        if (statusDiv) {
            if (meaningfulChanges.length > 0) {
                let summary = [];
                if (changesByType.added > 0) summary.push(`${changesByType.added} agregada(s)`);
                if (changesByType.removed > 0) summary.push(`${changesByType.removed} eliminada(s)`);
                if (changesByType.semester > 0) summary.push(`${changesByType.semester} movida(s)`);
                if (changesByType.prerequisites > 0) summary.push(`${changesByType.prerequisites} prerreq. modificado(s)`);
                
                statusDiv.innerHTML = `
                    <div class="alert alert-warning border-warning shadow-sm">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Simulación Activa: ${meaningfulChanges.length} cambio(s) temporal(es)</strong>
                                </h6>
                                <small class="text-muted">${summary.join(' • ')}</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="showChangesModal()">
                                    <i class="fas fa-clipboard-list me-1"></i>
                                    Ver cambios
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="clearAllChanges()">
                                    <i class="fas fa-undo me-1"></i>
                                    Deshacer Todo
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                statusDiv.innerHTML = '';
            }
        }
    }
    
    // Update affected percentage display
    function updateAffectedPercentage(percentage) {
        const percentageElement = document.getElementById('affected-percentage');
        if (percentageElement) {
            percentageElement.textContent = percentage + '%';
            
            // Add color coding
            if (percentage > 50) {
                percentageElement.style.color = '#dc3545'; // Red
            } else if (percentage > 25) {
                percentageElement.style.color = '#fd7e14'; // Orange
            } else if (percentage > 0) {
                percentageElement.style.color = '#ffc107'; // Yellow
            } else {
                percentageElement.style.color = '#28a745'; // Green
            }
        }
    }
    
    // Add simulation controls
    
    // Analyze impact of changes
    window.analyzeImpact = function() {
        if (simulationChanges.length === 0) {
            // Reset percentage if no changes
            updateAffectedPercentage(0);
            return;
        }
        
        const loadingModal = showLoadingModal('Analizando impacto...');
        
        fetch('/simulation/analyze-impact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                changes: simulationChanges
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingModal(loadingModal);
            
            // Update the affected percentage display
            updateAffectedPercentage(data.affected_percentage);
            
            // Show detailed impact analysis
            showImpactAnalysis(data);
        })
        .catch(error => {
            hideLoadingModal(loadingModal);
            console.error('Error:', error);
            showAlertModal('Ocurrió un error al analizar el impacto de los cambios. Por favor intente nuevamente.', 'error', 'Error al Analizar');
        });
    };
    
    // Reset simulation to original state
    window.resetSimulation = function() {
        showConfirmModal(
            '¿Está seguro de que desea resetear todos los cambios? Esto eliminará todos los cambios temporales y recargará la página.',
            function() {
                // Clear all temporary changes
                simulationChanges = [];
                
                // Clear from localStorage
                clearStoredChanges();
                
                // Reload the page to restore original state
                window.location.reload();
            },
            'warning',
            'Resetear Simulación',
            'Sí, resetear',
            'Cancelar'
        );
    };
    
    /**
     * Discard temporary changes without reloading the page
     * Removes visual indicators and restores original state
     */
    window.discardChanges = function() {
        // Check if there are changes to discard
        const meaningfulChanges = simulationChanges.filter(c => c.type !== 'display_order');
        
        if (meaningfulChanges.length === 0) {
            showAlertModal('No hay cambios temporales para descartar.', 'info', 'Sin Cambios');
            return;
        }
        
        showConfirmModal(
            `¿Desea descartar ${meaningfulChanges.length} cambio(s) temporal(es)?<br><br>` +
            '<div class="text-start">' +
            '<small class="text-muted">' +
            'Esto eliminará:<br>' +
            `• ${simulationChanges.filter(c => c.type === 'added').length} materia(s) agregada(s)<br>` +
            `• ${simulationChanges.filter(c => c.type === 'removed').length} materia(s) marcada(s) para eliminar<br>` +
            `• ${simulationChanges.filter(c => c.type === 'semester').length} cambio(s) de semestre<br>` +
            `• ${simulationChanges.filter(c => c.type === 'prerequisites').length} cambio(s) de prerrequisitos<br>` +
            '</small>' +
            '</div><br>' +
            '<strong>Los cambios se descartarán sin recargar la página.</strong>',
            function() {
                discardChangesWithoutReload();
            },
            'warning',
            'Descartar Cambios Temporales',
            '<i class="fas fa-times-circle me-2"></i>Sí, descartar',
            'Cancelar'
        );
    };
    
    /**
     * Actually discard the changes without page reload
     */
    function discardChangesWithoutReload() {
        const changesCopy = [...simulationChanges];
        
        // Process each change in reverse to undo them
        changesCopy.forEach(change => {
            const card = document.querySelector(`[data-subject-id="${change.subject_code}"]`);
            
            switch(change.type) {
                case 'added':
                    // Remove added subjects
                    if (card) {
                        card.remove();
                    }
                    break;
                    
                case 'removed':
                    // Restore removed subjects (remove visual mark)
                    if (card) {
                        card.style.opacity = '1';
                        card.style.pointerEvents = 'auto';
                        card.dataset.removed = 'false';
                        card.classList.remove('removed-subject');
                    }
                    break;
                    
                case 'semester':
                    // Move back to original semester
                    if (card && originalCurriculum[change.subject_code]) {
                        const originalSemester = originalCurriculum[change.subject_code].semester;
                        const targetColumn = document.querySelector(`[data-semester="${originalSemester}"] .subject-list`);
                        if (targetColumn) {
                            targetColumn.appendChild(card);
                            card.classList.remove('moved-subject');
                            
                            // Update semester badge
                            const semesterBadge = card.querySelector('.semester-badge');
                            if (semesterBadge) {
                                semesterBadge.textContent = `Semestre ${originalSemester}`;
                            }
                        }
                    }
                    break;
                    
                case 'prerequisites':
                    // Restore original prerequisites
                    if (card && originalCurriculum[change.subject_code]) {
                        const originalPrereqs = originalCurriculum[change.subject_code].prerequisites || [];
                        card.dataset.prerequisites = originalPrereqs.join(',');
                        card.classList.remove('prereqs-changed');
                    }
                    break;
            }
        });
        
        // Clear changes array
        simulationChanges = [];
        
        // Clear from localStorage
        clearStoredChanges();
        
        // Update status display
        updateSimulationStatus();
        
        // Recalculate credits
        initializeTotalCredits();
        updateCreditsDisplay();
        
        // Update relationships
        updateUnlocksRelationships();
        
        // Show success message
        showAlertModal(
            'Todos los cambios temporales han sido descartados exitosamente.<br><br>' +
            '<small class="text-muted">La simulación ha vuelto a su estado original.</small>',
            'success',
            'Cambios Descartados'
        );
    }
    
    // Reset using original order from materias.txt
    function resetToOriginalOrder() {
        // Clear all semester columns first
        const semesterColumns = document.querySelectorAll('.semester-column');
        semesterColumns.forEach(column => {
            const subjectList = column.querySelector('.subject-list');
            subjectList.innerHTML = '';
        });
        
        // Place subjects in original order
        Object.keys(window.originalOrder).forEach(semester => {
            const semesterColumn = document.querySelector(`[data-semester="${semester}"]`);
            const subjectList = semesterColumn.querySelector('.subject-list');
            
            window.originalOrder[semester].forEach(subjectCode => {
                const card = document.querySelector(`[data-subject-id="${subjectCode}"]`);
                if (card) {
                    // Reset visual changes
                    card.classList.remove('moved');
                    
                    // Reset prerequisites if they were changed
                    if (originalCurriculum[subjectCode]) {
                        card.dataset.prerequisites = originalCurriculum[subjectCode].prerequisites.join(',');
                    }
                    
                    // Reset semester badge
                    const semesterBadge = card.querySelector('.semester-badge');
                    if (semesterBadge) {
                        semesterBadge.textContent = `Semestre ${semester}`;
                    }
                    
                    // Add to correct semester
                    subjectList.appendChild(card);
                }
            });
        });
        
        console.log('Reset to original order from materias.txt');
    }
    
    // Reset using stored positions (fallback)
    function resetToStoredPositions() {
        Object.keys(originalCurriculum).forEach(subjectId => {
            const original = originalCurriculum[subjectId];
            const card = document.querySelector(`[data-subject-id="${subjectId}"]`);
            
            if (card) {
                // Find original semester column
                const originalColumn = document.querySelector(`[data-semester="${original.semester}"]`);
                const subjectList = originalColumn.querySelector('.subject-list');
                
                // Move card back
                subjectList.appendChild(card);
                
                // Reset visual changes
                card.classList.remove('moved');
                
                // Reset semester badge
                const semesterBadge = card.querySelector('.semester-badge');
                if (semesterBadge) {
                    semesterBadge.textContent = `Semestre ${original.semester}`;
                }
                
                // Reset prerequisites if they were changed
                card.dataset.prerequisites = original.prerequisites.join(',');
            }
        });
        
        console.log('Reset to stored positions');
    }
    
    // Show impact analysis modal
    function showImpactAnalysis(data) {
        const modalHtml = `
            <div class="modal fade" id="impactModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Análisis de Impacto</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h5 class="card-title">${data.total_students}</h5>
                                            <p class="card-text">Total estudiantes</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h5 class="card-title text-warning">${data.affected_students}</h5>
                                            <p class="card-text">Estudiantes afectados</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h5 class="card-title text-danger">${data.students_with_delays}</h5>
                                            <p class="card-text">Con retrasos</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h5 class="card-title text-info">${data.affected_percentage}%</h5>
                                            <p class="card-text">Porcentaje afectado</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            ${data.details.length > 0 ? `
                                <h6>Detalles de estudiantes afectados:</h6>
                                <div class="accordion" id="studentsAccordion">
                                    ${data.details.map((detail, index) => `
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                                                    ${detail.student_name} (ID: ${detail.student_id}) - Semestre ${detail.current_semester}
                                                    <span class="badge bg-info ms-2">${detail.current_subjects.length} materias cursando</span>
                                                </button>
                                            </h2>
                                            <div id="collapse${index}" class="accordion-collapse collapse" data-bs-parent="#studentsAccordion">
                                                <div class="accordion-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Materias actuales:</h6>
                                                            <ul class="list-group list-group-flush">
                                                                ${detail.current_subjects.map(subject => `
                                                                    <li class="list-group-item">${subject}</li>
                                                                `).join('')}
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Problemas identificados:</h6>
                                                            <ul class="list-group list-group-flush">
                                                                ${detail.issues.map(issue => `
                                                                    <li class="list-group-item text-danger">${issue}</li>
                                                                `).join('')}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            ` : '<p class="text-success">No se detectaron estudiantes afectados por los cambios.</p>'}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('impactModal');
        if (existingModal) {
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.dispose();
            }
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal with proper event handling
        const modalElement = document.getElementById('impactModal');
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Add event listeners to ensure proper cleanup
        modalElement.addEventListener('hidden.bs.modal', function () {
            cleanupModal(modalElement);
        });
        
        modal.show();
    }
    
    // Show loading modal
    function showLoadingModal(message) {
        const modalHtml = `
            <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">${message}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
        modal.show();
        
        return modal;
    }
    
    // Hide loading modal
    function hideLoadingModal(modal) {
        modal.hide();
        setTimeout(() => {
            document.getElementById('loadingModal').remove();
        }, 300);
    }
    
    // Add click event listeners to subject cards using event delegation
    document.addEventListener('click', function(e) {
        console.log('CLICK EVENT TRIGGERED');
        console.log('Target:', e.target);
        console.log('Target tagName:', e.target.tagName);
        console.log('Target classes:', e.target.className);
        
        const subjectCard = e.target.closest('.subject-card');
        console.log('Found subject card:', subjectCard?.dataset?.subjectId || 'none');
        
        if (subjectCard) {
            // Stop ANY other event handlers from running
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
            
            const subjectId = subjectCard.dataset.subjectId;
            console.log(`PROCESSING CLICK on: ${subjectId}`);
            console.log(`Currently selected: ${selectedSubjectId || 'none'}`);
            
            // If clicking the same card (by ID), toggle off
            if (selectedSubjectId === subjectId) {
                console.log('DESELECTING - Same subject clicked');
                clearHighlights();
                selectedCard = null;
                selectedSubjectId = null;
                return;
            }
            
            // Highlight related subjects
            console.log('HIGHLIGHTING - New subject selected');
            highlightRelated(subjectCard);
            selectedCard = subjectCard;
            selectedSubjectId = subjectId;
        } else {
            // Click outside - clear highlights
            if (selectedSubjectId) {
                console.log('CLICK OUTSIDE - Clearing selection');
                clearHighlights();
                selectedCard = null;
                selectedSubjectId = null;
            }
        }
    });
    
    // Show enhanced changes modal with detailed information
    window.showChangesModal = function() {
        // Group changes by type for better organization
        const changesByType = {
            added: simulationChanges.filter(c => c.type === 'added'),
            removed: simulationChanges.filter(c => c.type === 'removed'),
            semester: simulationChanges.filter(c => c.type === 'semester'),
            prerequisites: simulationChanges.filter(c => c.type === 'prerequisites'),
            display_order: simulationChanges.filter(c => c.type === 'display_order'),
            other: simulationChanges.filter(c => !['added', 'removed', 'semester', 'prerequisites', 'display_order'].includes(c.type))
        };
        
        // Count total meaningful changes (exclude display_order for count)
        const meaningfulChanges = simulationChanges.filter(c => c.type !== 'display_order').length;
        
        const modalHtml = `
            <div class="modal fade" id="changesModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Auditoría de Cambios Temporales
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${meaningfulChanges > 0 ? `
                                <!-- Summary Cards -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card text-center border-success">
                                            <div class="card-body">
                                                <h3 class="text-success mb-0">${changesByType.added.length}</h3>
                                                <small class="text-muted">Materias Agregadas</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card text-center border-danger">
                                            <div class="card-body">
                                                <h3 class="text-danger mb-0">${changesByType.removed.length}</h3>
                                                <small class="text-muted">Materias Eliminadas</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card text-center border-primary">
                                            <div class="card-body">
                                                <h3 class="text-primary mb-0">${changesByType.semester.length}</h3>
                                                <small class="text-muted">Cambios de Semestre</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card text-center border-warning">
                                            <div class="card-body">
                                                <h3 class="text-warning mb-0">${changesByType.prerequisites.length}</h3>
                                                <small class="text-muted">Cambios de Prerrequisitos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Added Subjects -->
                                ${changesByType.added.length > 0 ? `
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-success">
                                            <i class="fas fa-plus-circle me-2"></i>
                                            Materias Agregadas (${changesByType.added.length})
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-success">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Semestre</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${changesByType.added.map((change, index) => {
                                                        const originalIndex = simulationChanges.indexOf(change);
                                                        const data = change.new_value || {};
                                                        return `
                                                        <tr>
                                                            <td><strong>${change.subject_code}</strong></td>
                                                            <td>${change.subject_name || data.name || 'N/A'}</td>
                                                            <td>Semestre ${data.semester || 'N/A'}</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeChange(${originalIndex})" title="Deshacer">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `}).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                <!-- Removed Subjects -->
                                ${changesByType.removed.length > 0 ? `
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-danger">
                                            <i class="fas fa-minus-circle me-2"></i>
                                            Materias Eliminadas (${changesByType.removed.length})
                                        </h6>
                                        <div class="alert alert-danger">
                                            <strong>⚠️ Atención:</strong> Estas materias serán eliminadas de la malla permanentemente.
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-danger">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Semestre Original</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${changesByType.removed.map((change, index) => {
                                                        const originalIndex = simulationChanges.indexOf(change);
                                                        return `
                                                        <tr>
                                                            <td><strong>${change.subject_code}</strong></td>
                                                            <td>${change.subject_name}</td>
                                                            <td>Semestre ${change.old_value || 'N/A'}</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-success" onclick="removeChange(${originalIndex})" title="Restaurar">
                                                                    <i class="fas fa-undo"></i> Restaurar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `}).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                <!-- Semester Changes -->
                                ${changesByType.semester.length > 0 ? `
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-primary">
                                            <i class="fas fa-exchange-alt me-2"></i>
                                            Cambios de Semestre (${changesByType.semester.length})
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Semestre Original</th>
                                                        <th>Nuevo Semestre</th>
                                                        <th>Cambio</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${changesByType.semester.map((change, index) => {
                                                        const originalIndex = simulationChanges.indexOf(change);
                                                        const diff = parseInt(change.new_value) - parseInt(change.old_value);
                                                        const diffText = diff > 0 ? `+${diff}` : diff;
                                                        const diffClass = diff > 0 ? 'text-success' : 'text-danger';
                                                        return `
                                                        <tr>
                                                            <td><strong>${change.subject_code}</strong></td>
                                                            <td>${change.subject_name}</td>
                                                            <td><span class="badge bg-secondary">${change.old_value}°</span></td>
                                                            <td><span class="badge bg-primary">${change.new_value}°</span></td>
                                                            <td>
                                                                <span class="${diffClass} fw-bold">${diffText}</span>
                                                                ${Math.abs(diff) > 2 ? '<i class="fas fa-exclamation-triangle text-warning ms-1" title="Cambio grande"></i>' : ''}
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeChange(${originalIndex})" title="Deshacer">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `}).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                <!-- Prerequisites Changes -->
                                ${changesByType.prerequisites.length > 0 ? `
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-warning">
                                            <i class="fas fa-project-diagram me-2"></i>
                                            Cambios de Prerrequisitos (${changesByType.prerequisites.length})
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-warning">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Nombre</th>
                                                        <th>Prerrequisitos Anteriores</th>
                                                        <th>Nuevos Prerrequisitos</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${changesByType.prerequisites.map((change, index) => {
                                                        const originalIndex = simulationChanges.indexOf(change);
                                                        const oldPrereqs = change.old_value ? change.old_value.split(',').filter(p => p.trim()) : [];
                                                        const newPrereqs = change.new_value ? change.new_value.split(',').filter(p => p.trim()) : [];
                                                        return `
                                                        <tr>
                                                            <td><strong>${change.subject_code}</strong></td>
                                                            <td>${change.subject_name}</td>
                                                            <td>
                                                                ${oldPrereqs.length > 0 ? 
                                                                    oldPrereqs.map(p => `<span class="badge bg-secondary me-1">${p}</span>`).join('') 
                                                                    : '<span class="text-muted">Ninguno</span>'}
                                                            </td>
                                                            <td>
                                                                ${newPrereqs.length > 0 ? 
                                                                    newPrereqs.map(p => `<span class="badge bg-primary me-1">${p}</span>`).join('') 
                                                                    : '<span class="text-muted">Ninguno</span>'}
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="removeChange(${originalIndex})" title="Deshacer">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `}).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                <!-- Reordering Summary (if many) -->
                                ${changesByType.display_order.length > 0 ? `
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Reordenamientos:</strong> ${changesByType.display_order.length} materia(s) han sido reordenadas dentro de sus semestres.
                                    </div>
                                ` : ''}
                            ` : '<div class="alert alert-secondary text-center"><i class="fas fa-info-circle me-2"></i>No hay cambios temporales registrados.</div>'}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Cerrar
                            </button>
                            ${meaningfulChanges > 0 ? `
                                <button type="button" class="btn btn-warning" onclick="exportChangesReport()">
                                    <i class="fas fa-file-export me-1"></i>
                                    Exportar Reporte
                                </button>
                                <button type="button" class="btn btn-danger" onclick="clearAllChanges()">
                                    <i class="fas fa-trash-alt me-1"></i>
                                    Deshacer Todos
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('changesModal');
        if (existingModal) {
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.dispose();
            }
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal with proper event handling
        const modalElement = document.getElementById('changesModal');
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Add event listeners to ensure proper cleanup
        modalElement.addEventListener('hidden.bs.modal', function () {
            cleanupModal(modalElement);
        });
        
        modal.show();
    };
    
    // Remove individual change
    window.removeChange = function(index) {
        const change = simulationChanges[index];
        
        // Revert visual changes
        if (change.type === 'semester') {
            const card = document.querySelector(`[data-subject-id="${change.subject_code}"]`);
            if (card) {
                // Find original semester column
                const originalColumn = document.querySelector(`[data-semester="${change.old_value}"]`);
                const subjectList = originalColumn.querySelector('.subject-list');
                
                // Move card back
                subjectList.appendChild(card);
                
                // Reset visual changes
                card.classList.remove('moved');
                
                // Reset semester badge
                const semesterBadge = card.querySelector('.semester-badge');
                if (semesterBadge) {
                    semesterBadge.textContent = `Semestre ${change.old_value}`;
                }
            }
        }
        
        // Remove change from array
        simulationChanges.splice(index, 1);
        updateSimulationStatus();
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('changesModal'));
        if (modal) {
            modal.hide();
        }
    };
    
    // Save simulation (placeholder for future implementation)
    window.saveSimulation = function() {
        if (simulationChanges.length === 0) {
            showAlertModal('No hay cambios pendientes para guardar.', 'info', 'Sin Cambios');
            return;
        }
        
        showConfirmModal(
            '¿Está seguro de que desea guardar estos cambios permanentemente?',
            function() {
                showAlertModal('Los cambios son temporales y se perderán al recargar la página.', 'info', 'Funcionalidad no implementada');
            },
            'primary',
            'Guardar Cambios',
            'Sí, guardar',
            'Cancelar'
        );
    };
    
    // Export changes report
    window.exportChangesReport = function() {
        // Group changes by type
        const changesByType = {
            added: simulationChanges.filter(c => c.type === 'added'),
            removed: simulationChanges.filter(c => c.type === 'removed'),
            semester: simulationChanges.filter(c => c.type === 'semester'),
            prerequisites: simulationChanges.filter(c => c.type === 'prerequisites'),
            display_order: simulationChanges.filter(c => c.type === 'display_order')
        };
        
        // Generate report text
        let report = '=== REPORTE DE CAMBIOS CURRICULARES ===\n\n';
        report += `Fecha: ${new Date().toLocaleString('es-ES')}\n`;
        report += `Total de cambios: ${simulationChanges.filter(c => c.type !== 'display_order').length}\n\n`;
        
        if (changesByType.added.length > 0) {
            report += '--- MATERIAS AGREGADAS ---\n';
            changesByType.added.forEach(c => {
                const data = c.new_value || {};
                report += `• ${c.subject_code} - ${c.subject_name} (Semestre ${data.semester})\n`;
            });
            report += '\n';
        }
        
        if (changesByType.removed.length > 0) {
            report += '--- MATERIAS ELIMINADAS ---\n';
            changesByType.removed.forEach(c => {
                report += `• ${c.subject_code} - ${c.subject_name}\n`;
            });
            report += '\n';
        }
        
        if (changesByType.semester.length > 0) {
            report += '--- CAMBIOS DE SEMESTRE ---\n';
            changesByType.semester.forEach(c => {
                const diff = parseInt(c.new_value) - parseInt(c.old_value);
                report += `• ${c.subject_code} - ${c.subject_name}: Semestre ${c.old_value} → ${c.new_value} (${diff > 0 ? '+' : ''}${diff})\n`;
            });
            report += '\n';
        }
        
        if (changesByType.prerequisites.length > 0) {
            report += '--- CAMBIOS DE PRERREQUISITOS ---\n';
            changesByType.prerequisites.forEach(c => {
                report += `• ${c.subject_code} - ${c.subject_name}\n`;
                report += `  Antes: ${c.old_value || 'Ninguno'}\n`;
                report += `  Ahora: ${c.new_value || 'Ninguno'}\n`;
            });
            report += '\n';
        }
        
        if (changesByType.display_order.length > 0) {
            report += `--- REORDENAMIENTOS ---\n`;
            report += `${changesByType.display_order.length} materia(s) reordenadas dentro de sus semestres.\n\n`;
        }
        
        // Create downloadable file
        const blob = new Blob([report], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `cambios_curriculares_${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showSuccessMessage('Reporte exportado exitosamente');
    };
    
    // Clear all changes
    window.clearAllChanges = function() {
        showConfirmModal(
            '¿Está seguro de que desea deshacer TODOS los cambios? Esta acción no se puede revertir.',
            function() {
                // Revert all visual changes
                simulationChanges.forEach(change => {
                    if (change.type === 'semester') {
                        const card = document.querySelector(`[data-subject-id="${change.subject_code}"]`);
                        if (card) {
                            const originalColumn = document.querySelector(`[data-semester="${change.old_value}"]`);
                            const subjectList = originalColumn?.querySelector('.subject-list');
                            if (subjectList) {
                                subjectList.appendChild(card);
                                card.classList.remove('moved-subject');
                                const semesterBadge = card.querySelector('.semester-badge');
                                if (semesterBadge) {
                                    semesterBadge.textContent = `Semestre ${change.old_value}`;
                                }
                                
                                // Remove the ghost copy
                                const ghost = document.querySelector(`[data-ghost-of="${change.subject_code}"]`);
                                if (ghost) {
                                    ghost.remove();
                                }
                            }
                        }
                    } else if (change.type === 'removed') {
                        // Restore removed card if stored somewhere
                        // Implementation depends on how you're handling removed cards
                    } else if (change.type === 'prerequisites') {
                        const card = document.querySelector(`[data-subject-id="${change.subject_code}"]`);
                        if (card) {
                            card.dataset.prerequisites = change.old_value;
                        }
                    }
                });
                
                // Clear array
                simulationChanges = [];
                
                // Clear from localStorage
                clearStoredChanges();
                
                updateSimulationStatus();
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('changesModal'));
                if (modal) {
                    modal.hide();
                }
                
                showSuccessMessage('Todos los cambios han sido revertidos');
                
                // Reload page to ensure clean state
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            },
            'danger',
            'Deshacer Cambios',
            'Sí, deshacer todo',
            'Cancelar'
        );
    };
    
    // Show modal when moving a subject to allow prerequisite editing
    function showMoveSubjectModal(card, newColumn, newSemester, oldSemester) {
        console.log('*** MODAL FUNCTION CALLED ***');
        console.log('Subject:', card.dataset.subjectId);
        console.log('From semester:', oldSemester, 'To semester:', newSemester);
        
        const subjectId = card.dataset.subjectId;
        const subjectName = card.querySelector('.subject-name').textContent;
        const currentPrereqs = card.dataset.prerequisites.split(',').filter(p => p.trim());
        
        const modalHtml = `
            <div class="modal fade" id="moveSubjectModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-arrows-alt me-2"></i>
                                Mover Materia
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-1"></i> Información del Cambio</h6>
                                        <p><strong>Materia:</strong> ${subjectName}</p>
                                        <p><strong>Código:</strong> ${subjectId}</p>
                                        <p><strong>Semestre actual:</strong> ${oldSemester}</p>
                                        <p><strong>Nuevo semestre:</strong> ${newSemester}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle me-1"></i> Prerrequisitos Actuales</h6>
                                        <div id="current-prereqs-display">
                                            ${currentPrereqs.length > 0 ? currentPrereqs.map(prereq => `
                                                <span class="badge bg-secondary me-1 mb-1">${prereq}</span>
                                            `).join('') : '<span class="text-muted">Sin prerrequisitos</span>'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="editPrerequisites">
                                        <label class="form-check-label" for="editPrerequisites">
                                            <i class="fas fa-edit me-1"></i>
                                            Modificar prerrequisitos (opcional)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3" id="prerequisiteEditor" style="display: none;">
                                <div class="col-12">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-list me-1"></i>
                                        Prerrequisitos:
                                    </label>
                                    <div id="movePrerequisitesContainer" class="border rounded p-3 mb-3" style="background: #f8f9fa; min-height: 60px;">
                                        <div id="moveSelectedPrerequisites" class="d-flex flex-wrap gap-2">
                                            <!-- Chips will appear here -->
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openMovePrerequisiteSelector('${subjectId}')">
                                        <i class="fas fa-plus me-1"></i>
                                        Modificar Prerrequisitos
                                    </button>
                                    <input type="hidden" id="move-prereqs-hidden" value="${currentPrereqs.join(',')}">
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-light">
                                        <h6><i class="fas fa-chart-line me-1"></i> Análisis de Impacto</h6>
                                        <p class="mb-0">Después de confirmar el cambio, se analizará automáticamente el impacto en los estudiantes.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="confirmMoveSubject('${subjectId}', '${newSemester}', '${oldSemester}')">
                                <i class="fas fa-check me-1"></i>
                                Confirmar Cambio
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('moveSubjectModal');
        if (existingModal) {
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.dispose();
            }
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Store references for later use
        window.tempMoveData = {
            card: card,
            newColumn: newColumn,
            newSemester: newSemester,
            oldSemester: oldSemester
        };
        
        // Add event listener for prerequisite editor toggle
        document.getElementById('editPrerequisites').addEventListener('change', function() {
            const editor = document.getElementById('prerequisiteEditor');
            if (this.checked) {
                editor.style.display = 'block';
                // Populate initial chips
                populateMovePrerequisiteChips(currentPrereqs);
            } else {
                editor.style.display = 'none';
            }
        });
        
        // Show modal with proper event handling
        const modalElement = document.getElementById('moveSubjectModal');
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Add event listeners to ensure proper cleanup
        modalElement.addEventListener('hidden.bs.modal', function () {
            cleanupModal(modalElement);
            
            // FIXED: Aggressive cleanup to restore scroll
            const forceCleanup = () => {
                // Remove Bootstrap modal state
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Ensure curriculum grid maintains proper overflow
                const curriculumGrid = document.querySelector('.curriculum-grid');
                if (curriculumGrid) {
                    curriculumGrid.style.setProperty('overflow-x', 'auto', 'important');
                    curriculumGrid.style.setProperty('overflow-y', 'visible', 'important');
                }
                
                // Remove any stuck modal backdrops
                document.querySelectorAll('.modal-backdrop, .modal-backdrop.fade, .modal-backdrop.show').forEach(backdrop => {
                    backdrop.remove();
                });
                
                // Force browser reflow
                void document.body.offsetHeight;
            };
            
            // Execute cleanup multiple times
            forceCleanup();
            setTimeout(forceCleanup, 50);
            setTimeout(forceCleanup, 150);
            setTimeout(forceCleanup, 300);
        });
        
        modal.show();
    }
    
    // Confirm subject move with optional prerequisite changes
    window.confirmMoveSubject = function(subjectId, newSemester, oldSemester) {
        const moveData = window.tempMoveData;
        const editPrereqs = document.getElementById('editPrerequisites').checked;
        
        // Store current scroll position before moving
        const curriculumGrid = document.querySelector('.curriculum-grid');
        const scrollLeft = curriculumGrid ? curriculumGrid.scrollLeft : 0;
        
        // Move the subject
        moveSubjectToSemester(moveData.card, moveData.newColumn, newSemester);
        recordSimulationChange(subjectId, 'semester', newSemester, oldSemester);
        
        // Handle prerequisite changes if enabled
        if (editPrereqs) {
            const newPrereqs = document.getElementById('move-prereqs-hidden').value
                .split(',')
                .map(p => p.trim())
                .filter(p => p);
            
            const oldPrereqs = moveData.card.dataset.prerequisites.split(',').filter(p => p.trim());
            
            // Check if there are prerequisite changes
            const hasPrereqChanges = JSON.stringify(newPrereqs.sort()) !== JSON.stringify(oldPrereqs.sort());
            
            if (hasPrereqChanges) {
                // Update card data
                moveData.card.dataset.prerequisites = newPrereqs.join(',');
                
                // Record prerequisite change
                recordSimulationChange(subjectId, 'prerequisites', newPrereqs.join(','), oldPrereqs.join(','));
                
                // Update unlocks relationships for all subjects
                updateUnlocksRelationships();
                
                console.log('Prerequisites changed:', {
                    subject: subjectId,
                    old: oldPrereqs,
                    new: newPrereqs
                });
            }
        }
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('moveSubjectModal'));
        if (modal) {
            modal.hide();
        }
        
        // FIXED: Aggressive scroll restoration and cleanup
        const forceScrollRestoration = () => {
            // Restore scroll position
            if (curriculumGrid) {
                curriculumGrid.scrollLeft = scrollLeft;
                
                // Force overflow styles with !important equivalent
                curriculumGrid.style.setProperty('overflow-x', 'auto', 'important');
                curriculumGrid.style.setProperty('overflow-y', 'visible', 'important');
                curriculumGrid.style.setProperty('display', 'flex', 'important');
            }
            
            // Aggressive body cleanup
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Remove all modal backdrops (sometimes multiple get stuck)
            const backdrops = document.querySelectorAll('.modal-backdrop, .modal-backdrop.fade, .modal-backdrop.show');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            
            // Force page reflow
            void document.body.offsetHeight;
            
            console.log('Scroll restored:', {
                scrollLeft: curriculumGrid?.scrollLeft,
                overflowX: curriculumGrid?.style.overflowX,
                bodyOverflow: document.body.style.overflow,
                backdropsRemoved: backdrops.length
            });
        };
        
        // Execute restoration multiple times to ensure it works
        setTimeout(forceScrollRestoration, 100);
        setTimeout(forceScrollRestoration, 300);
        setTimeout(forceScrollRestoration, 500);
        
        // Clean up temp data
        delete window.tempMoveData;
        
        // Auto-analyze impact after move
        setTimeout(() => analyzeImpact(), 500);
    };
    
    // Right-click context menu for editing prerequisites using event delegation
    document.addEventListener('contextmenu', function(e) {
        const subjectCard = e.target.closest('.subject-card');
        if (subjectCard) {
            e.preventDefault();
            showSubjectContextMenu(e, subjectCard);
        }
    });
    
    // Show context menu with options
    function showSubjectContextMenu(event, card) {
        // Remove any existing context menu
        const existingMenu = document.getElementById('subjectContextMenu');
        if (existingMenu) {
            existingMenu.remove();
        }
        
        const subjectId = card.dataset.subjectId;
        const subjectName = card.querySelector('.subject-name').textContent;
        
        // Create context menu with initial positioning
        const menuHtml = `
            <div id="subjectContextMenu" class="context-menu" style="position: fixed; top: ${event.clientY}px; left: ${event.clientX}px; z-index: 9999;">
                <div class="list-group shadow-lg" style="min-width: 200px;">
                    <button type="button" class="list-group-item list-group-item-action" onclick="openEditSubjectModal('${subjectId}')">
                        <i class="fas fa-edit me-2"></i>
                        Editar Materia
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="editSubjectPrerequisites('${subjectId}')">
                        <i class="fas fa-link me-2"></i>
                        Editar Prerrequisitos
                    </button>
                    <button type="button" class="list-group-item list-group-item-action list-group-item-danger" 
                            id="deleteSubjectBtn" 
                            onclick="startDeleteSubjectTimer('${subjectId}', '${subjectName}')" 
                            disabled>
                        <i class="fas fa-trash me-2"></i>
                        <span id="deleteSubjectText">Eliminar Materia (<span id="deleteTimer">5</span>s)</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', menuHtml);
        
        // Adjust position if menu would overflow screen edges
        const menu = document.getElementById('subjectContextMenu');
        const menuRect = menu.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        let left = event.clientX;
        let top = event.clientY;
        
        // Check if menu overflows right edge - flip to left
        if (left + menuRect.width > viewportWidth) {
            left = event.clientX - menuRect.width;
            // Ensure it doesn't go off the left edge either
            if (left < 0) {
                left = 0;
            }
        }
        
        // Check if menu overflows bottom edge - flip to top
        if (top + menuRect.height > viewportHeight) {
            top = event.clientY - menuRect.height;
            // Ensure it doesn't go off the top edge either
            if (top < 0) {
                top = 0;
            }
        }
        
        // Apply adjusted position
        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
        
        // Close menu when clicking outside
        setTimeout(() => {
            document.addEventListener('click', closeContextMenu);
        }, 100);
        
        // Start countdown for delete button
        let countdown = 5;
        const timer = setInterval(() => {
            countdown--;
            const timerSpan = document.getElementById('deleteTimer');
            if (timerSpan) {
                timerSpan.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(timer);
                const deleteBtn = document.getElementById('deleteSubjectBtn');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.classList.add('fw-bold');
                    document.getElementById('deleteSubjectText').textContent = 'Eliminar Materia';
                }
            }
        }, 1000);
        
        // Store timer to clear it if menu is closed early
        document.getElementById('subjectContextMenu').dataset.timerId = timer;
    }
    
    // Close context menu
    function closeContextMenu() {
        const menu = document.getElementById('subjectContextMenu');
        if (menu) {
            // Clear timer if exists
            const timerId = menu.dataset.timerId;
            if (timerId) {
                clearInterval(parseInt(timerId));
            }
            menu.remove();
        }
        document.removeEventListener('click', closeContextMenu);
    }
    
    // Edit subject prerequisites (old function renamed)
    window.editSubjectPrerequisites = function(subjectId) {
        closeContextMenu();
        const card = document.querySelector(`[data-subject-id="${subjectId}"]`);
        if (card) {
            showPrerequisiteEditor(card);
        }
    };
    
    // Open edit subject modal
    window.openEditSubjectModal = function(subjectId) {
        closeContextMenu();
        const card = document.querySelector(`[data-subject-id="${subjectId}"]`);
        if (!card) return;
        
        // Get current values from card
        const subjectName = card.querySelector('.subject-name').textContent;
        const subjectCode = subjectId;
        const credits = parseInt(card.querySelector('.subject-card-header .info-box:nth-child(1) .info-value').textContent);
        const classroomHours = parseInt(card.querySelector('.subject-card-header .info-box:nth-child(2) .info-value').textContent);
        const studentHours = parseInt(card.querySelector('.subject-card-header .info-box:nth-child(3) .info-value').textContent);
        const description = card.title || '';
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="editSubjectModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>
                                Editar Materia
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editSubjectForm">
                                <input type="hidden" id="edit_subject_code" value="${subjectCode}">
                                
                                <div class="mb-3">
                                    <label for="edit_subject_name" class="form-label">
                                        <i class="fas fa-book me-1"></i> Nombre de la Materia
                                    </label>
                                    <input type="text" class="form-control" id="edit_subject_name" 
                                           value="${subjectName}" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_subject_credits" class="form-label">
                                                <i class="fas fa-certificate me-1"></i> Créditos
                                            </label>
                                            <input type="number" class="form-control" id="edit_subject_credits" 
                                                   value="${credits}" min="1" max="20" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_classroom_hours" class="form-label">
                                                <i class="fas fa-chalkboard-teacher me-1"></i> Horas Presenciales
                                            </label>
                                            <input type="number" class="form-control" id="edit_classroom_hours" 
                                                   value="${classroomHours}" min="0" max="20" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="edit_student_hours" class="form-label">
                                                <i class="fas fa-user-clock me-1"></i> Horas Independientes
                                            </label>
                                            <input type="number" class="form-control" id="edit_student_hours" 
                                                   value="${studentHours}" min="0" max="30" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_subject_description" class="form-label">
                                        <i class="fas fa-align-left me-1"></i> Descripción (opcional)
                                    </label>
                                    <textarea class="form-control" id="edit_subject_description" 
                                              rows="3">${description}</textarea>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Nota:</strong> Estos cambios se guardarán temporalmente hasta que presiones "Guardar Malla".
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="saveSubjectEdits()">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('editSubjectModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
        modal.show();
        
        // Clean up when modal is closed
        document.getElementById('editSubjectModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    };
    
    // Save subject edits
    window.saveSubjectEdits = function() {
        const subjectCode = document.getElementById('edit_subject_code').value;
        const newName = document.getElementById('edit_subject_name').value.trim();
        const newCredits = parseInt(document.getElementById('edit_subject_credits').value);
        const newClassroomHours = parseInt(document.getElementById('edit_classroom_hours').value);
        const newStudentHours = parseInt(document.getElementById('edit_student_hours').value);
        const newDescription = document.getElementById('edit_subject_description').value.trim();
        
        // Validation
        if (!newName) {
            showAlertModal('El nombre de la materia es obligatorio.', 'error', 'Error de Validación');
            return;
        }
        
        if (newCredits < 1 || newCredits > 20) {
            showAlertModal('Los créditos deben estar entre 1 y 20.', 'error', 'Error de Validación');
            return;
        }
        
        if (newClassroomHours < 0 || newClassroomHours > 20) {
            showAlertModal('Las horas presenciales deben estar entre 0 y 20.', 'error', 'Error de Validación');
            return;
        }
        
        if (newStudentHours < 0 || newStudentHours > 30) {
            showAlertModal('Las horas independientes deben estar entre 0 y 30.', 'error', 'Error de Validación');
            return;
        }
        
        // Get the card
        const card = document.querySelector(`[data-subject-id="${subjectCode}"]`);
        if (!card) {
            showAlertModal('No se encontró la materia.', 'error', 'Error');
            return;
        }
        
        // Get old values for tracking changes
        const oldName = card.querySelector('.subject-name').textContent;
        const oldCredits = parseInt(card.querySelector('.subject-card-header .info-box:nth-child(1) .info-value').textContent);
        const oldClassroomHours = parseInt(card.querySelector('.subject-card-header .info-box:nth-child(2) .info-value').textContent);
        const oldStudentHours = parseInt(card.querySelector('.subject-card-header .info-box:nth-child(3) .info-value').textContent);
        const oldDescription = card.title || '';
        
        // Update card UI
        card.querySelector('.subject-name').textContent = newName;
        card.querySelector('.subject-card-header .info-box:nth-child(1) .info-value').textContent = newCredits;
        card.querySelector('.subject-card-header .info-box:nth-child(2) .info-value').textContent = newClassroomHours;
        card.querySelector('.subject-card-header .info-box:nth-child(3) .info-value').textContent = newStudentHours;
        card.title = newDescription;
        
        // Mark card as edited
        card.classList.add('edited-subject');
        
        // Record changes
        const changeData = {
            name: newName !== oldName ? newName : oldName,
            credits: newCredits !== oldCredits ? newCredits : oldCredits,
            classroom_hours: newClassroomHours !== oldClassroomHours ? newClassroomHours : oldClassroomHours,
            student_hours: newStudentHours !== oldStudentHours ? newStudentHours : oldStudentHours,
            description: newDescription !== oldDescription ? newDescription : oldDescription
        };
        
        recordSimulationChange(subjectCode, 'edit', changeData, {
            name: oldName,
            credits: oldCredits,
            classroom_hours: oldClassroomHours,
            student_hours: oldStudentHours,
            description: oldDescription
        });
        
        // Update credits display
        updateCreditsDisplay();
        updateSimulationStatus();
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('editSubjectModal'));
        modal.hide();
        
        showAlertModal('Materia editada correctamente. Los cambios se guardarán al presionar "Guardar Malla".', 'success', 'Materia Editada');
    };
    
    // Start delete subject process
    window.startDeleteSubjectTimer = function(subjectId, subjectName) {
        closeContextMenu();
        showDeleteSubjectWarning(subjectId, subjectName);
    };
    
    // Show delete warning modal
    function showDeleteSubjectWarning(subjectId, subjectName) {
        const modalHtml = `
            <div class="modal fade" id="deleteSubjectModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Advertencia: Eliminar Materia
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fas fa-user-graduate me-2"></i>
                                    Impacto en Estudiantes
                                </h6>
                                <p class="mb-0">
                                    Esta acción puede afectar a los estudiantes que ya cursaron o están cursando esta materia.
                                </p>
                            </div>
                            
                            <h6 class="mb-2">Materia a eliminar:</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="mb-1">${subjectName}</h5>
                                    <span class="badge bg-secondary">${subjectId}</span>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <p class="fw-bold text-danger mb-2">⚠️ Se sugiere tener precaución</p>
                                <ul class="small text-muted mb-0">
                                    <li>Al guardar la malla, será redirigido al apartado de <strong>Convalidación</strong></li>
                                    <li>Allí podrá <strong>mitigar el impacto</strong> en los estudiantes afectados</li>
                                    <li>Se mostrará cuántos estudiantes se ven afectados por este cambio</li>
                                    <li>Esta acción <strong>no se puede deshacer</strong> después de guardar</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteSubject('${subjectId}')">
                                <i class="fas fa-trash me-1"></i>
                                Eliminar Materia
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('deleteSubjectModal');
        if (existingModal) {
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.dispose();
            }
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modalElement = document.getElementById('deleteSubjectModal');
        const modal = new bootstrap.Modal(modalElement);
        
        modalElement.addEventListener('hidden.bs.modal', function () {
            cleanupModal(modalElement);
        });
        
        modal.show();
    }
    
    // Confirm delete subject
    window.confirmDeleteSubject = function(subjectId) {
        // Close modal
        const modalElement = document.getElementById('deleteSubjectModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        
        // Find subject card
        const card = document.querySelector(`[data-subject-id="${subjectId}"]`);
        if (card) {
            const subjectName = card.querySelector('.subject-name')?.textContent || subjectId;
            const subjectType = card.dataset.type;
            
            // Apply removal preview style
            applyRemovedStyle(card);
            
            // Register as removed change (parameters: subjectId, changeType, newValue, oldValue)
            recordSimulationChange(subjectId, 'removed', null, null);
            
            // If it's a leveling subject, dispatch event for real-time sync
            if (subjectType === 'nivelacion' || isLevelingSubject(subjectId, subjectType)) {
                const removeEvent = new CustomEvent('levelingSubjectRemoved', {
                    detail: {
                        code: subjectId,
                        name: subjectName
                    }
                });
                window.dispatchEvent(removeEvent);
            }
            
            // Update simulation status
            updateSimulationStatus();
            
            showSuccessMessage(`Materia marcada para eliminación. Recuerda guardar los cambios.`);
        }
    };
    
    // Show prerequisite editor
    function showPrerequisiteEditor(card) {
        const subjectId = card.dataset.subjectId;
        const subjectName = card.querySelector('.subject-name').textContent;
        const currentPrereqs = card.dataset.prerequisites.split(',').filter(p => p.trim());
        
        const modalHtml = `
            <div class="modal fade" id="prereqModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar Prerrequisitos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <h6 class="mb-3">Materia: <strong>${subjectName}</strong> <span class="badge bg-secondary">${subjectId}</span></h6>
                            
                            <div class="mt-3">
                                <label class="form-label fw-bold">Prerrequisitos actuales:</label>
                                <div id="current-prereqs-display" class="border rounded p-3 mb-3" style="background: #f8f9fa; min-height: 60px;">
                                    <div id="editPrerequisitesChips" class="d-flex flex-wrap gap-2">
                                        <!-- Chips will be populated here -->
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openPrerequisiteSelectorForEdit('${subjectId}')">
                                    <i class="fas fa-plus me-1"></i>
                                    Modificar Prerrequisitos
                                </button>
                                <input type="hidden" id="edit-prereqs-hidden" value="${currentPrereqs.join(',')}">
                            </div>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Haga clic en "Modificar Prerrequisitos" para cambiar las materias prerrequisito
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="updatePrerequisites('${subjectId}')">
                                <i class="fas fa-save me-1"></i>
                                Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('prereqModal');
        if (existingModal) {
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.dispose();
            }
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal with proper event handling
        const modalElement = document.getElementById('prereqModal');
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Add event listeners to ensure proper cleanup
        modalElement.addEventListener('hidden.bs.modal', function () {
            cleanupModal(modalElement);
        });
        
        // Populate initial chips
        populateEditPrerequisiteChips(currentPrereqs);
        
        modal.show();
    }
    
    // Populate chips in edit mode
    function populateEditPrerequisiteChips(prereqCodes) {
        const container = document.getElementById('editPrerequisitesChips');
        if (!container) return;
        
        container.innerHTML = prereqCodes.length === 0 
            ? '<span class="text-muted">Sin prerrequisitos</span>'
            : prereqCodes.map(code => {
                const card = document.querySelector(`[data-subject-id="${code.trim()}"]`);
                const name = card ? card.querySelector('.subject-name').textContent : code;
                return `
                    <span class="badge bg-primary d-inline-flex align-items-center gap-1 p-2">
                        <strong>${code}</strong>: ${name}
                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.7rem;" 
                                onclick="removeEditPrerequisiteChip('${code}')" aria-label="Remove"></button>
                    </span>
                `;
            }).join('');
    }
    
    // Remove chip in edit mode
    window.removeEditPrerequisiteChip = function(code) {
        const hiddenInput = document.getElementById('edit-prereqs-hidden');
        const current = hiddenInput.value.split(',').filter(p => p.trim() && p.trim() !== code);
        hiddenInput.value = current.join(',');
        populateEditPrerequisiteChips(current);
    };
    
    // Open prerequisite selector for editing
    window.openPrerequisiteSelectorForEdit = function(subjectId) {
        const existingSubjects = Array.from(document.querySelectorAll('.subject-card')).map(card => ({
            code: card.dataset.subjectId,
            name: card.querySelector('.subject-name').textContent,
            semester: card.closest('.semester-column').dataset.semester
        }));

        // Get currently selected prerequisites from hidden field
        const currentPrereqs = document.getElementById('edit-prereqs-hidden').value
            .split(',').map(p => p.trim()).filter(p => p);

        const helperHtml = `
            <div class="modal fade" id="editPrerequisiteSelectorModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-search me-2"></i>
                                Seleccionar Prerrequisitos
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="editPrerequisiteSearch" 
                                       placeholder="🔍 Buscar por código o nombre de materia..." 
                                       onkeyup="filterEditPrerequisiteOptions()">
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;" id="editPrerequisiteList">
                                ${existingSubjects.filter(s => s.code !== subjectId).map(subject => `
                                    <div class="prerequisite-option mb-2" data-code="${subject.code}" data-name="${subject.name}">
                                        <div class="prerequisite-card p-3 border rounded ${currentPrereqs.includes(subject.code) ? 'selected' : ''}" 
                                             style="cursor: pointer; transition: all 0.3s ease;"
                                             data-code="${subject.code}"
                                             data-name="${subject.name}"
                                             onclick="toggleEditPrerequisiteSelection(this)">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <strong class="text-primary d-block">${subject.code}</strong>
                                                    <div class="text-dark">${subject.name}</div>
                                                </div>
                                                <div class="d-flex flex-column align-items-end">
                                                    <span class="badge bg-secondary mb-2">Sem. ${subject.semester}</span>
                                                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; opacity: ${currentPrereqs.includes(subject.code) ? '1' : '0'};"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="mt-3 text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Haga clic en una materia para seleccionarla/deseleccionarla como prerrequisito
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="applyEditSelectedPrerequisites()">
                                <i class="fas fa-check me-1"></i>
                                Confirmar Selección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', helperHtml);
        const modal = new bootstrap.Modal(document.getElementById('editPrerequisiteSelectorModal'));
        
        document.getElementById('editPrerequisiteSelectorModal').addEventListener('hidden.bs.modal', function() {
            cleanupModal(this);
        });
        
        modal.show();
    };

    // Toggle prerequisite selection in edit mode (visual cards)
    window.toggleEditPrerequisiteSelection = function(cardElement) {
        const icon = cardElement.querySelector('.fa-check-circle');
        
        if (cardElement.classList.contains('selected')) {
            // Deselect
            cardElement.classList.remove('selected');
            icon.style.opacity = '0';
        } else {
            // Select
            cardElement.classList.add('selected');
            icon.style.opacity = '1';
        }
    };

    // Filter prerequisite options in edit mode
    window.filterEditPrerequisiteOptions = function() {
        const searchTerm = document.getElementById('editPrerequisiteSearch').value.toLowerCase();
        const options = document.querySelectorAll('#editPrerequisiteList .prerequisite-option');
        
        options.forEach(option => {
            const code = option.dataset.code.toLowerCase();
            const name = option.dataset.name.toLowerCase();
            const matches = code.includes(searchTerm) || name.includes(searchTerm);
            option.style.display = matches ? 'block' : 'none';
        });
    };

    // Apply selected prerequisites in edit mode
    window.applyEditSelectedPrerequisites = function() {
        const selectedCards = Array.from(document.querySelectorAll('#editPrerequisiteList .prerequisite-card.selected'));
        
        // Store codes in hidden input
        const codes = selectedCards.map(card => card.dataset.code);
        document.getElementById('edit-prereqs-hidden').value = codes.join(',');
        
        // Update visual display with chips
        populateEditPrerequisiteChips(codes);
        
        bootstrap.Modal.getInstance(document.getElementById('editPrerequisiteSelectorModal')).hide();
    };

    // ===== FUNCTIONS FOR MOVE MODAL PREREQUISITE SELECTOR =====
    
    // Populate chips in move modal
    function populateMovePrerequisiteChips(prereqCodes) {
        const container = document.getElementById('moveSelectedPrerequisites');
        if (!container) return;
        
        container.innerHTML = prereqCodes.length === 0 
            ? '<span class="text-muted">Sin prerrequisitos</span>'
            : prereqCodes.map(code => {
                const card = document.querySelector(`[data-subject-id="${code.trim()}"]`);
                const name = card ? card.querySelector('.subject-name').textContent : code;
                return `
                    <span class="badge bg-primary d-inline-flex align-items-center gap-1 p-2">
                        <strong>${code}</strong>: ${name}
                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.7rem;" 
                                onclick="removeMovePrerequisiteChip('${code}')" aria-label="Remove"></button>
                    </span>
                `;
            }).join('');
    }
    
    // Remove chip in move modal
    window.removeMovePrerequisiteChip = function(code) {
        const hiddenInput = document.getElementById('move-prereqs-hidden');
        const current = hiddenInput.value.split(',').filter(p => p.trim() && p.trim() !== code);
        hiddenInput.value = current.join(',');
        populateMovePrerequisiteChips(current);
    };
    
    // Open prerequisite selector for move modal
    window.openMovePrerequisiteSelector = function(subjectId) {
        const existingSubjects = Array.from(document.querySelectorAll('.subject-card')).map(card => ({
            code: card.dataset.subjectId,
            name: card.querySelector('.subject-name').textContent,
            semester: card.closest('.semester-column').dataset.semester
        }));

        // Get currently selected prerequisites from hidden field
        const currentPrereqs = document.getElementById('move-prereqs-hidden').value
            .split(',').map(p => p.trim()).filter(p => p);

        const helperHtml = `
            <div class="modal fade" id="movePrerequisiteSelectorModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-search me-2"></i>
                                Seleccionar Prerrequisitos
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="movePrerequisiteSearch" 
                                       placeholder="🔍 Buscar por código o nombre de materia..." 
                                       onkeyup="filterMovePrerequisiteOptions()">
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;" id="movePrerequisiteList">
                                ${existingSubjects.filter(s => s.code !== subjectId).map(subject => `
                                    <div class="prerequisite-option mb-2" data-code="${subject.code}" data-name="${subject.name}">
                                        <div class="prerequisite-card p-3 border rounded ${currentPrereqs.includes(subject.code) ? 'selected' : ''}" 
                                             style="cursor: pointer; transition: all 0.3s ease;"
                                             data-code="${subject.code}"
                                             data-name="${subject.name}"
                                             onclick="toggleMovePrerequisiteSelection(this)">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <strong class="text-primary d-block">${subject.code}</strong>
                                                    <div class="text-dark">${subject.name}</div>
                                                </div>
                                                <div class="d-flex flex-column align-items-end">
                                                    <span class="badge bg-secondary mb-2">Sem. ${subject.semester}</span>
                                                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; opacity: ${currentPrereqs.includes(subject.code) ? '1' : '0'};"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="mt-3 text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Haga clic en una materia para seleccionarla/deseleccionarla como prerrequisito
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="applyMoveSelectedPrerequisites()">
                                <i class="fas fa-check me-1"></i>
                                Confirmar Selección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', helperHtml);
        const modal = new bootstrap.Modal(document.getElementById('movePrerequisiteSelectorModal'));
        
        document.getElementById('movePrerequisiteSelectorModal').addEventListener('hidden.bs.modal', function() {
            cleanupModal(this);
        });
        
        modal.show();
    };

    // Toggle prerequisite selection in move modal
    window.toggleMovePrerequisiteSelection = function(cardElement) {
        const icon = cardElement.querySelector('.fa-check-circle');
        
        if (cardElement.classList.contains('selected')) {
            // Deselect
            cardElement.classList.remove('selected');
            icon.style.opacity = '0';
        } else {
            // Select
            cardElement.classList.add('selected');
            icon.style.opacity = '1';
        }
    };

    // Filter prerequisite options in move modal
    window.filterMovePrerequisiteOptions = function() {
        const searchTerm = document.getElementById('movePrerequisiteSearch').value.toLowerCase();
        const options = document.querySelectorAll('#movePrerequisiteList .prerequisite-option');
        
        options.forEach(option => {
            const code = option.dataset.code.toLowerCase();
            const name = option.dataset.name.toLowerCase();
            const matches = code.includes(searchTerm) || name.includes(searchTerm);
            option.style.display = matches ? 'block' : 'none';
        });
    };

    // Apply selected prerequisites in move modal
    window.applyMoveSelectedPrerequisites = function() {
        const selectedCards = Array.from(document.querySelectorAll('#movePrerequisiteList .prerequisite-card.selected'));
        
        // Store codes in hidden input
        const codes = selectedCards.map(card => card.dataset.code);
        document.getElementById('move-prereqs-hidden').value = codes.join(',');
        
        // Update visual display with chips
        populateMovePrerequisiteChips(codes);
        
        bootstrap.Modal.getInstance(document.getElementById('movePrerequisiteSelectorModal')).hide();
    };

    // ===== END MOVE MODAL FUNCTIONS =====

    // Update prerequisites
    window.updatePrerequisites = function(subjectId) {
        const newPrereqs = document.getElementById('edit-prereqs-hidden').value
            .split(',')
            .map(p => p.trim())
            .filter(p => p);
        
        const card = document.querySelector(`[data-subject-id="${subjectId}"]`);
        const oldPrereqs = card.dataset.prerequisites.split(',').filter(p => p.trim());
        
        // Check if there are changes
        const hasChanges = JSON.stringify(newPrereqs.sort()) !== JSON.stringify(oldPrereqs.sort());
        
        if (hasChanges) {
            // Update card data
            card.dataset.prerequisites = newPrereqs.join(',');
            
            // Record change
            recordSimulationChange(subjectId, 'prerequisites', newPrereqs.join(','), oldPrereqs.join(','));
            
            // Update unlocks relationships for all subjects
            updateUnlocksRelationships();
            
            // If card was selected, update highlights
            if (selectedCard === card) {
                highlightRelated(card);
            }
        }
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('prereqModal'));
        if (modal) {
            modal.hide();
        }
    };
    
    // Open convalidation system
    window.openConvalidation = function() {
        window.location.href = '/convalidation';
    };

    // Add new subject functionality - Update the global function
    window.addNewSubject = function() {
        console.log('addNewSubject called - updated version');
        showAddSubjectModal();
    };

    // Export modified curriculum - Update the global function
    window.exportModifiedCurriculum = function() {
        console.log('exportModifiedCurriculum called - updated version');
        const modifiedCurriculum = getCurrentCurriculumState();
        showExportModal(modifiedCurriculum);
    };

    // Show modal to add new subject
    function showAddSubjectModal() {
        const modalHtml = `
            <div class="modal fade" id="addSubjectModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-plus me-2"></i>
                                Agregar Nueva Materia
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addSubjectForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subjectCode" class="form-label">Código de la Materia *</label>
                                            <input type="text" class="form-control" id="subjectCode" required 
                                                   placeholder="Ej: MAT101" maxlength="10">
                                            <div class="form-text">Debe ser único</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subjectSemester" class="form-label">Semestre *</label>
                                            <select class="form-select" id="subjectSemester" required>
                                                <option value="">Seleccionar semestre</option>
                                                <option value="1">1° Semestre</option>
                                                <option value="2">2° Semestre</option>
                                                <option value="3">3° Semestre</option>
                                                <option value="4">4° Semestre</option>
                                                <option value="5">5° Semestre</option>
                                                <option value="6">6° Semestre</option>
                                                <option value="7">7° Semestre</option>
                                                <option value="8">8° Semestre</option>
                                                <option value="9">9° Semestre</option>
                                                <option value="10">10° Semestre</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="subjectName" class="form-label">Nombre de la Materia *</label>
                                    <input type="text" class="form-control" id="subjectName" required 
                                           placeholder="Ej: Matemáticas Discretas" maxlength="100">
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="subjectCredits" class="form-label">Créditos *</label>
                                            <input type="number" class="form-control" id="subjectCredits" required 
                                                   min="1" max="10" value="3">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="subjectClassroomHours" class="form-label">Horas Presenciales *</label>
                                            <input type="number" class="form-control" id="subjectClassroomHours" required 
                                                   min="1" max="20" value="3">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="subjectStudentHours" class="form-label">Horas Estudiante *</label>
                                            <input type="number" class="form-control" id="subjectStudentHours" required 
                                                   min="1" max="20" value="6">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subjectType" class="form-label">Tipo de Materia *</label>
                                            <select class="form-select" id="subjectType" required>
                                                <option value="">Seleccionar tipo</option>
                                                <option value="fundamental">Fundamental</option>
                                                <option value="profesional" selected>Profesional</option>
                                                <option value="optativa_fundamentacion">Optativa Fundamentación</option>
                                                <option value="optativa_profesional">Optativa Profesional</option>
                                                <option value="libre_eleccion">Libre Elección</option>
                                                <option value="nivelacion">Nivelación</option>
                                                <option value="trabajo_grado">Trabajo de Grado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subjectRequired" class="form-label">Tipo de Oferta *</label>
                                            <select class="form-select" id="subjectRequired" required>
                                                <option value="true" selected>Obligatoria</option>
                                                <option value="false">Optativa</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="subjectPrerequisites" class="form-label">Prerrequisitos (opcional)</label>
                                    <div id="prerequisitesContainer" class="border rounded p-2 mb-2" style="min-height: 60px; background: #f8f9fa;">
                                        <div id="selectedPrerequisites" class="d-flex flex-wrap gap-2">
                                            <!-- Selected prerequisites will appear here as chips -->
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="showPrerequisiteSelector()">
                                        <i class="fas fa-plus me-1"></i>
                                        Agregar Prerrequisitos
                                    </button>
                                    <input type="hidden" id="subjectPrerequisites" value="">
                                </div>
                                <div class="mb-3">
                                    <label for="subjectDescription" class="form-label">Descripción (opcional)</label>
                                    <textarea class="form-control" id="subjectDescription" rows="3" 
                                              placeholder="Descripción breve de la materia"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" onclick="createNewSubject()">
                                <i class="fas fa-plus me-1"></i>
                                Agregar Materia
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('addSubjectModal'));
        
        // Clean up when modal is hidden
        document.getElementById('addSubjectModal').addEventListener('hidden.bs.modal', function() {
            cleanupModal(this);
        });
        
        modal.show();
    }

    // Show prerequisite helper modal
    // Global variable to store prerequisites
    window.selectedPrerequisitesData = [];

    // Show prerequisite selector modal
    window.showPrerequisiteSelector = function() {
        const existingSubjects = Array.from(document.querySelectorAll('.subject-card')).map(card => ({
            code: card.dataset.subjectId,
            name: card.querySelector('.subject-name').textContent,
            semester: card.closest('.semester-column').dataset.semester
        }));

        // Get currently selected prerequisites
        const currentPrereqs = document.getElementById('subjectPrerequisites').value
            .split(',').map(p => p.trim()).filter(p => p);

        const helperHtml = `
            <div class="modal fade" id="prerequisiteSelectorModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-search me-2"></i>
                                Seleccionar Prerrequisitos
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="prerequisiteSearch" 
                                       placeholder="🔍 Buscar por código o nombre de materia..." 
                                       onkeyup="filterPrerequisiteOptions()">
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;" id="prerequisiteList">
                                ${existingSubjects.map(subject => `
                                    <div class="prerequisite-option mb-2" data-code="${subject.code}" data-name="${subject.name}">
                                        <div class="prerequisite-card p-3 border rounded ${currentPrereqs.includes(subject.code) ? 'selected' : ''}" 
                                             style="cursor: pointer; transition: all 0.3s ease;"
                                             data-code="${subject.code}"
                                             data-name="${subject.name}"
                                             onclick="togglePrerequisiteSelection(this)">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <strong class="text-primary d-block">${subject.code}</strong>
                                                    <div class="text-dark">${subject.name}</div>
                                                </div>
                                                <div class="d-flex flex-column align-items-end">
                                                    <span class="badge bg-secondary mb-2">Sem. ${subject.semester}</span>
                                                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem; opacity: ${currentPrereqs.includes(subject.code) ? '1' : '0'};"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            <div class="mt-3 text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Haga clic en una materia para seleccionarla/deseleccionarla como prerrequisito
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="applySelectedPrerequisitesNew()">
                                <i class="fas fa-check me-1"></i>
                                Confirmar Selección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', helperHtml);
        const modal = new bootstrap.Modal(document.getElementById('prerequisiteSelectorModal'));
        
        document.getElementById('prerequisiteSelectorModal').addEventListener('hidden.bs.modal', function() {
            cleanupModal(this);
        });
        
        modal.show();
    };

    // Toggle prerequisite selection (visual cards)
    window.togglePrerequisiteSelection = function(cardElement) {
        const icon = cardElement.querySelector('.fa-check-circle');
        
        if (cardElement.classList.contains('selected')) {
            // Deselect
            cardElement.classList.remove('selected');
            icon.style.opacity = '0';
        } else {
            // Select
            cardElement.classList.add('selected');
            icon.style.opacity = '1';
        }
    };

    // Filter prerequisite options
    window.filterPrerequisiteOptions = function() {
        const searchTerm = document.getElementById('prerequisiteSearch').value.toLowerCase();
        const options = document.querySelectorAll('.prerequisite-option');
        
        options.forEach(option => {
            const code = option.dataset.code.toLowerCase();
            const name = option.dataset.name.toLowerCase();
            const matches = code.includes(searchTerm) || name.includes(searchTerm);
            option.style.display = matches ? 'block' : 'none';
        });
    };

    // Apply selected prerequisites (new version)
    window.applySelectedPrerequisitesNew = function() {
        const selectedCards = Array.from(document.querySelectorAll('#prerequisiteList .prerequisite-card.selected'));
        
        // Store codes in hidden input
        const codes = selectedCards.map(card => card.dataset.code);
        document.getElementById('subjectPrerequisites').value = codes.join(',');
        
        // Update visual display with chips
        const container = document.getElementById('selectedPrerequisites');
        container.innerHTML = selectedCards.map(card => {
            const code = card.dataset.code;
            const name = card.dataset.name;
            return `
                <span class="badge bg-primary d-inline-flex align-items-center gap-1 p-2">
                    <strong>${code}</strong>: ${name}
                    <button type="button" class="btn-close btn-close-white" style="font-size: 0.7rem;" 
                            onclick="removePrerequisiteChip('${code}')" aria-label="Remove"></button>
                </span>
            `;
        }).join('');
        
        bootstrap.Modal.getInstance(document.getElementById('prerequisiteSelectorModal')).hide();
    };

    // Remove prerequisite chip
    window.removePrerequisiteChip = function(code) {
        const current = document.getElementById('subjectPrerequisites').value
            .split(',').filter(p => p.trim() && p.trim() !== code);
        document.getElementById('subjectPrerequisites').value = current.join(',');
        
        // Update visual display
        const chip = event.target.closest('.badge');
        if (chip) chip.remove();
    };

    // Keep old function for compatibility
    function showPrerequisiteHelper() {
        showPrerequisiteSelector();
    }

    // Create new subject
    window.createNewSubject = function() {
        const code = document.getElementById('subjectCode').value.trim().toUpperCase();
        const name = document.getElementById('subjectName').value.trim();
        const semester = document.getElementById('subjectSemester').value;
        const prerequisites = document.getElementById('subjectPrerequisites').value.trim();
        const description = document.getElementById('subjectDescription').value.trim();
        const credits = parseInt(document.getElementById('subjectCredits').value) || 3;
        const classroomHours = parseInt(document.getElementById('subjectClassroomHours').value) || 3;
        const studentHours = parseInt(document.getElementById('subjectStudentHours').value) || 6;
        const type = document.getElementById('subjectType').value || 'profesional';
        const isRequired = document.getElementById('subjectRequired').value === 'true';

        // Validation
        if (!code || !name || !semester || !type) {
            showAlertModal('Por favor complete todos los campos obligatorios: Código, Nombre, Semestre y Tipo.', 'warning', 'Campos Incompletos');
            return;
        }

        // Check if code already exists
        if (document.querySelector(`[data-subject-id="${code}"]`)) {
            showAlertModal(`Ya existe una materia con el código "${code}". Por favor use un código diferente.`, 'warning', 'Código Duplicado');
            return;
        }

        // Process prerequisites - convert comma-separated codes to array
        const prerequisiteArray = prerequisites 
            ? prerequisites.split(',').map(p => p.trim().toUpperCase()).filter(p => p) 
            : [];

        // Validate that prerequisite codes exist (optional validation)
        const invalidPrereqs = prerequisiteArray.filter(prereqCode => {
            return !document.querySelector(`[data-subject-id="${prereqCode}"]`);
        });

        if (invalidPrereqs.length > 0) {
            showConfirmModal(
                `Los siguientes prerrequisitos no existen en la malla actual: ${invalidPrereqs.join(', ')}\n\n` +
                `¿Desea continuar agregando la materia de todas formas?`,
                function() {
                    continueAddingSubject();
                },
                'warning',
                'Prerrequisitos no Encontrados',
                'Sí, continuar',
                'Cancelar'
            );
            return;
        }

        continueAddingSubject();
    };

    function continueAddingSubject() {
        const code = document.getElementById('subjectCode').value.toUpperCase();
        const name = document.getElementById('subjectName').value;
        const semester = parseInt(document.getElementById('subjectSemester').value);
        const prerequisites = document.getElementById('subjectPrerequisites').value.toUpperCase();
        const description = document.getElementById('subjectDescription').value;
        const credits = parseInt(document.getElementById('subjectCredits').value) || 0;
        const classroomHours = parseInt(document.getElementById('subjectClassroomHours').value) || 0;
        const studentHours = parseInt(document.getElementById('subjectStudentHours').value) || 0;
        const type = document.getElementById('subjectType').value || 'profesional';
        const isRequired = document.getElementById('subjectRequired').value === 'true';
        
        const prerequisiteArray = prerequisites 
            ? prerequisites.split(',').map(p => p.trim().toUpperCase()).filter(p => p) 
            : [];

        // Create the new subject card
        const newSubjectCard = createSubjectCard(code, name, semester, prerequisiteArray.join(','), description, credits, classroomHours, studentHours, type, isRequired);
        
        // Add to the appropriate semester column
        const semesterColumn = document.querySelector(`[data-semester="${semester}"] .subject-list`);
        if (!semesterColumn) {
            showAlertModal(`No se encontró la columna del semestre ${semester}. Verifique que el semestre exista.`, 'error', 'Error de Semestre');
            return;
        }
        
        // Always add new subjects at the end of the semester
        semesterColumn.appendChild(newSubjectCard);
        
        // Recalculate display_order for all cards in this semester (silent - no tracking)
        recalculateDisplayOrder(semester, false);
        
        // Update credits (check if it's a leveling subject)
        const creditsNum = parseInt(credits) || 0;
        totalCredits += creditsNum;
        
        // Only add to career credits if it's NOT a leveling subject
        const isLeveling = isLevelingSubject(code, type);
        if (!isLeveling) {
            careerCredits += creditsNum;
        }
        
        updateCreditsDisplay();

        // Update drag and drop functionality
        enableDragAndDropForCard(newSubjectCard);

        // Update unlocks relationships for existing subjects
        updateUnlocksRelationships();

        // Record as simulation change with ALL necessary data for recreation
        recordSimulationChange(code, 'added', {
            name: name,
            semester: semester,
            prerequisites: prerequisiteArray,
            description: description,
            credits: creditsNum,
            classroomHours: classroomHours,
            studentHours: studentHours,
            type: type,
            isRequired: isRequired
        }, null);

        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('addSubjectModal')).hide();

        // Show success message
        showSuccessMessage(`Materia "${name}" (${code}) agregada exitosamente al semestre ${semester}`);
        
        console.log(`New subject added: ${code} - ${name}, Prerequisites: [${prerequisiteArray.join(', ')}]`);
    };

    // Create subject card HTML
    function createSubjectCard(code, name, semester, prerequisites, description, credits = 3, classroomHours = 3, studentHours = 6, type = 'profesional', isRequired = true) {
        const card = document.createElement('div');
        
        // Check if this is a leveling subject (from database or by type)
        const isLeveling = isLevelingSubject(code, type);
        const actualType = isLeveling ? 'nivelacion' : type;
        
        card.className = `subject-card ${actualType} added-subject`;
        card.dataset.subjectId = code;
        card.dataset.type = actualType;
        card.dataset.prerequisites = prerequisites;
        card.dataset.unlocks = '';
        card.title = description || name;

        const iconSvg = isRequired 
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';

        card.innerHTML = `
            <div class="subject-card-header">
                <div class="info-box">
                    <span class="info-value">${credits}</span>
                </div>
                <div class="info-box">
                    <span class="info-value">${classroomHours}</span>
                </div>
                <div class="info-box">
                    <span class="info-value">${studentHours}</span>
                </div>
            </div>
            <div class="subject-card-body">
                <div class="subject-name">${name}</div>
            </div>
            <div class="subject-card-footer">
                <div class="subject-code">${code}</div>
                <div class="subject-icon ${isRequired ? 'required' : 'elective'}">
                    ${iconSvg}
                </div>
            </div>
        `;

        return card;
    }

    // Enable drag and drop for a single card
    function enableDragAndDropForCard(card) {
        card.draggable = true;
        // Drag functionality is handled by event delegation in enableDragAndDrop()
    }

    // Update unlocks relationships when new subjects are added
    function updateUnlocksRelationships() {
        console.log('[UPDATE] Updating unlocks relationships...');
        
        // Clear all existing unlocks
        document.querySelectorAll('.subject-card').forEach(card => {
            card.dataset.unlocks = '';
        });

        // Rebuild unlocks relationships
        document.querySelectorAll('.subject-card').forEach(card => {
            const subjectCode = card.dataset.subjectId;
            const prerequisites = card.dataset.prerequisites.split(',').filter(p => p.trim());
            
            if (prerequisites.length > 0) {
                console.log(`Subject ${subjectCode} has prerequisites: [${prerequisites.join(', ')}]`);
            }
            
            // For each prerequisite, add this subject to their unlocks
            prerequisites.forEach(prereqCode => {
                const prereqCard = document.querySelector(`[data-subject-id="${prereqCode}"]`);
                if (prereqCard) {
                    const currentUnlocks = prereqCard.dataset.unlocks.split(',').filter(u => u.trim());
                    if (!currentUnlocks.includes(subjectCode)) {
                        currentUnlocks.push(subjectCode);
                        prereqCard.dataset.unlocks = currentUnlocks.join(',');
                        console.log(`Added ${subjectCode} to unlocks of ${prereqCode}. Now unlocks: [${currentUnlocks.join(', ')}]`);
                    }
                } else {
                    console.warn(`Prerequisite ${prereqCode} not found for subject ${subjectCode}`);
                }
            });
        });
        
        console.log('Unlocks relationships updated');
    }

    // Get current curriculum state for export
    function getCurrentCurriculumState() {
        const curriculum = {};
        
        for (let semester = 1; semester <= 10; semester++) {
            const semesterColumn = document.querySelector(`[data-semester="${semester}"]`);
            if (!semesterColumn) continue;
            
            const subjects = Array.from(semesterColumn.querySelectorAll('.subject-card')).map(card => {
                // Try to extract credits from existing data or default to null
                const creditsElement = card.querySelector('.subject-credits');
                const credits = creditsElement ? parseInt(creditsElement.textContent) : null;
                
                return {
                    code: card.dataset.subjectId,
                    name: card.querySelector('.subject-name').textContent.trim(),
                    prerequisites: card.dataset.prerequisites.split(',').filter(p => p.trim()),
                    semester: semester,
                    credits: credits,
                    isAdded: card.classList.contains('added-subject'),
                    description: card.title || card.querySelector('.subject-name').textContent.trim()
                };
            });
            
            if (subjects.length > 0) {
                curriculum[semester] = subjects;
            }
        }
        
        return curriculum;
    }

    // Show export modal
    function showExportModal(curriculum) {
        const totalSubjects = Object.values(curriculum).reduce((total, subjects) => total + subjects.length, 0);
        const addedSubjects = Object.values(curriculum).reduce((total, subjects) => 
            total + subjects.filter(s => s.isAdded).length, 0);

        const modalHtml = `
            <div class="modal fade" id="exportModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-download me-2"></i>
                                Exportar Malla Curricular Modificada
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="stat-card text-center">
                                        <div class="stat-number">${totalSubjects}</div>
                                        <div class="stat-label">Total Materias</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card text-center">
                                        <div class="stat-number">${addedSubjects}</div>
                                        <div class="stat-label">Materias Agregadas</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card text-center">
                                        <div class="stat-number">${simulationChanges.length}</div>
                                        <div class="stat-label">Cambios Realizados</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="exportName" class="form-label">Nombre de la Exportación</label>
                                <input type="text" class="form-control" id="exportName" 
                                       value="Malla_Modificada_${new Date().toISOString().split('T')[0]}"
                                       placeholder="Nombre del archivo/configuración">
                            </div>

                            <ul class="nav nav-tabs" id="exportTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" 
                                            data-bs-target="#preview" type="button" role="tab">
                                        <i class="fas fa-eye me-1"></i>
                                        Vista Previa
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="json-tab" data-bs-toggle="tab" 
                                            data-bs-target="#json" type="button" role="tab">
                                        <i class="fas fa-code me-1"></i>
                                        JSON
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="changes-tab" data-bs-toggle="tab" 
                                            data-bs-target="#changes" type="button" role="tab">
                                        <i class="fas fa-list me-1"></i>
                                        Cambios
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content mt-3" id="exportTabContent">
                                <div class="tab-pane fade show active" id="preview" role="tabpanel">
                                    ${generateCurriculumPreview(curriculum)}
                                </div>
                                <div class="tab-pane fade" id="json" role="tabpanel">
                                    <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>${JSON.stringify(curriculum, null, 2)}</code></pre>
                                </div>
                                <div class="tab-pane fade" id="changes" role="tabpanel">
                                    ${generateChangesPreview()}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-primary" onclick="downloadAsJSON()">
                                <i class="fas fa-download me-1"></i>
                                Descargar JSON
                            </button>
                            <button type="button" class="btn btn-success" onclick="saveToConvalidation()">
                                <i class="fas fa-save me-1"></i>
                                Guardar para Convalidación
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('exportModal'));
        
        document.getElementById('exportModal').addEventListener('hidden.bs.modal', function() {
            cleanupModal(this);
        });
        
        modal.show();
    }

    // Generate curriculum preview HTML
    function generateCurriculumPreview(curriculum) {
        let html = '<div class="row">';
        
        for (let semester = 1; semester <= 10; semester++) {
            const subjects = curriculum[semester] || [];
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">${semester}° Semestre (${subjects.length} materias)</h6>
                        </div>
                        <div class="card-body">
                            ${subjects.map(subject => `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>${subject.code}</strong><br>
                                        <small>${subject.name}</small>
                                        ${subject.isAdded ? '<span class="badge bg-success ms-1">Nueva</span>' : ''}
                                    </div>
                                </div>
                            `).join('')}
                            ${subjects.length === 0 ? '<em class="text-muted">Sin materias</em>' : ''}
                        </div>
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        return html;
    }

    // Generate changes preview HTML
    function generateChangesPreview() {
        if (simulationChanges.length === 0) {
            return '<div class="alert alert-info">No se han realizado cambios en la malla curricular.</div>';
        }

        let html = '<div class="list-group">';
        
        simulationChanges.forEach((change, index) => {
            const typeLabel = {
                'semester': 'Cambio de Semestre',
                'prerequisites': 'Cambio de Prerrequisitos',
                'added': 'Materia Agregada'
            };

            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${typeLabel[change.type] || change.type}</h6>
                        <small class="text-muted">#${index + 1}</small>
                    </div>
                    <p class="mb-1">
                        <strong>Materia:</strong> ${change.subject_code}
                        ${change.type === 'added' ? '' : `<br><strong>Anterior:</strong> ${change.old_value || 'N/A'}`}
                        <br><strong>Nuevo:</strong> ${typeof change.new_value === 'object' ? JSON.stringify(change.new_value) : change.new_value}
                    </p>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    }

    // Download curriculum as JSON
    window.downloadAsJSON = function() {
        const curriculum = getCurrentCurriculumState();
        const exportName = document.getElementById('exportName').value || 'malla_curricular';
        
        const dataStr = JSON.stringify({
            exportName: exportName,
            exportDate: new Date().toISOString(),
            curriculum: curriculum,
            changes: simulationChanges,
            metadata: {
                totalSubjects: Object.values(curriculum).reduce((total, subjects) => total + subjects.length, 0),
                totalChanges: simulationChanges.length,
                addedSubjects: Object.values(curriculum).reduce((total, subjects) => 
                    total + subjects.filter(s => s.isAdded).length, 0)
            }
        }, null, 2);
        
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `${exportName}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showSuccessMessage('Archivo JSON descargado exitosamente');
    };

    // Save to convalidation system
    window.saveToConvalidation = function() {
        const exportName = document.getElementById('exportName')?.value.trim() || 
                          `Malla_Modificada_${new Date().toISOString().split('T')[0]}`;
        
        const curriculum = getCurrentCurriculumState();
        
        // Show loading state
        const saveButton = document.querySelector('button[onclick="saveToConvalidation()"]');
        const originalText = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
        saveButton.disabled = true;

        // Prepare data for backend
        const payload = {
            name: exportName,
            institution: 'Simulación Curricular',
            curriculum: curriculum,
            changes: simulationChanges,
            _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        };

        // Send to backend
        fetch('/convalidation/save-modified-curriculum', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': payload._token,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close the export modal
                const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                if (exportModal) {
                    exportModal.hide();
                }

                // Show success message
                showSuccessMessage(`Malla curricular "${exportName}" guardada exitosamente para convalidación`);

                // Redirect to convalidation section after a short delay
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 2000);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error saving to convalidation:', error);
            showAlertModal(`No se pudo guardar la malla curricular: ${error.message}`, 'error', 'Error al Guardar');
        })
        .finally(() => {
            // Restore button state
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        });
    };

    // Show success message
    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }

    // ============================================
    // SISTEMA DE VERSIONES DE MALLAS
    // ============================================

    /**
     * Load available curriculum versions into the selector
     */
    function loadVersionsList() {
        fetch('/simulation/versions')
            .then(response => response.json())
            .then(data => {
                const menu = document.getElementById('versionDropdownMenu');
                if (!menu) return;

                // Build the menu items
                let items = `
                    <li>
                        <a class="dropdown-item" href="#" onclick="loadCurriculumVersion('current'); return false;">
                            Versión Actual (En Edición)
                        </a>
                    </li>
                `;
                
                // Add saved versions
                if (data.versions && data.versions.length > 0) {
                    items += '<li><hr class="dropdown-divider"></li>';
                    items += '<li><h6 class="dropdown-header">Versiones Guardadas</h6></li>';
                    
                    data.versions.forEach(version => {
                        const date = new Date(version.created_at).toLocaleDateString('es-ES');
                        const isCurrent = version.is_current ? ' ⭐' : '';
                        items += `
                            <li>
                                <div class="dropdown-item d-flex justify-content-between align-items-center p-0">
                                    <a href="#" class="flex-grow-1 text-decoration-none text-dark px-3 py-2" 
                                       onclick="loadCurriculumVersion('${version.id}'); return false;">
                                        v${version.version_number}${isCurrent} - ${date}
                                    </a>
                                    <button class="btn btn-sm btn-link text-danger p-2" 
                                            onclick="event.stopPropagation(); deleteVersion(${version.id}); return false;"
                                            title="Eliminar versión">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                        `;
                    });
                }

                menu.innerHTML = items;
            })
            .catch(error => {
                console.error('Error loading versions:', error);
            });
    }

    /**
     * Save current curriculum as a new version
     */
    window.saveCurrentCurriculum = function() {
        // Check if there are added or removed subjects
        const hasAddedSubjects = simulationChanges.some(c => c.type === 'added');
        const hasRemovedSubjects = simulationChanges.some(c => c.type === 'removed');
        
        if (hasAddedSubjects || hasRemovedSubjects) {
            // Show info message and redirect to convalidation
            const message = `⚠️ CONVALIDACIÓN REQUERIDA

Has ${hasAddedSubjects ? 'agregado' : ''} ${hasAddedSubjects && hasRemovedSubjects ? 'y' : ''} ${hasRemovedSubjects ? 'eliminado' : ''} materias.

Antes de guardar la versión, debes convalidar estas materias.

Serás redirigido al apartado de convalidación.
Una vez completada la convalidación, podrás guardar la nueva versión de la malla.`;
            
            showConfirmModal(
                message,
                function() {
                    // Export to convalidation system
                    const exportName = `Malla_Modificada_${new Date().toISOString().split('T')[0]}`;
                    const curriculum = getCurrentCurriculumState();
                    
                    // Show loading
                    const loadingModal = showLoadingModal('Exportando malla para convalidación...');
                    const originalButton = event?.target || document.querySelector('button[onclick="saveCurrentCurriculum()"]');
                    if (originalButton) {
                        originalButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Exportando...';
                        originalButton.disabled = true;
                    }
                    
                    // Prepare payload
                    const payload = {
                        name: exportName,
                        institution: 'Simulación Curricular - Cambios Pendientes',
                        curriculum: curriculum,
                        changes: simulationChanges,
                        _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    };
                    
                    // Send to backend
                    fetch('/convalidation/save-modified-curriculum', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': payload._token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccessMessage('Malla exportada. Redirigiendo a convalidación...');
                            
                            // Store pending save flag in sessionStorage
                            sessionStorage.setItem('pendingSave', JSON.stringify({
                                description: 'Versión con convalidaciones completadas',
                                exportedAt: new Date().toISOString()
                            }));
                            
                            // Redirect to convalidation
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1500);
                        } else {
                            throw new Error(data.message || 'Error desconocido');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlertModal(`No se pudo exportar la malla: ${error.message}`, 'error', 'Error al Exportar');
                        
                        // Restore button
                        if (originalButton) {
                            originalButton.innerHTML = '<i class="fas fa-save me-1"></i>Guardar Malla';
                            originalButton.disabled = false;
                        }
                    });
                },
                'warning',
                'Convalidación Requerida',
                'Ir a Convalidación',
                'Cancelar'
            );
            
            return; // Exit early, don't save version yet
        }
        
        // No added/removed subjects, proceed with normal save
        showPromptModal(
            'Guardar cambios actuales y crear versión histórica.\n\nLos cambios se aplicarán a la malla actual.\nEl estado anterior se guardará como versión histórica.\n\nDescripción de la versión histórica (opcional):',
            function(description) {
                // Gather all current curriculum data
                const curriculumData = {
                    subjects: [],
                    changes: window.simulationChanges || {}
                };

                // Collect all subjects with their current state
                document.querySelectorAll('.subject-card').forEach(card => {
                    const semester = card.closest('.semester-column')?.dataset.semester;
                    const prerequisites = card.dataset.prerequisites ? 
                        card.dataset.prerequisites.split(',').map(p => p.trim()).filter(p => p) : 
                        [];

                    curriculumData.subjects.push({
                        code: card.dataset.subjectId,
                        name: card.querySelector('.subject-name')?.textContent.trim(),
                        semester: parseInt(semester),
                        credits: parseInt(card.querySelector('.subject-credits')?.textContent) || 3,
                        type: card.dataset.type,
                        prerequisites: prerequisites,
                        description: card.title || '',
                        display_order: Array.from(card.parentElement.children).indexOf(card) + 1
                    });
                });

                // Send to server
                fetch('/simulation/versions/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        description: description,
                        curriculum_data: curriculumData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage(data.message || 'Cambios guardados correctamente');
                        
                        // Reload versions list
                        loadVersionsList();
                        
                        // Clear simulation changes since we just saved
                        simulationChanges = [];
                        
                        // Clear from localStorage
                        clearStoredChanges();
                        
                        updateSimulationStatus();
                        
                        // Optional: Reload page to show updated curriculum
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showAlertModal(data.message || 'No se pudo guardar la versión. Intente nuevamente.', 'error', 'Error al Guardar');
                    }
                })
                .catch(error => {
                    console.error('Error saving version:', error);
                    showAlertModal(`Error al guardar la versión: ${error.message}`, 'error', 'Error al Guardar');
                });
            },
            'Guardar Versión',
            'Ingrese una descripción para esta versión',
            ''
        );
    };

    /**
     * Load a specific curriculum version
     */
    window.loadCurriculumVersion = function(versionId) {
        const currentVersionText = document.getElementById('currentVersionText');

        if (versionId === 'current') {
            // Update button text
            if (currentVersionText) {
                currentVersionText.textContent = 'Versión Actual (En Edición)';
            }
            // Reload current version (refresh page)
            window.location.reload();
            return;
        }

        // Load specific version
        fetch(`/simulation/versions/${versionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button text with version info
                    if (currentVersionText && data.version) {
                        const date = new Date(data.version.created_at).toLocaleDateString('es-ES');
                        currentVersionText.textContent = `v${data.version.version_number} - ${date}`;
                    }
                    
                    // Show info about viewing old version
                    const versionInfo = `
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong>Visualizando versión ${data.version.version_number}</strong><br>
                            Creada: ${new Date(data.version.created_at).toLocaleString('es-ES')}<br>
                            ${data.version.description ? 'Descripción: ' + data.version.description : ''}
                            <br><small class="text-muted">Esta es una versión de solo lectura. Para editarla, vuelva a "Versión Actual".</small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    
                    const controls = document.querySelector('.curriculum-controls');
                    controls.insertAdjacentHTML('afterend', versionInfo);

                    // Rebuild curriculum grid with version data
                    rebuildCurriculumFromVersion(data.version);
                    
                    // Disable editing for old versions
                    disableEditingMode();
                } else {
                    showAlertModal(data.message || 'No se pudo cargar la versión seleccionada.', 'error', 'Error al Cargar Versión');
                }
            })
            .catch(error => {
                console.error('Error loading version:', error);
                showAlertModal(`Error al cargar la versión: ${error.message}`, 'error', 'Error al Cargar');
            });
    };

    /**
     * Rebuild curriculum grid from version data
     */
    function rebuildCurriculumFromVersion(version) {
        const subjects = version.curriculum_data.subjects || [];
        
        // Clear all subject lists
        document.querySelectorAll('.subject-list').forEach(list => {
            list.innerHTML = '';
        });

        // Rebuild subjects
        subjects.forEach(subject => {
            const semesterColumn = document.querySelector(`[data-semester="${subject.semester}"] .subject-list`);
            if (!semesterColumn) return;

            const card = createSubjectCard(
                subject.code,
                subject.name,
                subject.semester,
                subject.prerequisites.join(','),
                subject.description,
                subject.credits,
                subject.classroom_hours || 3,
                subject.student_hours || 6,
                subject.type,
                subject.is_required !== false
            );

            semesterColumn.appendChild(card);
        });

        // Update credits display
        updateCreditsDisplay();
        
        // Update prerequisites relationships
        updateUnlocksRelationships();
    }

    /**
     * Disable editing mode when viewing old versions
     */
    function disableEditingMode() {
        // Disable drag and drop
        document.querySelectorAll('.subject-card').forEach(card => {
            card.draggable = false;
            card.style.cursor = 'default';
        });

        // Disable buttons
        document.querySelectorAll('.curriculum-controls button:not(#versionSelector)').forEach(btn => {
            if (btn.textContent.includes('Exportar')) {
                // Keep export button enabled
                return;
            }
            btn.disabled = true;
            btn.classList.add('opacity-50');
        });

        // Hide edit/delete buttons on cards
        document.querySelectorAll('.subject-card .btn-danger, .subject-card .btn-primary').forEach(btn => {
            btn.style.display = 'none';
        });
    }

    // Load versions list on page load
    loadVersionsList();

    /**
     * Delete a specific version with confirmation
     */
    window.deleteVersion = function(versionId) {
        if (!versionId) {
            showAlertModal('ID de versión no válido. No se puede proceder con la eliminación.', 'error', 'Error de Versión');
            return;
        }
        
        // Get version info for confirmation
        fetch(`/simulation/versions`)
            .then(response => response.json())
            .then(data => {
                const version = data.versions.find(v => v.id == versionId);
                if (!version) {
                    showAlertModal('La versión seleccionada no se encontró en el sistema.', 'error', 'Versión No Encontrada');
                    return;
                }
                
                const date = new Date(version.created_at).toLocaleDateString('es-ES');
                const versionName = `v${version.version_number} - ${date}`;
                
                // Show confirmation modal
                showConfirmModal(
                    `¿Estás seguro que deseas eliminar la versión "${versionName}"?\n\n⚠️ ADVERTENCIA: Esta acción NO se puede deshacer.\n\nSe eliminará permanentemente esta versión del historial.`,
                    function() {
                        // Send delete request
                        fetch(`/simulation/versions/${versionId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showSuccessMessage(`Versión "${versionName}" eliminada correctamente`);
                                
                                // Reload versions list
                                loadVersionsList();
                                
                                // Reset to current version if needed
                                const currentVersionText = document.getElementById('currentVersionText');
                                if (currentVersionText) {
                                    currentVersionText.textContent = 'Versión Actual (En Edición)';
                                }
                            } else {
                                showAlertModal(data.message || 'No se pudo eliminar la versión. Intente nuevamente.', 'error', 'Error al Eliminar');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting version:', error);
                            showAlertModal(`Error al eliminar la versión: ${error.message}`, 'error', 'Error al Eliminar');
                        });
                    },
                    'danger',
                    'Eliminar Versión',
                    'Sí, eliminar',
                    'Cancelar'
                );
            })
            .catch(error => {
                console.error('Error loading versions:', error);
                showAlertModal('No se pudo cargar la información de las versiones.', 'error', 'Error de Conexión');
            });
    };

    // FIXED: Global scroll fix watcher - monitors and fixes scroll issues
    const scrollFixWatcher = new MutationObserver((mutations) => {
        // Check if body has modal-open but no visible modals
        if (document.body.classList.contains('modal-open')) {
            const visibleModals = document.querySelectorAll('.modal.show');
            
            // If no modals are visible but body is marked as modal-open, clean up
            if (visibleModals.length === 0) {
                console.log('⚠️ Detected stuck modal state, cleaning up...');
                
                // Clean body
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Clean grid
                const curriculumGrid = document.querySelector('.curriculum-grid');
                if (curriculumGrid) {
                    curriculumGrid.style.setProperty('overflow-x', 'auto', 'important');
                    curriculumGrid.style.setProperty('overflow-y', 'visible', 'important');
                }
                
                // Remove backdrops
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                
                console.log('✅ Scroll fixed automatically');
            }
        }
    });
    
    // Start watching body for class changes
    scrollFixWatcher.observe(document.body, {
        attributes: true,
        attributeFilter: ['class', 'style']
    });
    
    console.log('🔧 Scroll fix watcher initialized');

    // Debug: Verify functions are available
    console.log('=== SIMULATION JS LOADED ===');
    console.log('addNewSubject available:', typeof window.addNewSubject);
    console.log('exportModifiedCurriculum available:', typeof window.exportModifiedCurriculum);
    console.log('showComponentCredits available:', typeof window.showComponentCredits);
    console.log('=== END DEBUG ===');

    // Initialize simulation when page loads
    initializeSimulation();
    
    // ============================================
    // BIDIRECTIONAL SYNC: Listen for changes from other sources
    // ============================================
    
    /**
     * Listen for custom events from leveling-subjects module
     * This enables real-time synchronization when editing from /leveling-subjects
     */
    window.addEventListener('levelingSubjectUpdated', function(e) {
        const { code, name, credits, classroomHours, studentHours, description, updatedLocalStorage } = e.detail;
        
        // Find the card in the DOM (works for both official and temporary subjects)
        const card = document.querySelector(`[data-subject-id="${code}"]`);
        
        if (card) {
            // Update name
            const nameElement = card.querySelector('.subject-name');
            if (nameElement) {
                nameElement.textContent = name;
            }
            
            // Update title/description
            card.title = description || name;
            
            // Update credits and hours (info-values in header)
            const infoValues = card.querySelectorAll('.subject-card-header .info-value');
            if (infoValues.length >= 3) {
                infoValues[0].textContent = credits;
                infoValues[1].textContent = classroomHours;
                infoValues[2].textContent = studentHours;
            }
            
            // Add visual indicator for edited leveling subject
            card.classList.add('edited-subject');
            
            // Recalculate credits display
            updateCreditsDisplay();
        }
        
        // If localStorage was updated, also reload the changes array
        if (updatedLocalStorage) {
            const updatedChanges = loadChangesFromStorage();
            if (updatedChanges) {
                simulationChanges = updatedChanges;
            }
        }
        
        // Update simulation status to reflect the change
        updateSimulationStatus();
    });
    
    /**
     * Listen for leveling subject removal events
     * This enables real-time removal preview when deleting from /leveling-subjects
     */
    window.addEventListener('levelingSubjectRemoved', function(e) {
        const { code, name } = e.detail;
        
        // Find the card in the DOM
        const card = document.querySelector(`[data-subject-id="${code}"]`);
        
        if (card) {
            // Apply removal preview styles
            applyRemovedStyle(card);
            
            // Add to simulationChanges array
            const existingIndex = simulationChanges.findIndex(c => 
                c.type === 'removed' && c.subject_code === code
            );
            
            if (existingIndex === -1) {
                simulationChanges.push({
                    type: 'removed',
                    subject_code: code,
                    subject_name: name,
                    old_value: null,
                    new_value: null,
                    timestamp: new Date().toISOString()
                });
            }
            
            // Update simulation status
            updateSimulationStatus();
        }
    });
    
    /**
     * Listen for localStorage changes from other tabs (cross-tab sync)
     * This enables synchronization across different browser tabs
     */
    window.addEventListener('storage', function(e) {
        if (e.key === STORAGE_KEY) {
            // Reload changes and update the simulation view
            const updatedChanges = loadChangesFromStorage();
            if (!updatedChanges) return;
            
            simulationChanges = updatedChanges;
            
            // Update only the affected cards (don't reload the whole page)
            updatedChanges.forEach(change => {
                const card = document.querySelector(`[data-subject-id="${change.subject_code}"]`);
                
                if (change.type === 'added' && card) {
                    // Update card content with new data
                    const data = change.new_value;
                    if (data) {
                        updateCardFromData(card, data);
                    }
                }
            });
            
            // Update simulation status display
            updateSimulationStatus();
        }
    });
    
    /**
     * Update a subject card with new data from localStorage
     */
    function updateCardFromData(card, data) {
        // Update name
        const nameElement = card.querySelector('.subject-name');
        if (nameElement && data.name) {
            nameElement.textContent = data.name;
        }
        
        // Update title/description
        if (data.description) {
            card.title = data.description;
        }
        
        // Update credits (first info-value in header)
        const infoValues = card.querySelectorAll('.subject-card-header .info-value');
        if (infoValues.length >= 3) {
            if (data.credits !== undefined) {
                infoValues[0].textContent = data.credits;
            }
            if (data.classroomHours !== undefined) {
                infoValues[1].textContent = data.classroomHours;
            }
            if (data.studentHours !== undefined) {
                infoValues[2].textContent = data.studentHours;
            }
        }
        
        // Recalculate credits display
        updateCreditsDisplay();
    }
    
    /**
     * Apply removal preview style to a subject card
     * This shows the user that the subject is marked for deletion
     */
    function applyRemovedStyle(card) {
        // Set opacity and disable interactions
        card.style.opacity = '0.5';
        card.style.pointerEvents = 'none';
        card.setAttribute('data-removed', 'true');
        
        // Add red border
        card.style.border = '2px solid #dc3545';
        card.style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
        
        // Strike through the name
        const nameElement = card.querySelector('.subject-name');
        if (nameElement) {
            nameElement.style.textDecoration = 'line-through';
            nameElement.style.color = '#dc3545';
        }
        
        // Add removed badge if not exists
        if (!card.querySelector('.removed-badge')) {
            const badge = document.createElement('div');
            badge.className = 'removed-badge';
            badge.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                background: #dc3545;
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
                z-index: 10;
            `;
            badge.textContent = 'ELIMINADA';
            card.style.position = 'relative';
            card.appendChild(badge);
        }
    }
});
