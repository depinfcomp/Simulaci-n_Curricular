/**
 * Convalidation Show Initialization Module
 * 
 * Handles initialization and reset functionality for the convalidation show page.
 * Sets up global routes, manages reset operations, and controls completion modal display.
 * 
 * Features:
 * - Global routes configuration
 * - Reset convalidations with confirmation
 * - Completion modal management (shown once per session)
 * - Loading states during operations
 * 
 * @version 1.0.0
 */

/**
 * Initialize the convalidation show page
 * @param {Object} config - Configuration object
 * @param {Object} config.routes - Routes for convalidation operations
 * @param {string} config.routes.store - Store convalidation route
 * @param {string} config.routes.destroy - Destroy convalidation route (with :id placeholder)
 * @param {string} config.routes.suggestions - Suggestions route
 * @param {string} config.routes.export - Export route
 * @param {string} config.routes.bulkConvalidation - Bulk convalidation route
 * @param {string} config.routes.reset - Reset convalidations route
 * @param {string} config.csrfToken - CSRF token for requests
 * @param {number} config.externalCurriculumId - External curriculum ID
 * @param {number} config.completionPercentage - Completion percentage (0-100)
 * @param {boolean} config.fromSimulation - Whether this convalidation is from simulation
 */
function initConvalidationShow(config) {
    // Set global variables for other modules
    window.convalidationRoutes = config.routes;
    window.csrfToken = config.csrfToken;
    window.externalCurriculumId = config.externalCurriculumId;

    setupCompletionModal(
        config.completionPercentage,
        config.fromSimulation,
        config.externalCurriculumId
    );
}

/**
 * Setup and potentially display completion modal
 * Shows modal if convalidation is 100% complete, from simulation, and not shown before
 * @private
 * @param {number} completionPercentage - Completion percentage (0-100)
 * @param {boolean} fromSimulation - Whether this convalidation is from simulation
 * @param {number} externalCurriculumId - External curriculum ID for session storage key
 */
function setupCompletionModal(completionPercentage, fromSimulation, externalCurriculumId) {
    document.addEventListener('DOMContentLoaded', function() {
        const hasShownModal = sessionStorage.getItem('completion_modal_shown_' + externalCurriculumId);
        
        console.log('Completion check:', {
            percentage: completionPercentage,
            fromSimulation: fromSimulation,
            hasShownModal: hasShownModal
        });
        
        // Show modal if 100% complete, from simulation, and not shown before
        if (completionPercentage >= 100 && fromSimulation && !hasShownModal) {
            const modal = new bootstrap.Modal(document.getElementById('completionSuccessModal'));
            modal.show();
            
            // Mark as shown to prevent showing again
            sessionStorage.setItem('completion_modal_shown_' + externalCurriculumId, 'true');
        }
    });
}

/**
 * Show confirmation modal for resetting convalidations
 * Opens the reset confirmation modal
 */
function confirmResetConvalidations() {
    const modal = new bootstrap.Modal(document.getElementById('resetConvalidationsModal'));
    modal.show();
}

/**
 * Execute reset convalidations operation
 * Sends request to reset all convalidations and reloads page on success
 */
function executeResetConvalidations() {
    const btn = document.getElementById('confirm_reset_btn');
    const originalHtml = btn.innerHTML;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Restableciendo...';

    fetch(window.convalidationRoutes.reset, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            bootstrap.Modal.getInstance(document.getElementById('resetConvalidationsModal')).hide();
            
            // Reload page directly without alert
            location.reload();
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al restablecer las convalidaciones: ' + error.message);
        
        // Restore button
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
