// Leveling Subjects Management JavaScript

let deleteLevelingId = null;

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
function deleteLeveling(id, name) {
    // Check if it's a temporary subject
    if (String(id).startsWith('temp_')) {
        const code = String(id).replace('temp_', '');
        deleteTemporaryLeveling(code, name);
        return;
    }
    
    deleteLevelingId = id;
    document.getElementById('delete_name').textContent = name;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Confirm delete
document.getElementById('confirmDelete').addEventListener('click', async function() {
    if (!deleteLevelingId) return;
    
    try {
        const response = await fetch(`/leveling-subjects/${deleteLevelingId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        // Close delete modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
        
        if (result.success) {
            showSuccessModal('Materia eliminada', result.message);
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showErrorModal('Error', result.message || 'No se pudo eliminar la materia');
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorModal('Error de conexión', 'No se pudo conectar con el servidor');
    }
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

document.getElementById('editModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('editForm').reset();
    clearErrors('edit');
});
