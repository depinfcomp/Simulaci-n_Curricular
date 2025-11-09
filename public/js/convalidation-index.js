let currentImpactResults = null;
let currentCurriculumId = null;

function deleteCurriculum(curriculumId) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/convalidation/${curriculumId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function exportReport(curriculumId) {
    // Implement export functionality
    window.location.href = `/convalidation/${curriculumId}/export`;
}

document.getElementById('impactConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const curriculumId = formData.get('curriculum_id');
    
    // Make AJAX request to save configuration
    fetch(`/convalidation/${curriculumId}/set-impact-config`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('impactConfigModal'));
            modal.hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Configuración Guardada',
                text: 'La configuración del análisis de impacto se ha guardado correctamente.',
                confirmButtonText: 'Aceptar'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al guardar la configuración',
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Conexión',
            text: 'No se pudo conectar al servidor. Inténtelo de nuevo más tarde.',
            confirmButtonText: 'Aceptar'
        });
    });
});

function showImpactConfigModal(curriculumId) {
    currentCurriculumId = curriculumId;
    const modal = new bootstrap.Modal(document.getElementById('impactConfigModal'));
    modal.show();
    
    // Cargar total de créditos de la malla
    loadCurriculumTotalCredits(curriculumId);
}

function loadCurriculumTotalCredits(curriculumId) {
    const creditsElement = document.getElementById('curriculumTotalCredits');
    
    if (!creditsElement) {
        console.error('[loadCurriculumTotalCredits] Element #curriculumTotalCredits not found!');
        return;
    }
    
    creditsElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...';
    
    console.log('[loadCurriculumTotalCredits] Starting for curriculum ID:', curriculumId);
    const startTime = performance.now();
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('[loadCurriculumTotalCredits] CSRF token not found');
        creditsElement.innerHTML = '<span class="text-danger">❌ Token no encontrado</span>';
        return;
    }
    
    // Fetch total credits from external subjects
    const url = `/convalidation/${curriculumId}/total-credits`;
    console.log('[loadCurriculumTotalCredits] Fetching from URL:', url);
    console.log('[loadCurriculumTotalCredits] Base URL:', window.location.origin);
    console.log('[loadCurriculumTotalCredits] Full URL:', window.location.origin + url);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        const fetchTime = performance.now() - startTime;
        console.log('[loadCurriculumTotalCredits] Response received in:', fetchTime.toFixed(2), 'ms');
        console.log('[loadCurriculumTotalCredits] Response status:', response.status);
        console.log('[loadCurriculumTotalCredits] Response headers:', response.headers);
        
        if (!response.ok) {
            console.error('[loadCurriculumTotalCredits] HTTP Error:', response.status, response.statusText);
            throw new Error(`Error HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        const totalTime = performance.now() - startTime;
        console.log('[loadCurriculumTotalCredits] ✅ Data received:', data);
        console.log('[loadCurriculumTotalCredits] Total time:', totalTime.toFixed(2), 'ms');
        
        if (data.success) {
            const displayText = `<strong>${data.total_credits}</strong> créditos` + 
                              (data.total_subjects ? ` <small class="text-muted">(${data.total_subjects} materias)</small>` : '') +
                              (data.duration_ms ? ` <small class="text-muted">[${data.duration_ms}ms]</small>` : '');
            creditsElement.innerHTML = displayText;
            console.log('[loadCurriculumTotalCredits] ✅ Display updated successfully');
        } else {
            console.error('[loadCurriculumTotalCredits] Response success=false:', data);
            creditsElement.innerHTML = '<span class="text-danger">❌ Error al calcular</span>';
        }
    })
    .catch(error => {
        const totalTime = performance.now() - startTime;
        console.error('[loadCurriculumTotalCredits] ❌ Error:', error);
        console.error('[loadCurriculumTotalCredits] Error type:', error.constructor.name);
        console.error('[loadCurriculumTotalCredits] Error message:', error.message);
        console.error('[loadCurriculumTotalCredits] Failed after:', totalTime.toFixed(2), 'ms');
        creditsElement.innerHTML = `<span class="text-danger">❌ Error: ${error.message}</span>`;
    });
}

function runImpactAnalysis() {
    if (!currentCurriculumId) {
        showErrorMessage('Error: No se ha seleccionado una malla curricular');
        return;
    }

    // Close the configuration modal
    const configModal = bootstrap.Modal.getInstance(document.getElementById('impactConfigModal'));
    configModal.hide();

    // Open the analysis modal
    const analysisModal = new bootstrap.Modal(document.getElementById('impactAnalysisModal'));
    analysisModal.show();

    // Get form values - Todos los límites de créditos (todos obligatorios)
    const maxFreeElectiveCredits = document.getElementById('maxFreeElectiveCredits').value || 36;
    const maxOptionalProfessionalCredits = document.getElementById('maxOptionalProfessionalCredits').value || 9;
    const maxOptionalFundamentalCredits = document.getElementById('maxOptionalFundamentalCredits').value || 6;
    const maxLevelingCredits = document.getElementById('maxLevelingCredits').value || 12;
    const maxRequiredFundamentalCredits = document.getElementById('maxRequiredFundamentalCredits').value || 60;
    const maxRequiredProfessionalCredits = document.getElementById('maxRequiredProfessionalCredits').value || 80;
    const maxThesisCredits = document.getElementById('maxThesisCredits').value || 6;

    // Reset content
    document.getElementById('impactAnalysisContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Analizando...</span>
            </div>
            <p class="mt-3">Analizando impacto en estudiantes...</p>
            <small class="text-muted">Simulando migración de estudiantes a la nueva malla curricular</small>
        </div>
    `;
    
    document.getElementById('exportImpactBtn').style.display = 'none';

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        showErrorMessage('Error: Token CSRF no encontrado');
        return;
    }

    // Perform the AJAX request con todos los límites
    fetch(`/convalidation/${currentCurriculumId}/analyze-impact`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
        },
        body: JSON.stringify({
            max_free_elective_credits: maxFreeElectiveCredits,
            max_optional_professional_credits: maxOptionalProfessionalCredits,
            max_optional_fundamental_credits: maxOptionalFundamentalCredits,
            max_leveling_credits: maxLevelingCredits,
            max_required_fundamental_credits: maxRequiredFundamentalCredits,
            max_required_professional_credits: maxRequiredProfessionalCredits,
            max_thesis_credits: maxThesisCredits
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            currentImpactResults = data.results;
            displayImpactResults(data.results);
            document.getElementById('exportImpactBtn').style.display = 'inline-block';
        } else {
            showErrorMessage(data.message || 'Error al analizar el impacto');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Error: ' + error.message);
    });
}

