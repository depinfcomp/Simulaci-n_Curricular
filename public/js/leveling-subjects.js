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
    const code = document.getElementById('edit_code').value;
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Clear previous errors
    clearErrors('edit');
    
    // Check if this is an official leveling subject
    const row = document.querySelector(`tr[data-leveling-code="${code}"]`);
    const isOfficial = row && row.querySelector('.badge.bg-primary');
    
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
            // If it's an official subject, save to localStorage for sync with simulation
            if (isOfficial && result.leveling) {
                saveEditToLocalStorage(result.leveling);
            }
            
            // Try to update simulation if this subject exists
            if (result.leveling) {
                updateSimulationFromLeveling(result.leveling);
            }
            
            // Apply yellow styling locally for official subjects
            if (isOfficial && row) {
                row.classList.add('table-warning');
                const nameCell = row.querySelector('.leveling-name-cell');
                if (nameCell && !nameCell.querySelector('.edit-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-warning text-dark ms-2 edit-badge';
                    badge.innerHTML = '<i class="fas fa-edit"></i> Editado';
                    nameCell.querySelector('div').appendChild(badge);
                }
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            modal.hide();
            
            // Show success message (don't reload, keep the yellow styling)
            showSuccessModal('Materia actualizada', 
                isOfficial 
                    ? result.message + ' (Cambio marcado como temporal hasta guardar la malla)'
                    : result.message
            );
            
            // Only reload for non-official subjects
            if (!isOfficial) {
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
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
 * Save edit to localStorage for sync with simulation page
 * Only for official leveling subjects
 */
function saveEditToLocalStorage(leveling) {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        let data;
        
        if (stored) {
            data = JSON.parse(stored);
            if (!data.changes) {
                data.changes = [];
            }
        } else {
            data = {
                changes: [],
                timestamp: new Date().toISOString(),
                curriculumId: CURRICULUM_ID
            };
        }
        
        // Check if there's already an edit for this subject
        const existingIndex = data.changes.findIndex(c => 
            c.type === 'edit' && c.subject_code === leveling.code
        );
        
        const editChange = {
            type: 'edit',
            subject_code: leveling.code,
            subject_name: leveling.name,
            old_value: null, // We don't track old values for now
            new_value: {
                name: leveling.name,
                credits: parseInt(leveling.credits) || 0,
                classroomHours: parseInt(leveling.classroom_hours) || 0,
                studentHours: parseInt(leveling.student_hours) || 0,
                description: leveling.description || ''
            },
            timestamp: new Date().toISOString()
        };
        
        if (existingIndex !== -1) {
            // Update existing edit
            data.changes[existingIndex] = editChange;
        } else {
            // Add new edit
            data.changes.push(editChange);
        }
        
        data.timestamp = new Date().toISOString();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        
        console.log('Edit saved to localStorage:', editChange);
        
    } catch (error) {
        console.error('Error saving edit to localStorage:', error);
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

// On page load, apply visual styles for edited and removed subjects
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== LEVELING SUBJECTS PAGE LOADED ===');
    console.log('Checking for stored changes in localStorage...');
    
    const storedChanges = localStorage.getItem('simulation_temporary_changes');
    console.log('Raw localStorage data:', storedChanges);
    
    if (!storedChanges) {
        console.log('No stored changes found');
        return;
    }
    
    try {
        const data = JSON.parse(storedChanges);
        console.log('Parsed data:', data);
        console.log('Number of changes:', data.changes ? data.changes.length : 0);
        
        if (!data || !data.changes) {
            console.log('No changes array found');
            return;
        }
        
        // Log all available rows
        const allRows = document.querySelectorAll('tr[data-leveling-code]');
        console.log('Total rows with data-leveling-code:', allRows.length);
        allRows.forEach(row => {
            console.log('  Row code:', row.dataset.levelingCode);
        });
        
        // Process each change
        data.changes.forEach((change, index) => {
            console.log(`\n--- Processing change ${index} ---`);
            console.log('Type:', change.type);
            console.log('Subject code:', change.subject_code);
            console.log('New value:', change.new_value);
            
            const row = document.querySelector(`tr[data-leveling-code="${change.subject_code}"]`);
            console.log('Row found:', !!row);
            
            if (!row) {
                console.log('Row NOT found for code:', change.subject_code);
                return;
            }
            
            if (change.type === 'removed') {
                console.log('Applying REMOVED styles...');
                row.classList.add('table-danger');
                row.style.opacity = '0.6';
                row.style.textDecoration = 'line-through';
            } else if (change.type === 'edit') {
                console.log('Applying EDIT styles...');
                row.classList.add('table-warning');
                
                // Update values
                if (change.new_value) {
                    const nameCell = row.querySelector('.leveling-name');
                    const creditsCell = row.querySelector('.leveling-credits');
                    const classroomCell = row.querySelector('.leveling-classroom-hours');
                    const studentCell = row.querySelector('.leveling-student-hours');
                    
                    console.log('Cells found:', {
                        name: !!nameCell,
                        credits: !!creditsCell,
                        classroom: !!classroomCell,
                        student: !!studentCell
                    });
                    
                    if (nameCell && change.new_value.name) {
                        console.log('Updating name to:', change.new_value.name);
                        nameCell.textContent = change.new_value.name;
                    }
                    if (creditsCell && change.new_value.credits) {
                        console.log('Updating credits to:', change.new_value.credits);
                        creditsCell.textContent = change.new_value.credits;
                    }
                    if (classroomCell && change.new_value.classroom_hours !== undefined) {
                        console.log('Updating classroom hours to:', change.new_value.classroom_hours);
                        classroomCell.textContent = change.new_value.classroom_hours + 'h';
                    }
                    if (studentCell && change.new_value.student_hours !== undefined) {
                        console.log('Updating student hours to:', change.new_value.student_hours);
                        studentCell.textContent = change.new_value.student_hours + 'h';
                    }
                }
                
                // Add edit badge
                const codeCell = row.querySelector('td:first-child');
                if (codeCell && !codeCell.querySelector('.edit-badge')) {
                    console.log('Adding edit badge...');
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-warning text-dark badge-sm mt-1 edit-badge';
                    badge.innerHTML = '<i class="fas fa-edit"></i> Editado';
                    badge.title = 'Esta materia tiene cambios pendientes en la simulación';
                    codeCell.appendChild(document.createElement('br'));
                    codeCell.appendChild(badge);
                    console.log('Edit badge added');
                }
            }
        });
        
        console.log('=== FINISHED APPLYING STORED CHANGES ===');
    } catch (error) {
        console.error('Error applying change preview styles:', error);
    }
});

// Listen for updates from simulation page (cross-tab communication)
window.addEventListener('storage', function(e) {
    console.log('\n=== STORAGE EVENT DETECTED ===');
    console.log('Key:', e.key);
    console.log('Old value:', e.oldValue);
    console.log('New value:', e.newValue);
    
    if (e.key === 'simulation_temporary_changes') {
        console.log('Simulation changes detected!');
        
        // Check if localStorage was cleared (reset all changes)
        if (!e.newValue || e.newValue === 'null') {
            console.log('localStorage was cleared - removing all preview styles');
            
            // Remove all yellow styling
            document.querySelectorAll('.table-warning').forEach(row => {
                row.classList.remove('table-warning');
            });
            
            // Remove all edit badges
            document.querySelectorAll('.edit-badge').forEach(badge => {
                badge.remove();
            });
            
            // Remove all removal styling (red)
            document.querySelectorAll('.table-danger').forEach(row => {
                row.classList.remove('table-danger');
            });
            
            // Remove all removal badges
            document.querySelectorAll('.badge-danger').forEach(badge => {
                if (badge.textContent.includes('Eliminado')) {
                    badge.remove();
                }
            });
            
            console.log('All preview styles removed');
            return;
        }
        
        try {
            const data = JSON.parse(e.newValue);
            console.log('Parsed new data:', data);
            
            if (!data || !data.changes) {
                console.log('No changes in new data');
                return;
            }
            
            console.log('Number of changes:', data.changes.length);
            
            // Clear existing styles first
            console.log('Clearing existing styles...');
            document.querySelectorAll('tr[data-leveling-code]').forEach(row => {
                row.classList.remove('table-warning', 'table-danger');
                row.style.opacity = '';
                row.style.textDecoration = '';
                const editBadge = row.querySelector('.edit-badge');
                if (editBadge) {
                    editBadge.remove();
                    const br = row.querySelector('td:first-child br:last-of-type');
                    if (br) br.remove();
                }
            });
            
            // Reapply all styles and update data
            data.changes.forEach((change, index) => {
                console.log(`\n--- Processing change ${index} from storage event ---`);
                console.log('Type:', change.type);
                console.log('Code:', change.subject_code);
                
                const row = document.querySelector(`tr[data-leveling-code="${change.subject_code}"]`);
                console.log('Row found:', !!row);
                
                if (!row) return;
                
                if (change.type === 'edit') {
                    console.log('Applying EDIT styles and updating data...');
                    row.classList.add('table-warning');
                    
                    if (change.new_value) {
                        updateLevelingTableRow(change.subject_code, change.new_value);
                    }
                    
                    // Add badge
                    const codeCell = row.querySelector('td:first-child');
                    if (codeCell && !codeCell.querySelector('.edit-badge')) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-warning text-dark badge-sm mt-1 edit-badge';
                        badge.innerHTML = '<i class="fas fa-edit"></i> Editado';
                        badge.title = 'Esta materia tiene cambios pendientes en la simulación';
                        codeCell.appendChild(document.createElement('br'));
                        codeCell.appendChild(badge);
                    }
                } else if (change.type === 'removed') {
                    console.log('Applying REMOVED styles...');
                    row.classList.add('table-danger');
                    row.style.opacity = '0.6';
                    row.style.textDecoration = 'line-through';
                }
            });
            
            console.log('=== FINISHED PROCESSING STORAGE EVENT ===');
        } catch (error) {
            console.error('Error processing storage event:', error);
        }
    } else {
        console.log('Storage event for different key, ignoring');
    }
});

// Function to update a leveling subject row in the table
function updateLevelingTableRow(code, data) {
    console.log('updateLevelingTableRow called for:', code);
    console.log('Data to update:', data);
    
    const row = document.querySelector(`tr[data-leveling-code="${code}"]`);
    if (!row) {
        console.log('Row not found in updateLevelingTableRow');
        return;
    }
    
    // Update name
    if (data.name) {
        const nameElement = row.querySelector('.leveling-name');
        console.log('Name element found:', !!nameElement);
        if (nameElement) {
            nameElement.textContent = data.name;
            console.log('Name updated to:', data.name);
        }
    }
    
    // Update credits
    if (data.credits) {
        const creditsElement = row.querySelector('.leveling-credits');
        console.log('Credits element found:', !!creditsElement);
        if (creditsElement) {
            creditsElement.textContent = data.credits;
            console.log('Credits updated to:', data.credits);
        }
    }
    
    // Update classroom hours
    if (data.classroom_hours !== undefined) {
        const classroomElement = row.querySelector('.leveling-classroom-hours');
        console.log('Classroom element found:', !!classroomElement);
        if (classroomElement) {
            classroomElement.textContent = data.classroom_hours + 'h';
            console.log('Classroom hours updated to:', data.classroom_hours);
        }
    }
    
    // Update student hours
    if (data.student_hours !== undefined) {
        const studentElement = row.querySelector('.leveling-student-hours');
        console.log('Student element found:', !!studentElement);
        if (studentElement) {
            studentElement.textContent = data.student_hours + 'h';
            console.log('Student hours updated to:', data.student_hours);
        }
    }
    
    // Update description
    if (data.description !== undefined) {
        const descElement = row.querySelector('.leveling-description');
        console.log('Description element found:', !!descElement);
        if (descElement && data.description) {
            descElement.textContent = data.description.substring(0, 60) + (data.description.length > 60 ? '...' : '');
            console.log('Description updated');
        }
    }
}
