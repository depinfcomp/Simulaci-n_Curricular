// Academic History Import - Index Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadForm');
    const uploadModal = document.getElementById('uploadModal');
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', handleUpload);
    }
});

/**
 * Handle file upload
 */
async function handleUpload(e) {
    e.preventDefault();
    
    const form = e.target;
    const fileInput = document.getElementById('file');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    
    if (!fileInput.files[0]) {
        showAlertModal('Por favor seleccione un archivo', 'warning', 'Archivo Requerido');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    // Show progress
    uploadBtn.disabled = true;
    uploadProgress.style.display = 'block';
    
    try {
        const response = await fetch('/academic-history/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('El servidor no respondió correctamente. Por favor, verifica el formato del archivo.');
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Close upload modal
            const modalInstance = bootstrap.Modal.getInstance(uploadModal);
            modalInstance.hide();
            
            // Show success with statistics
            const stats = data.stats;
            const message = `
                <div class="text-start">
                    <h5 class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Importación Completada</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-users text-primary me-2"></i><strong>${stats.students}</strong> estudiantes procesados</li>
                        <li class="mb-2"><i class="fas fa-history text-info me-2"></i><strong>${stats.historical}</strong> registros históricos importados</li>
                        <li class="mb-2"><i class="fas fa-graduation-cap text-success me-2"></i><strong>${stats.current}</strong> materias actuales registradas</li>
                        <li><i class="fas fa-database text-secondary me-2"></i><strong>${stats.total}</strong> total de estudiantes en el sistema</li>
                    </ul>
                </div>
            `;
            
            showAlertModal(message, 'success', 'Historia Académica Importada');
            
            // Reload after showing message
            setTimeout(() => window.location.reload(), 3000);
        } else {
            throw new Error(data.message || 'Error al cargar el archivo');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showAlertModal(`Error al cargar el archivo: ${error.message}`, 'error', 'Error de Carga');
        uploadBtn.disabled = false;
        uploadProgress.style.display = 'none';
    }
}

/**
 * Delete an import
 */
function deleteImport(importId) {
    showConfirmModal(
        '¿Está seguro de que desea eliminar esta importación?\n\n⚠️ ADVERTENCIA: Se eliminarán todos los registros asociados.\n\nEsta acción NO se puede deshacer.',
        async function() {
            try {
                const response = await fetch(`/academic-history/${importId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlertModal(data.message, 'success', 'Importación Eliminada');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Error al eliminar');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showAlertModal(`Error al eliminar la importación: ${error.message}`, 'error', 'Error al Eliminar');
            }
        },
        'danger',
        'Eliminar Importación',
        'Sí, eliminar',
        'Cancelar'
    );
}

/**
 * Show alert modal (reusable function from simulation.js)
 */
function showAlertModal(message, type = 'info', title = null) {
    const typeConfig = {
        error: { icon: 'fas fa-exclamation-circle', color: 'danger', defaultTitle: 'Error' },
        warning: { icon: 'fas fa-exclamation-triangle', color: 'warning', defaultTitle: 'Advertencia' },
        info: { icon: 'fas fa-info-circle', color: 'info', defaultTitle: 'Información' },
        success: { icon: 'fas fa-check-circle', color: 'success', defaultTitle: 'Éxito' }
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
    
    const existingModal = document.getElementById('alertModal');
    if (existingModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
        if (existingModalInstance) existingModalInstance.dispose();
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('alertModal');
    const modal = new bootstrap.Modal(modalElement);
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalElement.remove();
    });
    
    modal.show();
}

/**
 * Show confirm modal (reusable function from simulation.js)
 */
function showConfirmModal(message, onConfirm, type = 'warning', title = null, confirmText = 'Aceptar', cancelText = 'Cancelar') {
    const typeConfig = {
        danger: { icon: 'fas fa-exclamation-triangle', defaultTitle: 'Confirmar Acción' },
        warning: { icon: 'fas fa-exclamation-circle', defaultTitle: 'Confirmación' },
        info: { icon: 'fas fa-question-circle', defaultTitle: 'Confirmar' },
        primary: { icon: 'fas fa-check-circle', defaultTitle: 'Confirmación' }
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
    
    const existingModal = document.getElementById('confirmModal');
    if (existingModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
        if (existingModalInstance) existingModalInstance.dispose();
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('confirmModal');
    const modal = new bootstrap.Modal(modalElement);
    
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
 * Confirm clearing all academic history data
 */
function confirmClearAll() {
    const message = `ADVERTENCIA: Esta acción es IRREVERSIBLE

Esta acción eliminará PERMANENTEMENTE:
- Todos los estudiantes importados
- Todas las materias cursadas (student_subject)
- Todos los historiales académicos (academic_histories)
- Todos los registros de importación

¿Está seguro de que desea continuar?`;

    showConfirmModal(
        message,
        () => {
            // Ask for confirmation word
            const confirmWord = prompt('Para confirmar, escriba "ELIMINAR":');
            if (confirmWord === 'ELIMINAR') {
                clearAllData();
            } else if (confirmWord !== null) {
                showAlertModal('Confirmación incorrecta. No se eliminó ningún dato.', 'warning', 'Cancelado');
            }
        },
        'danger',
        'Eliminar Todas las Historias Académicas',
        'Continuar',
        'Cancelar'
    );
}

/**
 * Clear all academic history data via AJAX
 */
async function clearAllData() {
    try {
        // Show loading indicator
        showAlertModal(
            '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x mb-3"></i><br>Eliminando datos...</div>',
            'warning',
            'Procesando',
            false // Don't show close button
        );

        const response = await fetch('/academic-history/clear-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        // Close loading modal
        const loadingModal = document.getElementById('alertModal');
        if (loadingModal) {
            const modalInstance = bootstrap.Modal.getInstance(loadingModal);
            if (modalInstance) modalInstance.hide();
        }

        if (data.success) {
            const deletedInfo = `
Datos eliminados correctamente:

- Estudiantes: ${data.deleted.students}
- Materias cursadas: ${data.deleted.student_subject}
- Historiales académicos: ${data.deleted.academic_histories}
- Importaciones: ${data.deleted.imports}

La página se recargará automáticamente.
            `;
            
            showAlertModal(deletedInfo, 'success', 'Eliminación Exitosa');
            
            // Reload page after 3 seconds
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else {
            showAlertModal(
                `Error al eliminar los datos:\n${data.message}`,
                'danger',
                'Error'
            );
        }

    } catch (error) {
        console.error('Error clearing data:', error);
        
        // Close loading modal if still open
        const loadingModal = document.getElementById('alertModal');
        if (loadingModal) {
            const modalInstance = bootstrap.Modal.getInstance(loadingModal);
            if (modalInstance) modalInstance.hide();
        }
        
        showAlertModal(
            `Error de conexión: ${error.message}`,
            'danger',
            'Error de Red'
        );
    }
}
