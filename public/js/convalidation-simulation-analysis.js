/**
 * Convalidation Simulation Analysis
 * Handles creation of simulations based on convalidation results
 */

let createRoute;
let csrfToken;
let minLevelingCredits;

/**
 * Initialize the module
 * @param {Object} config - Configuration object
 * @param {string} config.createRoute - Route for creating simulation
 * @param {string} config.csrfToken - CSRF token
 * @param {number} config.minLevelingCredits - Minimum leveling credits required
 */
function initSimulationAnalysis(config) {
    createRoute = config.createRoute;
    csrfToken = config.csrfToken;
    minLevelingCredits = config.minLevelingCredits || 0;
    
    // Setup leveling credits validation
    setupLevelingValidation();
}

/**
 * Setup validation for leveling credits input
 */
function setupLevelingValidation() {
    document.addEventListener('DOMContentLoaded', function() {
        const levelingInput = document.getElementById('leveling_credits');
        
        if (levelingInput) {
            levelingInput.addEventListener('change', function() {
                if (parseInt(this.value) < minLevelingCredits) {
                    alert(`Los créditos de nivelación no pueden ser menores a ${minLevelingCredits}`);
                    this.value = minLevelingCredits;
                }
            });
        }
    });
}

/**
 * Create a new simulation based on convalidation data
 * @param {number} externalCurriculumId - External curriculum ID
 */
function createSimulation(externalCurriculumId) {
    const form = document.getElementById('simulationForm');
    const simulationName = document.getElementById('simulation_name').value;
    const levelingCredits = document.getElementById('leveling_credits').value;
    const description = document.getElementById('description').value;

    // Validation
    if (!simulationName) {
        alert('Por favor ingrese un nombre para la simulación');
        return;
    }

    if (parseInt(levelingCredits) < minLevelingCredits) {
        alert(`Los créditos de nivelación no pueden ser menores a ${minLevelingCredits}`);
        return;
    }

    // Get button reference (from event or fallback)
    const btn = event?.target || document.querySelector('button[onclick*="createSimulation"]');
    const originalHtml = btn.innerHTML;
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';

    // Send request
    fetch(createRoute, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            external_curriculum_id: externalCurriculumId,
            simulation_name: simulationName,
            description: description,
            leveling_credits: levelingCredits
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Simulación creada exitosamente');
            
            // Redirect after brief delay
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    location.reload();
                }
            }, 1500);
        } else {
            showToast('error', data.error || 'Error al crear simulación');
            // Restore button
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error creating simulation:', error);
        showToast('error', 'Error de conexión');
        // Restore button
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

/**
 * Show toast notification
 * @param {string} type - Toast type: 'success', 'error', 'warning', 'info'
 * @param {string} message - Message to display
 */
function showToast(type, message) {
    const colors = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white ${colors[type] || 'bg-secondary'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    // Get or create toast container
    const container = document.getElementById('toast-container') || createToastContainer();
    container.appendChild(toast);

    // Show toast using Bootstrap
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();

    // Remove toast from DOM after hidden
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

/**
 * Create toast container if it doesn't exist
 * @returns {HTMLElement} Toast container element
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
