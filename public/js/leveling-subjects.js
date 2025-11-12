// Leveling Subjects Management JavaScript

let deleteLevelingId = null;
let deleteLevelingCode = null;
let deleteLevelingName = null;

// Storage constants are defined in leveling-subjects-sync.js

// Create new leveling
document.getElementById('createForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Clear previous errors
    clearErrors('create');
    
    try {
        const response = await fetch('/leveling-subjects', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createModal'));
            modal.hide();
            
            // Show success message
            showSuccessModal('Materia nivelación creada exitosamente', result.message);
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            if (result.errors) {
                showErrors('create', result.errors);
            } else {
                showErrorModal('Error', result.message || 'No se pudo crear la materia nivelación');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorModal('Error de conexión', 'No se pudo conectar con el servidor');
    }
});

// Edit leveling
async function editLeveling(id) {
    // Check if it's a temporary subject
    if (String(id).startsWith('temp_')) {
        const code = String(id).replace('temp_', '');
        editTemporaryLeveling(code);
        return;
    }
    
    try {
        const response = await fetch(`/leveling-subjects/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            const leveling = result.leveling;
            
            // Fill form
            document.getElementById('edit_id').value = leveling.id;
            document.getElementById('edit_code').value = leveling.code;
            document.getElementById('edit_name').value = leveling.name;
            document.getElementById('edit_credits').value = leveling.credits;
            document.getElementById('edit_classroom_hours').value = leveling.classroom_hours || 0;
            document.getElementById('edit_student_hours').value = leveling.student_hours || 0;
            document.getElementById('edit_description').value = leveling.description || '';
            
            // Clear previous errors
            clearErrors('edit');
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        } else {
            showErrorModal('Error', result.message || 'No se pudo cargar los datos de la materia');
        }
    } catch (error) {
        console.error('Error completo:', error);
        showErrorModal('Error', 'No se pudo cargar los datos de la materia: ' + error.message);
    }
}

// Update leveling
document.getElementById('editForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('edit_id').value;
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Clear previous errors
    clearErrors('edit');
    
    try {
        const response = await fetch(`/leveling-subjects/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Try to update simulation if this subject exists
            if (result.leveling) {
                updateSimulationFromLeveling(result.leveling);
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            modal.hide();
            
            // Show success message
            showSuccessModal('Materia actualizada', result.message);
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            if (result.errors) {
                showErrors('edit', result.errors);
            } else {
                showErrorModal('Error', result.message || 'No se pudo actualizar la materia nivelación');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorModal('Error de conexión', 'No se pudo conectar con el servidor');
    }
});

// Delete leveling - show confirmation
function deleteLeveling(id, name, code) {
    // Check if it's a temporary subject
    if (String(id).startsWith('temp_')) {
        const subjectCode = String(id).replace('temp_', '');
        deleteTemporaryLeveling(subjectCode, name);
        return;
    }
    
    // For official subjects, mark as removed in localStorage (preview mode)
    // Store the info for confirmation modal
    deleteLevelingId = id;
    deleteLevelingCode = code;
    deleteLevelingName = name;
    
    document.getElementById('delete_name').textContent = name;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Confirm delete
document.getElementById('confirmDelete').addEventListener('click', async function() {
    if (!deleteLevelingId) return;
    
    // Mark as removed in localStorage (preview mode)
    markLevelingAsRemoved(deleteLevelingCode, deleteLevelingName);
    
    // Close delete modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
    modal.hide();
    
    // Show info modal
    showInfoModal(
        'Materia marcada para eliminación',
        `La materia "${deleteLevelingName}" ha sido marcada para eliminación. Los cambios serán aplicados cuando guardes la simulación.`
    );
    
    // Reload page after 2 seconds to show preview
    setTimeout(() => {
        window.location.reload();
    }, 2000);
    
    // Reset
    deleteLevelingId = null;
    deleteLevelingCode = null;
    deleteLevelingName = null;
});

// Toggle active status
async function toggleStatus(id) {
    try {
        const response = await fetch(`/leveling-subjects/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessModal('Estado actualizado', result.message);
            
            // Reload page after 1 second
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showErrorModal('Error', result.message || 'No se pudo cambiar el estado');
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorModal('Error de conexión', 'No se pudo conectar con el servidor');
    }
}

// Helper: Clear form errors
function clearErrors(prefix) {
    const form = document.getElementById(`${prefix}Form`);
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

// Helper: Show form errors
function showErrors(prefix, errors) {
    for (const [field, messages] of Object.entries(errors)) {
        const input = document.getElementById(`${prefix}_${field}`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
            }
        }
    }
}

// Helper: Show success modal
function showSuccessModal(title, message) {
    const modalHtml = `
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>
                            ${title}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing success modal
    const existing = document.getElementById('successModal');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
    
    // Clean up after modal is hidden
    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Helper: Show error modal
function showErrorModal(title, message) {
    const modalHtml = `
        <div class="modal fade" id="errorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${title}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove any existing error modal
    const existing = document.getElementById('errorModal');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('errorModal'));
    modal.show();
    
    // Clean up after modal is hidden
    document.getElementById('errorModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Clear forms when modals are hidden
document.getElementById('createModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('createForm').reset();
    clearErrors('create');
});

/**
 * Mark a leveling subject as removed in localStorage (preview mode)
 * This will be applied permanently when the user saves the simulation
 */
function markLevelingAsRemoved(code, name) {
    try {
        // Load current changes
        const stored = localStorage.getItem(STORAGE_KEY);
        let data = stored ? JSON.parse(stored) : {
            changes: [],
            timestamp: new Date().toISOString(),
            curriculumId: CURRICULUM_ID
        };
        
        // Check if already marked as removed
        const existingIndex = data.changes.findIndex(c => 
            c.type === 'removed' && c.subject_code === code
        );
        
        if (existingIndex !== -1) {
            // Already marked, do nothing
            return;
        }
        
        // Add removal change
        data.changes.push({
            type: 'removed',
            subject_code: code,
            subject_name: name,
            old_value: null,
            new_value: null,
            timestamp: new Date().toISOString()
        });
        
        data.timestamp = new Date().toISOString();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        
        // Dispatch custom event for real-time sync
        const removeEvent = new CustomEvent('levelingSubjectRemoved', {
            detail: {
                code: code,
                name: name
            }
        });
        window.dispatchEvent(removeEvent);
        
    } catch (error) {
        console.error('Error marking leveling as removed:', error);
    }
}

/**
 * Update simulation localStorage when an official leveling subject is edited
 * This enables bidirectional synchronization between /leveling-subjects and /simulation
 */
function updateSimulationFromLeveling(leveling) {
    
    try {
        // First, try to update temporary changes in localStorage
        const stored = localStorage.getItem(STORAGE_KEY);
        let updatedLocalStorage = false;
        
        if (stored) {
            const data = JSON.parse(stored);
            
            if (data.curriculumId === CURRICULUM_ID) {
                let changes = data.changes || [];
                
                const changeIndex = changes.findIndex(c => 
                    c.type === 'added' && 
                    c.subject_code === leveling.code &&
                    c.new_value?.type === 'nivelacion'
                );
                
                if (changeIndex !== -1) {
                    changes[changeIndex].new_value = {
                        ...changes[changeIndex].new_value,
                        name: leveling.name,
                        credits: parseInt(leveling.credits) || 0,
                        classroomHours: parseInt(leveling.classroom_hours) || 0,
                        studentHours: parseInt(leveling.student_hours) || 0,
                        description: leveling.description || '',
                        semester: changes[changeIndex].new_value.semester,
                        prerequisites: changes[changeIndex].new_value.prerequisites || [],
                        type: 'nivelacion',
                        isRequired: changes[changeIndex].new_value.isRequired
                    };
                    
                    changes[changeIndex].subject_name = leveling.name;
                    changes[changeIndex].timestamp = new Date().toISOString();
                    
                    data.changes = changes;
                    data.timestamp = new Date().toISOString();
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
                    
                    updatedLocalStorage = true;
                }
            }
        }
        
        // Dispatch custom event to update simulation view in real-time
        const updateEvent = new CustomEvent('levelingSubjectUpdated', {
            detail: {
                code: leveling.code,
                name: leveling.name,
                credits: parseInt(leveling.credits) || 0,
                classroomHours: parseInt(leveling.classroom_hours) || 0,
                studentHours: parseInt(leveling.student_hours) || 0,
                description: leveling.description || '',
                updatedLocalStorage: updatedLocalStorage
            }
        });
        
        window.dispatchEvent(updateEvent);
        
    } catch (error) {
        console.error('Error al actualizar simulación desde nivelación:', error);
    }
}

document.getElementById('editModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('editForm').reset();
    clearErrors('edit');
});
