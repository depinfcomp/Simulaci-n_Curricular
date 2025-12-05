/**
 * Simulation Index Initialization Module
 * 
 * Handles initialization and debug logging for the simulation index page.
 * Sets up leveling subjects data and performs color/type debugging for subject cards.
 * 
 * Features:
 * - Leveling subjects codes configuration
 * - Subject card type validation
 * - Color scheme debugging
 * - Type distribution analysis
 * 
 * @version 1.0.0
 */

let levelingSubjectsCodes = [];

/**
 * Initialize the simulation index page
 * @param {Object} config - Configuration object
 * @param {Array<string>} config.levelingSubjectsCodes - Array of leveling subject codes
 */
function initSimulationIndex(config) {
    levelingSubjectsCodes = config.levelingSubjectsCodes;
    window.levelingSubjectsCodes = levelingSubjectsCodes;

    setupColorDebug();
}

/**
 * Setup color and type debugging for subject cards
 * Logs information about card types, classes, and styling
 * @private
 */
function setupColorDebug() {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== COLOR DEBUG ===');
        const cards = document.querySelectorAll('.subject-card');
        console.log(`Total cards found: ${cards.length}`);
        
        const typeCount = {};
        cards.forEach(card => {
            const type = card.dataset.type || 'undefined';
            const classes = card.className;
            const hasTypeClass = card.classList.contains('fundamental') || 
                                card.classList.contains('profesional') || 
                                card.classList.contains('optativa_profesional') ||
                                card.classList.contains('optativa_fundamentacion') ||
                                card.classList.contains('nivelacion') ||
                                card.classList.contains('trabajo_grado') ||
                                card.classList.contains('libre_eleccion');
            
            typeCount[type] = (typeCount[type] || 0) + 1;
            
            if (!hasTypeClass) {
                console.warn(`Card ${card.dataset.subjectId} has no type class! Classes: ${classes}`);
            }
        });
        
        console.log('Type distribution:', typeCount);
        console.log('Sample card styles:');
        if (cards.length > 0) {
            const firstCard = cards[0];
            console.log(`  Card: ${firstCard.dataset.subjectId}`);
            console.log(`  Type: ${firstCard.dataset.type}`);
            console.log(`  Classes: ${firstCard.className}`);
            console.log(`  Background: ${window.getComputedStyle(firstCard).background.substring(0, 100)}`);
        }
        console.log('=== END COLOR DEBUG ===');
    });
}
