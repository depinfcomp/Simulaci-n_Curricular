// Debug script to test drag and drop functionality
// Open browser console and run this to test

function testDragAndDrop() {
    console.log('=== Testing Drag and Drop ===');
    
    // Test 1: Check if elements exist
    const subjectCards = document.querySelectorAll('.subject-card');
    const semesterColumns = document.querySelectorAll('.semester-column');
    
    console.log('Subject cards found:', subjectCards.length);
    console.log('Semester columns found:', semesterColumns.length);
    
    // Test 2: Check if elements have required attributes
    const firstCard = subjectCards[0];
    if (firstCard) {
        console.log('First card attributes:');
        console.log('- draggable:', firstCard.draggable);
        console.log('- data-subject-id:', firstCard.dataset.subjectId);
        console.log('- data-prerequisites:', firstCard.dataset.prerequisites);
        console.log('- data-semester:', firstCard.dataset.semester);
        console.log('- drag handlers:', {
            onDragStart: typeof firstCard.ondragstart === 'function',
            onDragEnd: typeof firstCard.ondragend === 'function'
        });
    }
    
    const firstColumn = semesterColumns[0];
    if (firstColumn) {
        console.log('First column attributes:');
        console.log('- data-semester:', firstColumn.dataset.semester);
        console.log('- has subject-list:', firstColumn.querySelector('.subject-list') !== null);
    }
    
    // Test 3: Check drag event listeners
    if (firstCard) {
        console.log('First card drag properties:');
        console.log('- draggable:', firstCard.draggable);
        console.log('- has ondragstart:', typeof firstCard.ondragstart);
        console.log('- has ondragend:', typeof firstCard.ondragend);
    }
    
    if (firstColumn) {
        console.log('First column drop properties:');
        console.log('- has ondragover:', typeof firstColumn.ondragover);
        console.log('- has ondrop:', typeof firstColumn.ondrop);
    }
    
    return { subjectCards, semesterColumns };
}

// Additional debug functions
function showAllSubjectData() {
    const subjects = document.querySelectorAll('.subject-card');
    const data = Array.from(subjects).map(subject => ({
        id: subject.dataset.subjectId,
        name: subject.querySelector('.subject-name')?.textContent,
        semester: subject.dataset.semester,
        prerequisites: subject.dataset.prerequisites,
        draggable: subject.draggable
    }));
    
    console.table(data);
    return data;
}

function cleanupModals() {
    console.log('Starting modal cleanup...');
    
    // Find all modals and backdrops
    const modals = document.querySelectorAll('.modal');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    
    console.log(`Found ${modals.length} modals and ${backdrops.length} backdrops`);
    
    // Clean up modal instances first
    modals.forEach((modal, index) => {
        const instance = bootstrap.Modal.getInstance(modal);
        if (instance) {
            try {
                instance.dispose();
                console.log(`Modal ${index + 1} instance disposed`);
            } catch (e) {
                console.warn(`Error disposing modal ${index + 1}:`, e);
            }
        }
        modal.remove();
    });
    
    // Clean up backdrops with more thorough approach
    backdrops.forEach((backdrop, index) => {
        try {
            backdrop.remove();
            console.log(`Backdrop ${index + 1} removed`);
        } catch (e) {
            console.warn(`Error removing backdrop ${index + 1}:`, e);
        }
    });
    
    // Additional cleanup: search for any remaining backdrops by class variations
    const remainingBackdrops = document.querySelectorAll('.modal-backdrop, .modal-backdrop.fade, .modal-backdrop.show, .modal-backdrop.fade.show');
    if (remainingBackdrops.length > 0) {
        console.log(`Found ${remainingBackdrops.length} additional backdrops to clean`);
        remainingBackdrops.forEach((backdrop, index) => {
            try {
                backdrop.remove();
                console.log(`Additional backdrop ${index + 1} removed`);
            } catch (e) {
                console.warn(`Error removing additional backdrop ${index + 1}:`, e);
            }
        });
    }
    
    // Clean up body classes and styles
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
    document.body.style.overflow = '';
    
    // Force cleanup of any remaining modal-related classes
    const bodyClasses = document.body.className.split(' ').filter(cls => !cls.includes('modal'));
    document.body.className = bodyClasses.join(' ');
    
    console.log('Modal cleanup completed');
}

// Check for orphaned backdrops
function checkBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const modals = document.querySelectorAll('.modal');
    
    console.log('Backdrop Analysis:');
    console.log(`Found ${backdrops.length} backdrops and ${modals.length} modals`);
    
    if (backdrops.length > 0) {
        backdrops.forEach((backdrop, index) => {
            console.log(`Backdrop ${index + 1}:`, {
                className: backdrop.className,
                display: backdrop.style.display,
                visibility: backdrop.style.visibility,
                opacity: backdrop.style.opacity
            });
        });
    }
    
    if (modals.length > 0) {
        modals.forEach((modal, index) => {
            const instance = bootstrap.Modal.getInstance(modal);
            console.log(`Modal ${index + 1}:`, {
                id: modal.id,
                className: modal.className,
                hasInstance: !!instance,
                display: modal.style.display
            });
        });
    }
    
    // Check body classes
    const bodyClasses = document.body.className.split(' ').filter(cls => cls.includes('modal'));
    if (bodyClasses.length > 0) {
        console.log('Body modal classes:', bodyClasses);
    }
    
    return { backdrops, modals };
}

// Make functions available globally
window.testDragAndDrop = testDragAndDrop;
window.showAllSubjectData = showAllSubjectData;
window.cleanupModals = cleanupModals;
window.checkBackdrops = checkBackdrops;
