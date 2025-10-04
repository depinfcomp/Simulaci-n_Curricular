        // Immediate function definitions to prevent ReferenceError
        function addNewSubject() {
            console.log('addNewSubject called');
            
            // Try to call the full function if available, otherwise show a basic modal
            if (window.showAddSubjectModal && typeof window.showAddSubjectModal === 'function') {
                window.showAddSubjectModal();
            } else {
                // Fallback basic modal
                showBasicAddSubjectModal();
            }
        }
        
        function exportModifiedCurriculum() {
            console.log('exportModifiedCurriculum called');
            
            // Try to call the full function if available, otherwise show basic modal
            if (window.getCurrentCurriculumState && window.showExportModal && 
                typeof window.getCurrentCurriculumState === 'function' && 
                typeof window.showExportModal === 'function') {
                const curriculum = window.getCurrentCurriculumState();
                window.showExportModal(curriculum);
            } else {
                // Fallback basic modal
                showBasicExportModal();
            }
        }
        
        function showBasicAddSubjectModal() {
            const modalHtml = `
                <div class="modal fade" id="basicAddModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Agregar Nueva Materia</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="basicSubjectCode" class="form-label">Código *</label>
                                    <input type="text" class="form-control" id="basicSubjectCode" placeholder="Ej: MAT101">
                                </div>
                                <div class="mb-3">
                                    <label for="basicSubjectName" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="basicSubjectName" placeholder="Ej: Matemáticas">
                                </div>
                                <div class="mb-3">
                                    <label for="basicSubjectSemester" class="form-label">Semestre *</label>
                                    <select class="form-select" id="basicSubjectSemester">
                                        <option value="">Seleccionar</option>
                                        ${[1,2,3,4,5,6,7,8,9,10].map(s => `<option value="${s}">${s}° Semestre</option>`).join('')}
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="basicSubjectPrereqs" class="form-label">Prerrequisitos (opcional)</label>
                                    <input type="text" class="form-control" id="basicSubjectPrereqs" 
                                           placeholder="Códigos separados por comas: MAT100, FIS101">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-success" onclick="createBasicSubject()">Agregar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal
            const existing = document.getElementById('basicAddModal');
            if (existing) existing.remove();
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            new bootstrap.Modal(document.getElementById('basicAddModal')).show();
        }
        
        function createBasicSubject() {
            const code = document.getElementById('basicSubjectCode').value.trim().toUpperCase();
            const name = document.getElementById('basicSubjectName').value.trim();
            const semester = document.getElementById('basicSubjectSemester').value;
            const prereqs = document.getElementById('basicSubjectPrereqs').value.trim();
            
            if (!code || !name || !semester) {
                alert('Por favor complete todos los campos obligatorios');
                return;
            }
            
            if (document.querySelector(`[data-subject-id="${code}"]`)) {
                alert('Ya existe una materia con ese código');
                return;
            }
            
            // Create normal subject card that behaves like existing ones
            const semesterColumn = document.querySelector(`[data-semester="${semester}"] .subject-list`);
            if (!semesterColumn) {
                alert('Error: No se encontró el semestre');
                return;
            }
            
            // Create a proper subject card element
            const newCard = document.createElement('div');
            newCard.className = 'subject-card available added-subject';
            newCard.dataset.subjectId = code;
            newCard.dataset.prerequisites = prereqs;
            newCard.dataset.unlocks = '';
            newCard.title = name;
            
            newCard.innerHTML = `
                <div class="subject-name">${name}</div>
                <div class="subject-code">${code}</div>
                <div class="semester-badge">Semestre ${semester}</div>
            `;
            
            // Add to semester column
            semesterColumn.appendChild(newCard);
            
            // Make it draggable and interactive like other cards
            enableCardInteractivity(newCard);
            
            // Update unlocks relationships for all cards
            updateUnlocksRelationships();
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('basicAddModal')).hide();
            
            // Show success message
            showSuccessMessage(`Materia "${name}" (${code}) agregada al semestre ${semester}`);
        }
        
        function enableCardInteractivity(card) {
            // Make draggable
            card.draggable = true;
            
            // COMMENTED OUT: This was interfering with the main drag and drop system
            /*
            // Add drag event listeners
            card.addEventListener('dragstart', function(e) {
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.outerHTML);
                window.draggedCard = this;
            });
            
            card.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                window.draggedCard = null;
            });
            */
            
            // COMMENTED OUT: This was interfering with the main simulation.js click system
            /*
            // Add click event for highlighting
            card.addEventListener('click', function() {
                highlightRelatedSubjects(this);
            });
            */
        }
        
        // COMMENTED OUT: This function was interfering with the main simulation.js click system
        /*
        function highlightRelatedSubjects(card) {
            // Check if this card is already selected
            const wasSelected = card.classList.contains('selected');
            
            // Clear all highlights first
            document.querySelectorAll('.subject-card').forEach(c => {
                c.classList.remove('prerequisite', 'unlocks', 'selected');
            });
            
            // If it was already selected, just clear highlights (deselect)
            if (wasSelected) {
                window.selectedCard = null;
                return;
            }
            
            // Otherwise, select and highlight this card
            const subjectId = card.dataset.subjectId;
            const prerequisites = card.dataset.prerequisites.split(',').filter(p => p.trim());
            const unlocks = card.dataset.unlocks.split(',').filter(u => u.trim());
            
            // Highlight the selected card
            card.classList.add('selected');
            window.selectedCard = card;
            
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
            
            console.log(`Selected: ${subjectId}, Prerequisites: [${prerequisites.join(', ')}], Unlocks: [${unlocks.join(', ')}]`);
        }
        */
        
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
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert-success');
                if (alert) {
                    alert.remove();
                }
            }, 3000);
        }
        
        // Update unlocks relationships when new subjects are added
        function updateUnlocksRelationships() {
            // Clear all existing unlocks
            document.querySelectorAll('.subject-card').forEach(card => {
                card.dataset.unlocks = '';
            });

            // Rebuild unlocks relationships
            document.querySelectorAll('.subject-card').forEach(card => {
                const subjectCode = card.dataset.subjectId;
                const prerequisites = card.dataset.prerequisites.split(',').filter(p => p.trim());
                
                // For each prerequisite, add this subject to their unlocks
                prerequisites.forEach(prereqCode => {
                    const prereqCard = document.querySelector(`[data-subject-id="${prereqCode}"]`);
                    if (prereqCard) {
                        const currentUnlocks = prereqCard.dataset.unlocks.split(',').filter(u => u.trim());
                        if (!currentUnlocks.includes(subjectCode)) {
                            currentUnlocks.push(subjectCode);
                            prereqCard.dataset.unlocks = currentUnlocks.join(',');
                        }
                    }
                });
            });
            
            console.log('Unlocks relationships updated');
        }
        
        function showBasicExportModal() {
            const modalHtml = `
                <div class="modal fade" id="basicExportModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Exportar Malla Modificada</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="basicExportName" class="form-label">Nombre de la exportación</label>
                                    <input type="text" class="form-control" id="basicExportName" 
                                           value="Malla_Modificada_${new Date().toISOString().split('T')[0]}">
                                </div>
                                <div class="alert alert-info">
                                    <h6>Resumen de la malla actual:</h6>
                                    <div id="exportSummary">Calculando...</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary" onclick="downloadBasicJSON()">Descargar JSON</button>
                                <button type="button" class="btn btn-success" onclick="saveBasicToConvalidation()">Guardar para Convalidación</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal
            const existing = document.getElementById('basicExportModal');
            if (existing) existing.remove();
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('basicExportModal'));
            modal.show();
            
            // Calculate summary
            setTimeout(calculateBasicSummary, 100);
        }
        
        function calculateBasicSummary() {
            const summary = document.getElementById('exportSummary');
            if (!summary) return;
            
            let totalSubjects = 0;
            let addedSubjects = 0;
            let html = '<ul>';
            
            for (let sem = 1; sem <= 10; sem++) {
                const subjects = document.querySelectorAll(`[data-semester="${sem}"] .subject-card`);
                const added = document.querySelectorAll(`[data-semester="${sem}"] .subject-card.added-subject`);
                
                if (subjects.length > 0) {
                    html += `<li>Semestre ${sem}: ${subjects.length} materias`;
                    if (added.length > 0) {
                        html += ` (${added.length} nuevas)`;
                    }
                    html += '</li>';
                }
                
                totalSubjects += subjects.length;
                addedSubjects += added.length;
            }
            
            html += '</ul>';
            html += `<p><strong>Total: ${totalSubjects} materias, ${addedSubjects} agregadas</strong></p>`;
            
            summary.innerHTML = html;
        }
        
        function getBasicCurriculumState() {
            const curriculum = {};
            
            for (let semester = 1; semester <= 10; semester++) {
                const cards = document.querySelectorAll(`[data-semester="${semester}"] .subject-card`);
                if (cards.length > 0) {
                    curriculum[semester] = Array.from(cards).map(card => ({
                        code: card.dataset.subjectId,
                        name: card.querySelector('.subject-name').textContent.trim(),
                        prerequisites: card.dataset.prerequisites.split(',').filter(p => p.trim()),
                        semester: semester,
                        credits: 3, // Default
                        isAdded: card.classList.contains('added-subject'),
                        description: card.title || card.querySelector('.subject-name').textContent.trim()
                    }));
                }
            }
            
            return curriculum;
        }
        
        function downloadBasicJSON() {
            const curriculum = getBasicCurriculumState();
            const exportName = document.getElementById('basicExportName').value || 'malla_curricular';
            
            const data = {
                exportName: exportName,
                exportDate: new Date().toISOString(),
                curriculum: curriculum,
                source: 'basic_export'
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `${exportName}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            alert('Archivo JSON descargado exitosamente');
        }
        
        function saveBasicToConvalidation() {
            const exportName = document.getElementById('basicExportName').value.trim() || 
                              `Malla_Modificada_${new Date().toISOString().split('T')[0]}`;
            
            const curriculum = getBasicCurriculumState();
            
            const saveButton = document.querySelector('button[onclick="saveBasicToConvalidation()"]');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Guardando...';
            saveButton.disabled = true;

            const payload = {
                name: exportName,
                institution: 'Simulación Curricular',
                curriculum: curriculum,
                changes: [],
                _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            };

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
                    bootstrap.Modal.getInstance(document.getElementById('basicExportModal')).hide();
                    alert(`Malla curricular "${exportName}" guardada exitosamente para convalidación`);
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error saving to convalidation:', error);
                alert(`Error al guardar la malla curricular: ${error.message}`);
            })
            .finally(() => {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            });
        }
        
        // Make functions globally available
        window.addNewSubject = addNewSubject;
        window.exportModifiedCurriculum = exportModifiedCurriculum;
        
        // Initialize drag and drop functionality
        function initializeDragAndDrop() {
            // COMMENTED OUT: This was interfering with the main simulation.js drag and drop system
            /*
            const semesterColumns = document.querySelectorAll('.semester-column');
            
            semesterColumns.forEach(column => {
                column.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    this.classList.add('drag-over');
                });
                
                column.addEventListener('dragleave', function(e) {
                    if (!this.contains(e.relatedTarget)) {
                        this.classList.remove('drag-over');
                    }
                });
                
                column.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('drag-over');
                    
                    if (window.draggedCard) {
                        const targetSemester = this.dataset.semester;
                        const currentSemester = window.draggedCard.closest('.semester-column').dataset.semester;
                        
                        if (targetSemester !== currentSemester) {
                            // Move the card to new semester
                            const targetList = this.querySelector('.subject-list');
                            targetList.appendChild(window.draggedCard);
                            
                            // Update semester badge
                            const semesterBadge = window.draggedCard.querySelector('.semester-badge');
                            if (semesterBadge) {
                                semesterBadge.textContent = `Semestre ${targetSemester}`;
                            }
                            
                            showSuccessMessage(`Materia movida al semestre ${targetSemester}`);
                        }
                    }
                });
            });
            */
            
            // Enable interactivity for existing cards
            document.querySelectorAll('.subject-card').forEach(card => {
                if (!card.hasAttribute('data-interactive')) {
                    enableCardInteractivity(card);
                    card.setAttribute('data-interactive', 'true');
                }
            });
            
            // COMMENTED OUT: This was interfering with the main simulation.js click system
            /*
            // Add click outside listener to deselect cards
            document.addEventListener('click', function(e) {
                // If click is not on a subject card, clear highlights
                if (!e.target.closest('.subject-card')) {
                    document.querySelectorAll('.subject-card').forEach(c => {
                        c.classList.remove('prerequisite', 'unlocks', 'selected');
                    });
                    window.selectedCard = null;
                }
            });
            */
        }
        
        // Reset temporary changes
        window.resetTemporaryChanges = function() {
            if (confirm('¿Está seguro de que desea eliminar todas las materias agregadas y cambios temporales?')) {
                // Remove all added subjects
                document.querySelectorAll('.subject-card.added-subject').forEach(card => {
                    card.remove();
                });
                
                // Reset moved subjects to original positions if needed
                // (This would require more complex state tracking for full functionality)
                
                showSuccessMessage('Cambios temporales eliminados');
                
                // Refresh the page to ensure clean state
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        };
        
        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initializeDragAndDrop();
        });
        
        // Also initialize immediately in case DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDragAndDrop);
        } else {
            initializeDragAndDrop();
        }
        
        console.log('Basic functions loaded:', {
            addNewSubject: typeof window.addNewSubject,
            exportModifiedCurriculum: typeof window.exportModifiedCurriculum,
            resetTemporaryChanges: typeof window.resetTemporaryChanges
        });
