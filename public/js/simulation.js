// Simulation View JavaScript
// Make sure key functions are available immediately
window.addNewSubject = function() {
    console.log('addNewSubject called');
    if (typeof showAddSubjectModal === 'function') {
        showAddSubjectModal();
    } else {
        alert('Función no disponible aún, por favor espere a que la página cargue completamente');
    }
};

window.exportModifiedCurriculum = function() {
    console.log('exportModifiedCurriculum called');
    if (typeof getCurrentCurriculumState === 'function' && typeof showExportModal === 'function') {
        const modifiedCurriculum = getCurrentCurriculumState();
        showExportModal(modifiedCurriculum);
    } else {
        alert('Función no disponible aún, por favor espere a que la página cargue completamente');
    }
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
    
    // Initialize total credits from all visible cards
    function initializeTotalCredits() {
        careerCredits = 0;
        totalCredits = 0;
        document.querySelectorAll('.subject-card').forEach(card => {
            const creditsElement = card.querySelector('.info-box:first-child .info-value');
            if (creditsElement) {
                const credits = parseInt(creditsElement.textContent) || 0;
                totalCredits += credits;
                
                // Check if it's a leveling subject (lengua_extranjera type)
                const isLeveling = card.classList.contains('lengua_extranjera');
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
        addSimulationControls();
        
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
            });
            
            column.addEventListener('dragleave', function(e) {
                // Only remove drag-over if we're actually leaving the column
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('drag-over');
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
                    
                    console.log('Moving subject:', {
                        subjectId,
                        from: oldSemester,
                        to: newSemester
                    });
                    
                    if (newSemester !== oldSemester) {
                        console.log('*** CALLING MODAL FUNCTION ***');
                        console.log('Subject:', subjectId, 'From:', oldSemester, 'To:', newSemester);
                        
                        // Show modal to optionally edit prerequisites
                        showMoveSubjectModal(draggedCard, this, newSemester, oldSemester);
                    } else {
                        console.log('Same semester, no modal needed');
                    }
                }
            });
        });
    }
    
    // Move subject to new semester
    function moveSubjectToSemester(card, newColumn, newSemester) {
        const subjectList = newColumn.querySelector('.subject-list');
        subjectList.appendChild(card);
        
        // Update semester display
        const semesterBadge = card.querySelector('.semester-badge');
        if (semesterBadge) {
            semesterBadge.textContent = `Semestre ${newSemester}`;
        }
    }
    
    // Record simulation changes
    function recordSimulationChange(subjectId, changeType, newValue, oldValue) {
        // Remove existing change for this subject and type
        simulationChanges = simulationChanges.filter(change => 
            !(change.subject_code === subjectId && change.type === changeType)
        );
        
        // Add new change
        simulationChanges.push({
            subject_code: subjectId,
            type: changeType,
            new_value: newValue,
            old_value: oldValue
        });
        
        updateSimulationStatus();
    }
    
    // Update simulation status display
    function updateSimulationStatus() {
        const statusDiv = document.getElementById('simulation-status');
        if (statusDiv) {
            statusDiv.innerHTML = `
                <div class="alert alert-info">
                    <strong>Simulación activa:</strong> ${simulationChanges.length} cambio(s) temporal(es)
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="showChangesModal()">
                        Ver cambios
                    </button>
                </div>
            `;
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
    function addSimulationControls() {
        const controlsHtml = `
            <div class="simulation-controls mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <div id="simulation-status"></div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-info me-2" onclick="openConvalidation()">
                            <i class="fas fa-exchange-alt me-1"></i>
                            Realizar Convalidación
                        </button>
                        <div class="btn-group" role="group">
                            <button class="btn btn-primary" onclick="analyzeImpact()">
                                <i class="fas fa-chart-line me-1"></i>
                                Analizar
                            </button>
                            <button class="btn btn-warning" onclick="resetSimulation()">
                                <i class="fas fa-undo me-1"></i>
                                Reset
                            </button>
                            <button class="btn btn-success" onclick="saveSimulation()">
                                <i class="fas fa-save me-1"></i>
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const gridContainer = document.querySelector('.curriculum-grid');
        gridContainer.insertAdjacentHTML('beforebegin', controlsHtml);
    }
    
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
            alert('Error al analizar el impacto');
        });
    };
    
    // Reset simulation to original state
    window.resetSimulation = function() {
        if (confirm('¿Está seguro de que desea resetear todos los cambios? Esto recargará la página.')) {
            // Simple solution: reload the page to restore original state
            window.location.reload();
        }
    };
    
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
    
    // Show changes modal
    window.showChangesModal = function() {
        const modalHtml = `
            <div class="modal fade" id="changesModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cambios Temporales</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${simulationChanges.length > 0 ? `
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Materia</th>
                                                <th>Tipo de Cambio</th>
                                                <th>Valor Anterior</th>
                                                <th>Valor Nuevo</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${simulationChanges.map((change, index) => `
                                                <tr>
                                                    <td>${change.subject_code}</td>
                                                    <td>${change.type === 'semester' ? 'Semestre' : 'Prerrequisitos'}</td>
                                                    <td>${change.old_value}</td>
                                                    <td>${change.new_value}</td>
                                                    <td>
                                                        <button class="btn btn-sm btn-danger" onclick="removeChange(${index})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            ` : '<p class="text-muted">No hay cambios temporales.</p>'}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
            alert('No hay cambios para guardar');
            return;
        }
        
        if (confirm('¿Está seguro de que desea guardar estos cambios permanentemente?')) {
            alert('Funcionalidad de guardado no implementada. Los cambios son temporales.');
        }
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
                                    <label class="form-label">
                                        <i class="fas fa-list me-1"></i>
                                        Nuevos prerrequisitos:
                                    </label>
                                    <textarea class="form-control" id="new-prerequisites" rows="3" 
                                        placeholder="Ingrese códigos de materias separados por comas">${currentPrereqs.join(', ')}</textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Ejemplo: 4100400, 4100401, 4100402
                                    </small>
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
        });
        
        modal.show();
    }
    
    // Confirm subject move with optional prerequisite changes
    window.confirmMoveSubject = function(subjectId, newSemester, oldSemester) {
        const moveData = window.tempMoveData;
        const editPrereqs = document.getElementById('editPrerequisites').checked;
        
        // Move the subject
        moveSubjectToSemester(moveData.card, moveData.newColumn, newSemester);
        recordSimulationChange(subjectId, 'semester', newSemester, oldSemester);
        
        // Handle prerequisite changes if enabled
        if (editPrereqs) {
            const newPrereqs = document.getElementById('new-prerequisites').value
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
            showPrerequisiteEditor(subjectCard);
        }
    });
    
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
                            <h6>Materia: ${subjectName} (${subjectId})</h6>
                            <div class="mt-3">
                                <label class="form-label">Prerrequisitos actuales:</label>
                                <div id="current-prereqs">
                                    ${currentPrereqs.length > 0 ? currentPrereqs.map(prereq => `
                                        <span class="badge bg-secondary me-1">${prereq}</span>
                                    `).join('') : '<span class="text-muted">Sin prerrequisitos</span>'}
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Modificar prerrequisitos:</label>
                                <textarea class="form-control" id="new-prereqs" rows="3" 
                                    placeholder="Ingrese códigos de materias separados por comas">${currentPrereqs.join(', ')}</textarea>
                                <small class="form-text text-muted">
                                    Ejemplo: 4100400, 4100401, 4100402
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="updatePrerequisites('${subjectId}')">
                                Actualizar
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
        
        modal.show();
    }
    
    // Update prerequisites
    window.updatePrerequisites = function(subjectId) {
        const newPrereqs = document.getElementById('new-prereqs').value
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
                                <div class="mb-3">
                                    <label for="subjectPrerequisites" class="form-label">Prerrequisitos (opcional)</label>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="subjectPrerequisites" 
                                                   placeholder="Ej: MAT100, FIS101, QUI200">
                                            <div class="form-text">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Digite los códigos de las materias separados por comas. 
                                                Se validará que las materias existan en la malla.
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-outline-secondary w-100" onclick="showPrerequisiteHelper()">
                                                <i class="fas fa-search me-1"></i>
                                                Buscar Materias
                                            </button>
                                        </div>
                                    </div>
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
    function showPrerequisiteHelper() {
        const existingSubjects = Array.from(document.querySelectorAll('.subject-card')).map(card => ({
            code: card.dataset.subjectId,
            name: card.querySelector('.subject-name').textContent,
            semester: card.closest('.semester-column').dataset.semester
        }));

        const helperHtml = `
            <div class="modal fade" id="prerequisiteHelperModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Seleccionar Prerrequisitos</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="prerequisiteSearch" 
                                       placeholder="Buscar materias..." onkeyup="filterPrerequisites()">
                            </div>
                            <div class="row" id="prerequisiteList">
                                ${existingSubjects.map(subject => `
                                    <div class="col-md-6 mb-2 prerequisite-item">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   value="${subject.code}" id="prereq_${subject.code}">
                                            <label class="form-check-label" for="prereq_${subject.code}">
                                                <strong>${subject.code}</strong> - ${subject.name}
                                                <small class="text-muted">(Sem. ${subject.semester})</small>
                                            </label>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="applySelectedPrerequisites()">
                                Aplicar Selección
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', helperHtml);
        const modal = new bootstrap.Modal(document.getElementById('prerequisiteHelperModal'));
        
        document.getElementById('prerequisiteHelperModal').addEventListener('hidden.bs.modal', function() {
            cleanupModal(this);
        });
        
        modal.show();
    }

    // Filter prerequisites in helper modal
    window.filterPrerequisites = function() {
        const searchTerm = document.getElementById('prerequisiteSearch').value.toLowerCase();
        const items = document.querySelectorAll('.prerequisite-item');
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? 'block' : 'none';
        });
    };

    // Apply selected prerequisites from helper modal
    window.applySelectedPrerequisites = function() {
        const selected = Array.from(document.querySelectorAll('#prerequisiteList input:checked'))
                              .map(input => input.value);
        
        document.getElementById('subjectPrerequisites').value = selected.join(', ');
        bootstrap.Modal.getInstance(document.getElementById('prerequisiteHelperModal')).hide();
    };

    // Create new subject
    window.createNewSubject = function() {
        const code = document.getElementById('subjectCode').value.trim().toUpperCase();
        const name = document.getElementById('subjectName').value.trim();
        const semester = document.getElementById('subjectSemester').value;
        const prerequisites = document.getElementById('subjectPrerequisites').value.trim();
        const description = document.getElementById('subjectDescription').value.trim();

        // Validation
        if (!code || !name || !semester) {
            alert('Por favor complete todos los campos obligatorios');
            return;
        }

        // Check if code already exists
        if (document.querySelector(`[data-subject-id="${code}"]`)) {
            alert('Ya existe una materia con ese código');
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
            const proceed = confirm(
                `Los siguientes prerrequisitos no existen en la malla actual: ${invalidPrereqs.join(', ')}\n\n` +
                `¿Desea continuar agregando la materia de todas formas?`
            );
            if (!proceed) return;
        }

        // Create the new subject card
        const newSubjectCard = createSubjectCard(code, name, semester, prerequisiteArray.join(','), description, credits, classroomHours, studentHours, type, isRequired);
        
        // Add to the appropriate semester column
        const semesterColumn = document.querySelector(`[data-semester="${semester}"] .subject-list`);
        if (!semesterColumn) {
            alert(`Error: No se encontró el semestre ${semester}`);
            return;
        }
        
        semesterColumn.appendChild(newSubjectCard);
        
        // Update credits (check if it's a leveling subject)
        const creditsNum = parseInt(credits) || 0;
        totalCredits += creditsNum;
        
        // Only add to career credits if it's NOT a leveling subject
        const isLeveling = type === 'lengua_extranjera';
        if (!isLeveling) {
            careerCredits += creditsNum;
        }
        
        updateCreditsDisplay();

        // Update drag and drop functionality
        enableDragAndDropForCard(newSubjectCard);

        // Update unlocks relationships for existing subjects
        updateUnlocksRelationships();

        // Record as simulation change
        recordSimulationChange(code, 'added', {
            name: name,
            semester: semester,
            prerequisites: prerequisiteArray,
            description: description
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
        card.className = `subject-card ${type} added-subject`;
        card.dataset.subjectId = code;
        card.dataset.type = type;
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
            alert(`Error al guardar la malla curricular: ${error.message}`);
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

    // Debug: Verify functions are available
    console.log('=== SIMULATION JS LOADED ===');
    console.log('addNewSubject available:', typeof window.addNewSubject);
    console.log('exportModifiedCurriculum available:', typeof window.exportModifiedCurriculum);
    console.log('=== END DEBUG ===');

    // Initialize simulation when page loads
    initializeSimulation();
});
