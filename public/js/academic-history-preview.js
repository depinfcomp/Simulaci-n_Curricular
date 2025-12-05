/**
 * Academic History Preview
 * Handles column mapping and import processing
 */

let importId;
let csrfToken;

/**
 * Initialize the module
 */
function initAcademicHistoryPreview(config) {
    importId = config.importId;
    csrfToken = config.csrfToken;
    
    // Auto-save mapping on change
    document.querySelectorAll('.mapping-select').forEach(select => {
        select.addEventListener('change', saveMapping);
    });
}

/**
 * Save column mapping automatically
 */
async function saveMapping() {
    const form = document.getElementById('mappingForm');
    const formData = new FormData(form);
    
    const mapping = {};
    formData.forEach((value, key) => {
        const field = key.replace('mapping[', '').replace(']', '');
        if (value) {
            mapping[field] = parseInt(value);
        }
    });
    
    try {
        const response = await fetch(`/academic-history/${importId}/mapping`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ mapping })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('Error saving mapping:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

/**
 * Process the import after validation
 */
async function procesarImportacion() {
    // Validate required fields
    const requiredFields = ['student_code', 'subject_code', 'subject_name'];
    const missing = [];
    
    requiredFields.forEach(field => {
        const select = document.getElementById(`mapping_${field}`);
        if (!select.value) {
            missing.push(select.previousElementSibling.textContent.trim());
        }
    });
    
    if (missing.length > 0) {
        showAlertModal(
            `Los siguientes campos obligatorios no están mapeados:\n\n${missing.join('\n')}`,
            'warning',
            'Campos Requeridos'
        );
        return;
    }
    
    // Save mapping first
    await saveMapping();
    
    // Show processing modal
    const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
    processingModal.show();
    
    try {
        const response = await fetch(`/academic-history/${importId}/process`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        processingModal.hide();
        
        if (data.success) {
            showAlertModal(
                `Importación completada exitosamente!\n\n` +
                `• Registros exitosos: ${data.data.successful}\n` +
                `• Registros fallidos: ${data.data.failed}\n\n` +
                `Serás redirigido al listado de importaciones.`,
                'success',
                'Procesamiento Completado'
            );
            
            setTimeout(() => {
                window.location.href = '/academic-history';
            }, 3000);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    } catch (error) {
        processingModal.hide();
        showAlertModal(
            `Error al procesar la importación:\n\n${error.message}`,
            'error',
            'Error de Procesamiento'
        );
    }
}

/**
 * Show alert modal with customizable type and message
 * @param {string} message - Message to display
 * @param {string} type - Modal type: 'error', 'warning', 'info', 'success'
 * @param {string} title - Optional custom title
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
                        <p class="mb-0" style="white-space: pre-line;">${message}</p>
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
    
    // Remove existing modal if any
    const existingModal = document.getElementById('alertModal');
    if (existingModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
        if (existingModalInstance) existingModalInstance.dispose();
        existingModal.remove();
    }
    
    // Add and show new modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalElement = document.getElementById('alertModal');
    const modal = new bootstrap.Modal(modalElement);
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalElement.remove();
    });
    
    modal.show();
}
