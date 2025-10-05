let currentExternalSubjectId = null;

function showConvalidationModal(externalSubjectId) {
    currentExternalSubjectId = externalSubjectId;
    
    // Get subject info and show modal
    const row = document.getElementById(`subject-row-${externalSubjectId}`);
    const subjectCode = row.querySelector('code').textContent;
    const subjectName = row.querySelector('h6').textContent;
    const subjectCredits = row.querySelector('.badge').textContent;
    
    document.getElementById('external_subject_id').value = externalSubjectId;
    document.getElementById('external_subject_info').innerHTML = 
        `<strong>${subjectName}</strong> (${subjectCode}) - ${subjectCredits} créditos`;
    
    // Reset form
    document.getElementById('convalidationForm').reset();
    document.getElementById('external_subject_id').value = externalSubjectId;
    document.getElementById('internal_subject_selection').style.display = 'none';
    
    const modal = new bootstrap.Modal(document.getElementById('convalidationModal'));
    modal.show();
}

// Handle convalidation type change
document.querySelectorAll('input[name="convalidation_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const internalSubjectSelection = document.getElementById('internal_subject_selection');
        if (this.value === 'direct') {
            internalSubjectSelection.style.display = 'block';
        } else {
            internalSubjectSelection.style.display = 'none';
        }
    });
});

function saveConvalidation() {
    const formData = new FormData(document.getElementById('convalidationForm'));
    
    // Store current active semester before making the request
    const currentActiveSemester = getCurrentActiveSemester();
    
    fetch('{{ route("convalidation.store-convalidation") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the convalidation display
            updateConvalidationDisplay(currentExternalSubjectId, data.convalidation);
            
            // Update statistics without page reload
            if (data.stats) {
                updateStatistics(data.stats);
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('convalidationModal'));
            modal.hide();
            
            // Restore active semester
            restoreActiveSemester(currentActiveSemester);
            
            // Show success message
            showAlert('success', 'Convalidación guardada exitosamente');
        } else {
            showAlert('danger', data.error || 'Error al guardar la convalidación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error de conexión');
    });
}

function getCurrentActiveSemester() {
    // Find which semester tab is currently active
    const activeTab = document.querySelector('.nav-link.active[data-bs-target^="#semester"]');
    if (activeTab) {
        const href = activeTab.getAttribute('data-bs-target');
        return href.replace('#semester-', '');
    }
    return '1'; // Default to semester 1
}

function restoreActiveSemester(semesterNumber) {
    // Restore the active semester tab
    setTimeout(() => {
        const targetTab = document.querySelector(`[data-bs-target="#semester-${semesterNumber}"]`);
        if (targetTab) {
            const tab = new bootstrap.Tab(targetTab);
            tab.show();
        }
    }, 100);
}

function updateStatistics(stats) {
    // Update convalidation progress
    const progressBar = document.getElementById('convalidation-progress');
    if (progressBar) {
        progressBar.style.width = `${stats.completion_percentage}%`;
        progressBar.textContent = `${stats.completion_percentage.toFixed(1)}%`;
    }
    
    // Update counts
    const directCount = document.getElementById('direct-count');
    if (directCount) directCount.textContent = stats.direct_convalidations;
    
    const electiveCount = document.getElementById('elective-count');
    if (electiveCount) electiveCount.textContent = stats.free_electives;
    
    const notConvalidatedCount = document.getElementById('not-convalidated-count');
    if (notConvalidatedCount) notConvalidatedCount.textContent = stats.not_convalidated;
    
    const pendingCount = document.getElementById('pending-count');
    if (pendingCount) pendingCount.textContent = stats.pending_subjects;
    
    // Update career completion stats
    const careerPercentage = document.getElementById('career-percentage');
    if (careerPercentage) careerPercentage.textContent = `${stats.career_completion_percentage.toFixed(1)}%`;
    
    const convalidatedCredits = document.getElementById('convalidated-credits');
    if (convalidatedCredits) convalidatedCredits.textContent = stats.convalidated_credits.toFixed(1);
    
    const careerProgress = document.getElementById('career-progress');
    if (careerProgress) careerProgress.style.width = `${stats.career_completion_percentage}%`;
}

function updateConvalidationDisplay(subjectId, convalidation) {
    const displayElement = document.getElementById(`convalidation-display-${subjectId}`);
    
    if (convalidation.convalidation_type === 'direct') {
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-arrow-right text-success me-2"></i>
                <div>
                    <small class="fw-bold text-success">${convalidation.internal_subject.name}</small><br>
                    <small class="text-muted">${convalidation.internal_subject.code}</small>
                </div>
            </div>
        `;
    } else if (convalidation.convalidation_type === 'free_elective') {
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-star text-info me-2"></i>
                <span class="fw-bold text-info">Libre Elección</span>
            </div>
        `;
    } else if (convalidation.convalidation_type === 'not_convalidated') {
        displayElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-plus-circle text-warning me-2"></i>
                <span class="fw-bold text-warning">Materia Nueva</span>
            </div>
        `;
    }
    
    // Update status badge
    const statusElement = document.querySelector(`#subject-row-${subjectId} .badge`);
    if (statusElement) {
        if (convalidation.convalidation_type === 'direct') {
            statusElement.className = 'badge bg-success';
            statusElement.innerHTML = '<i class="fas fa-check me-1"></i>Convalidada';
        } else if (convalidation.convalidation_type === 'free_elective') {
            statusElement.className = 'badge bg-info';
            statusElement.innerHTML = '<i class="fas fa-star me-1"></i>Libre Elección';
        } else if (convalidation.convalidation_type === 'not_convalidated') {
            statusElement.className = 'badge bg-warning';
            statusElement.innerHTML = '<i class="fas fa-plus-circle me-1"></i>Materia Nueva';
        }
    }
}

function getSuggestions(externalSubjectId = null) {
    const targetId = externalSubjectId || currentExternalSubjectId;
    
    fetch(`{{ route('convalidation.suggestions') }}?external_subject_id=${targetId}`)
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('suggestions_container');
        const list = document.getElementById('suggestions_list');
        
        if (data.suggestions && data.suggestions.length > 0) {
            list.innerHTML = data.suggestions.map(suggestion => `
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${suggestion.subject.name}</strong>
                                <small class="text-muted">(${suggestion.subject.code})</small>
                                <div class="mt-1">
                                    <span class="badge bg-info">${suggestion.match_percentage}% similitud</span>
                                    <span class="badge bg-secondary">Semestre ${suggestion.subject.semester}</span>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="selectSuggestion('${suggestion.subject.code}')">
                                Seleccionar
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            container.style.display = 'block';
        } else {
            list.innerHTML = '<p class="text-muted">No se encontraron sugerencias automáticas</p>';
            container.style.display = 'block';
        }
    });
}

function selectSuggestion(subjectCode) {
    document.getElementById('type_direct').checked = true;
    document.getElementById('internal_subject_code').value = subjectCode;
    document.getElementById('internal_subject_selection').style.display = 'block';
}

function removeConvalidation(convalidationId) {
    if (confirm('¿Está seguro de que desea eliminar esta convalidación?')) {
        fetch(`/convalidation/convalidation/${convalidationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert('danger', 'Error al eliminar la convalidación');
            }
        });
    }
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at top of page
    document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alertHtml);
}

function exportReport() {
    window.location.href = '{{ route("convalidation.export", $externalCurriculum) }}';
}