function displayImpactResults(results) {
    const content = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4>${results.total_students}</h4>
                        <small>Total Estudiantes</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4>${results.affected_students}</h4>
                        <small>Estudiantes Afectados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4>${results.affected_percentage}%</h4>
                        <small>Porcentaje Afectado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>${results.average_progress_change > 0 ? '+' : ''}${results.average_progress_change}%</h4>
                        <small>Cambio Promedio</small>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="impactTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">
                    <i class="fas fa-chart-pie me-1"></i>
                    Resumen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab">
                    <i class="fas fa-users me-1"></i>
                    Estudiantes Afectados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="subjects-tab" data-bs-toggle="tab" data-bs-target="#subjects" type="button" role="tab">
                    <i class="fas fa-book me-1"></i>
                    Impacto por Materias
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3" id="impactTabContent">
            <div class="tab-pane fade show active" id="summary" role="tabpanel">
                ${generateSummaryTab(results)}
            </div>
            <div class="tab-pane fade" id="students" role="tabpanel">
                ${generateStudentsTab(results)}
            </div>
            <div class="tab-pane fade" id="subjects" role="tabpanel">
                ${generateSubjectsTab(results)}
            </div>
        </div>
    `;
    
    document.getElementById('impactAnalysisContent').innerHTML = content;
}

function generateSummaryTab(results) {
    // Component breakdown section
    let componentBreakdown = '';
    if (results.credits_by_component) {
        const componentNames = {
            'fundamental_required': { name: 'Fundamental Obligatorio', color: 'warning' },
            'fundamental_optional': { name: 'Fundamental Optativo', color: 'warning' },
            'professional_required': { name: 'Disciplinar Obligatorio', color: 'success' },
            'professional_optional': { name: 'Disciplinar Optativo', color: 'success' },
            'leveling': { name: 'Nivelación', color: 'danger' },
            'thesis': { name: 'Trabajo de Grado', color: 'dark' },
            'free_elective': { name: 'Libre Elección', color: 'primary' },
        };

        componentBreakdown = `
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-layer-group me-2"></i>
                            Desglose de Créditos por Componente
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
        `;

        for (const [component, data] of Object.entries(results.credits_by_component)) {
            if (!componentNames[component]) continue;
            
            const { name, color } = componentNames[component];
            const used = data.used || 0;
            const overflow = data.overflow || 0;
            const excess = data.excess || 0;
            const total = used + overflow + excess;

            if (total > 0) {
                componentBreakdown += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card border-${color}">
                            <div class="card-body">
                                <h6 class="text-${color}">
                                    <i class="fas fa-circle me-1"></i>
                                    ${name}
                                </h6>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <small>Válidos:</small>
                                        <span class="badge bg-${color}">${used} créditos</span>
                                    </div>
                                </div>
                `;

                if (overflow > 0) {
                    componentBreakdown += `
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <small>→ Libre Elección:</small>
                                <span class="badge bg-primary">${overflow} créditos</span>
                            </div>
                        </div>
                    `;
                }

                if (excess > 0) {
                    componentBreakdown += `
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <small>❌ Excedentes:</small>
                                <span class="badge bg-secondary">${excess} créditos</span>
                            </div>
                        </div>
                    `;
                }

                componentBreakdown += `
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-${color}" style="width: ${(used/total)*100}%" title="Válidos: ${used}"></div>
                                    <div class="progress-bar bg-primary" style="width: ${(overflow/total)*100}%" title="Overflow: ${overflow}"></div>
                                    <div class="progress-bar bg-secondary" style="width: ${(excess/total)*100}%" title="Excedentes: ${excess}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        componentBreakdown += `
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    return `
        ${componentBreakdown}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Distribución de Impacto</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Estudiantes con progreso mejorado:</span>
                                <span class="badge bg-success">${results.students_with_improved_progress || 0}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${((results.students_with_improved_progress || 0) / results.total_students * 100)}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Estudiantes con progreso reducido:</span>
                                <span class="badge bg-danger">${results.students_with_reduced_progress || 0}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: ${((results.students_with_reduced_progress || 0) / results.total_students * 100)}%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Estudiantes sin cambio:</span>
                                <span class="badge bg-secondary">${results.students_with_no_change || 0}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-secondary" style="width: ${((results.students_with_no_change || 0) / results.total_students * 100)}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Estadísticas Generales</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h5 class="text-primary">${results.total_convalidated_credits || 0}</h5>
                                <small>Créditos Convalidados</small>
                            </div>
                            <div class="col-4">
                                <h5 class="text-warning">${results.total_new_credits || 0}</h5>
                                <small>Créditos Nuevos</small>
                            </div>
                            <div class="col-4">
                                <h5 class="text-danger">${results.total_lost_credits || 0}</h5>
                                <small>Créditos Perdidos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateStudentsTab(results) {
    if (!results.student_details || results.student_details.length === 0) {
        return '<div class="alert alert-info">No hay estudiantes afectados por el cambio de malla.</div>';
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Progreso Original</th>
                        <th>Progreso Nuevo</th>
                        <th>Cambio</th>
                        <th>Convalidadas</th>
                        <th>Nuevas</th>
                        <th>Perdidas</th>
                        <th>Explicación</th>
                    </tr>
                </thead>
                <tbody>
    `;

    results.student_details.forEach(student => {
        const changeClass = student.progress_change > 0.1 ? 'text-success' : 
                          student.progress_change < -0.1 ? 'text-danger' : 'text-muted';
        const changeIcon = student.progress_change > 0.1 ? 'fa-arrow-up' : 
                         student.progress_change < -0.1 ? 'fa-arrow-down' : 'fa-minus';
        
        const progressBarClass = student.progress_change > 0.1 ? 'bg-success' : 
                               student.progress_change < -0.1 ? 'bg-danger' : 'bg-warning';
        
        html += `
            <tr>
                <td>
                    <strong>${escapeHtml(student.name || 'Sin nombre')}</strong><br>
                    <small class="text-muted">${escapeHtml(student.email || 'Sin email')}</small>
                </td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-secondary" style="width: ${student.original_progress || 0}%">
                            ${(student.original_progress || 0).toFixed(1)}%
                        </div>
                    </div>
                    <small class="text-muted">${student.original_subjects_passed || 0} materias</small>
                </td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${progressBarClass}" style="width: ${student.new_progress || 0}%">
                            ${(student.new_progress || 0).toFixed(1)}%
                        </div>
                    </div>
                    <small class="text-muted">${student.convalidated_subjects_count || 0} convalidadas</small>
                </td>
                <td class="${changeClass}">
                    <i class="fas ${changeIcon}"></i>
                    ${student.progress_change > 0 ? '+' : ''}${(student.progress_change || 0).toFixed(1)}%
                </td>
                <td>
                    <span class="badge bg-success">${student.convalidated_subjects_count || 0}</span>
                </td>
                <td>
                    <span class="badge bg-warning">${student.new_subjects_count || 0}</span>
                </td>
                <td>
                    <span class="badge bg-danger">${student.lost_credits_count || 0}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-info show-explanation-btn" 
                            data-student-name="${escapeHtml(student.name || 'Sin nombre')}"
                            data-explanation="${encodeURIComponent(student.progress_explanation || 'Sin explicación disponible')}"
                            data-original-progress="${student.original_progress || 0}"
                            data-new-progress="${student.new_progress || 0}"
                            data-progress-change="${student.progress_change || 0}"
                            data-convalidated-count="${student.convalidated_subjects_count || 0}"
                            data-new-subjects-count="${student.new_subjects_count || 0}"
                            data-lost-credits-count="${student.lost_credits_count || 0}"
                            title="Ver explicación detallada del cambio de progreso">
                        <i class="fas fa-info-circle"></i> Detalles
                    </button>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Interpretación de los Datos</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Convalidadas:</strong> Materias que el estudiante cursó y pueden ser reconocidas en la nueva malla.
                    </div>
                    <div class="col-md-4">
                        <strong>Nuevas:</strong> Materias adicionales que debe cursar en la nueva malla.
                    </div>
                    <div class="col-md-4">
                        <strong>Perdidas:</strong> Materias que cursó pero no tienen equivalencia en la nueva malla.
                    </div>
                </div>
            </div>
        </div>
    `;

    return html;
}

function generateSubjectsTab(results) {
    return `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Materias Más Convalidadas</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">Funcionalidad en desarrollo</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Materias Problemáticas</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">Funcionalidad en desarrollo</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function exportImpactResults() {
    if (!currentImpactResults) {
        showErrorMessage('No hay resultados para exportar');
        return;
    }

    const csvContent = generateCSVContent(currentImpactResults);
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `impacto_convalidacion_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function generateCSVContent(results) {
    let csv = 'Estudiante,Email,Progreso Original,Progreso Nuevo,Cambio,Materias Convalidadas\n';
    
    if (results.student_details) {
        results.student_details.forEach(student => {
            csv += `"${student.name}","${student.email}",${student.original_progress}%,${student.new_progress}%,${student.progress_change}%,${student.convalidated_subjects}\n`;
        });
    }
    
    return csv;
}

function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event delegation for explanation buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('show-explanation-btn') || 
        e.target.closest('.show-explanation-btn')) {
        
        const button = e.target.classList.contains('show-explanation-btn') ? 
                      e.target : e.target.closest('.show-explanation-btn');
        
        const studentName = button.getAttribute('data-student-name') || 'Estudiante';
        const explanation = decodeURIComponent(button.getAttribute('data-explanation') || '');
        const originalProgress = parseFloat(button.getAttribute('data-original-progress')) || 0;
        const newProgress = parseFloat(button.getAttribute('data-new-progress')) || 0;
        const progressChange = parseFloat(button.getAttribute('data-progress-change')) || 0;
        const convalidatedCount = parseInt(button.getAttribute('data-convalidated-count')) || 0;
        const newSubjectsCount = parseInt(button.getAttribute('data-new-subjects-count')) || 0;
        const lostCreditsCount = parseInt(button.getAttribute('data-lost-credits-count')) || 0;
        
        // Show the modal with all data
        showProgressExplanationDetailed(
            studentName, 
            explanation, 
            originalProgress, 
            newProgress, 
            progressChange, 
            convalidatedCount, 
            newSubjectsCount, 
            lostCreditsCount
        );
    }
});

