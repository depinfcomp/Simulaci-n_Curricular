/**
 * Convalidation - Synchronization Module
 * Handles synchronization between simulation and convalidation subjects table
 */

console.log('🔵 CONVALIDATION VISUAL SYNC LOADED');

// Constants
const STORAGE_KEY = 'simulation_temporary_changes';
const CURRICULUM_ID = 'simulation';

/**
 * Load temporary changes from localStorage
 */
function loadTemporaryChanges() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) {
            console.log('📭 No localStorage data');
            return null;
        }
        
        const data = JSON.parse(stored);
        console.log('📦 localStorage data:', data);
        
        // Validate curriculum ID
        if (data.curriculumId !== CURRICULUM_ID) {
            console.warn('⚠️ Wrong curriculum ID');
            return null;
        }
        
        console.log('✅ Loaded', data.changes?.length || 0, 'changes');
        return data.changes || [];
    } catch (error) {
        console.error('❌ Error loading changes:', error);
        return null;
    }
}

/**
 * Apply styles to convalidation subjects based on their state in localStorage
 */
function highlightTemporarySubjects() {
    console.log('=== HIGHLIGHT START ===');
    
    const changes = loadTemporaryChanges();
    if (!changes || changes.length === 0) {
        console.log('No changes to apply');
        return;
    }
    
    // Group changes by type
    const changesByType = {
        added: changes.filter(c => c.type === 'added'),
        removed: changes.filter(c => c.type === 'removed'),
        semester: changes.filter(c => c.type === 'semester'),
        edit: changes.filter(c => c.type === 'edit' || c.type === 'prerequisites')
    };
    
    console.log('Changes:', {
        added: changesByType.added.length,
        removed: changesByType.removed.length,
        moved: changesByType.semester.length,
        edited: changesByType.edit.length
    });
    
    // Find ALL semester tabs
    const allTabPanes = document.querySelectorAll('.tab-pane[id^="semester-"]');
    console.log('Found', allTabPanes.length, 'semester tabs');
    
    // Collect all rows from all tabs
    const allRows = [];
    allTabPanes.forEach((tabPane) => {
        const tbody = tabPane.querySelector('table tbody');
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            console.log('Semester', tabPane.id, ':', rows.length, 'rows');
            allRows.push(...Array.from(rows));
        }
    });
    
    console.log('Total rows:', allRows.length);
    
    let applied = 0;
    
    // Process each row
    allRows.forEach((row) => {
        const subjectCode = row.dataset.subjectCode;
        if (!subjectCode) return;
        
        // Check for removed
        if (changesByType.removed.find(c => c.subject_code === subjectCode)) {
            applyRemovedStyleToRow(row);
            console.log('REMOVED:', subjectCode);
            applied++;
            return;
        }
        
        // Check for added
        if (changesByType.added.find(c => c.subject_code === subjectCode)) {
            applyAddedStyleToRow(row);
            console.log('ADDED:', subjectCode);
            applied++;
            return;
        }
        
        // Check for moved
        if (changesByType.semester.find(c => c.subject_code === subjectCode)) {
            applyMovedStyleToRow(row);
            console.log('MOVED:', subjectCode);
            applied++;
            return;
        }
        
        // Check for edited
        if (changesByType.edit.find(c => c.subject_code === subjectCode)) {
            applyEditedStyleToRow(row);
            console.log('EDITED:', subjectCode);
            applied++;
            return;
        }
    });
    
    console.log('Applied styles to', applied, 'rows');
    console.log('=== END ===');
}

// Keep old function name for compatibility
function applyRemovedStyleToRow(row) {
    if (!row) return;
    row.style.opacity = '0.6';
    row.style.backgroundColor = '#f8f9fa';
    row.style.borderLeft = '4px solid #dc3545';
    
    const codeCell = row.querySelector('td:first-child code');
    const nameCell = row.querySelector('td:nth-child(2) h6');
    
    if (codeCell) {
        codeCell.style.textDecoration = 'line-through';
        codeCell.style.color = '#dc3545';
    }
    if (nameCell) {
        nameCell.style.textDecoration = 'line-through';
        nameCell.style.color = '#dc3545';
    }
    
    const actionButtons = row.querySelectorAll('.btn:not(.btn-outline-danger)');
    actionButtons.forEach(btn => btn.style.display = 'none');
}

function applyAddedStyleToRow(row) {
    if (!row) return;
    row.style.borderLeft = '4px solid #28a745';
    row.style.backgroundColor = 'rgba(40, 167, 69, 0.05)';
}

function applyMovedStyleToRow(row) {
    if (!row) return;
    row.style.borderLeft = '4px solid #0dcaf0';
    row.style.backgroundColor = 'rgba(13, 202, 240, 0.05)';
}

function applyEditedStyleToRow(row) {
    if (!row) return;
    row.style.borderLeft = '4px solid #ffc107';
    row.style.backgroundColor = 'rgba(255, 193, 7, 0.05)';
}

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM loaded - initializing visual sync');
    highlightTemporarySubjects();
});

/**
 * Listen for storage changes
 */
window.addEventListener('storage', function(e) {
    if (e.key === STORAGE_KEY) {
        console.log('🔄 Storage changed, refreshing...');
        highlightTemporarySubjects();
    }
});

if (typeof window !== 'undefined') {
    window.highlightTemporarySubjects = highlightTemporarySubjects;
    window.refreshConvalidationVisuals = highlightTemporarySubjects;
}