function showProgressExplanationDetailed(studentName, explanation, originalProgress, newProgress, progressChange, convalidatedCount, newSubjectsCount, lostCreditsCount) {
    // Set student name
    document.getElementById('student-name-title').textContent = `Estudiante: ${studentName}`;
    
    // Set progress badges
    document.getElementById('original-progress-badge').textContent = `${originalProgress}%`;
    
    const newProgressBadge = document.getElementById('new-progress-badge');
    newProgressBadge.textContent = `${newProgress}%`;
    
    // Set badge color based on change
    if (progressChange > 0.1) {
        newProgressBadge.className = 'badge bg-success fs-6 mb-2';
    } else if (progressChange < -0.1) {
        newProgressBadge.className = 'badge bg-danger fs-6 mb-2';
    } else {
        newProgressBadge.className = 'badge bg-warning fs-6 mb-2';
    }
    
    // Set change summary
    const changeSummary = document.getElementById('change-summary');
    let summaryHTML = '';
    let alertClass = '';
    
    if (progressChange > 0.1) {
        alertClass = 'alert-success';
        summaryHTML = `
            <h6><i class="fas fa-arrow-up me-2"></i>Progreso Mejorado</h6>
            <p class="mb-2"><strong>Aumento de ${Math.abs(progressChange).toFixed(1)} puntos porcentuales</strong></p>
            <div class="row text-center">
                <div class="col-4">
                    <small class="text-muted">Convalidadas</small><br>
                    <strong class="text-success">${convalidatedCount}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Nuevas</small><br>
                    <strong class="text-warning">${newSubjectsCount}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Perdidas</small><br>
                    <strong class="text-danger">${lostCreditsCount}</strong>
                </div>
            </div>
        `;
    } else if (progressChange < -0.1) {
        alertClass = 'alert-danger';
        summaryHTML = `
            <h6><i class="fas fa-arrow-down me-2"></i>Progreso Reducido</h6>
            <p class="mb-2"><strong>Disminución de ${Math.abs(progressChange).toFixed(1)} puntos porcentuales</strong></p>
            <div class="row text-center">
                <div class="col-4">
                    <small class="text-muted">Convalidadas</small><br>
                    <strong class="text-success">${convalidatedCount}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Nuevas</small><br>
                    <strong class="text-warning">${newSubjectsCount}</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted">Perdidas</small><br>
                    <strong class="text-danger">${lostCreditsCount}</strong>
                </div>
            </div>
        `;
    } else {
        alertClass = 'alert-info';
        summaryHTML = `
            <h6><i class="fas fa-minus me-2"></i>Progreso Sin Cambios Significativos</h6>
            <p class="mb-0">El cambio es menor a 0.1 puntos porcentuales</p>
        `;
    }
    
    changeSummary.className = `alert ${alertClass}`;
    changeSummary.innerHTML = summaryHTML;
    
    // Set detailed explanation
    document.getElementById('detailed-explanation').innerHTML = explanation || 'No hay explicación detallada disponible.';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('progressExplanationModal'));
    modal.show();
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Convalidation system loaded successfully');
});
