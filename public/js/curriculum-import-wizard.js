/**
 * Curriculum Import Wizard
 * Handles multi-step import process with automatic format detection
 */

let currentImportId = null;
let currentStep = 'upload';
let fileData = null;
let analysisData = null;
let validationData = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeWizard();
    loadTemplates();
});

/**
 * Initialize wizard components
 */
function initializeWizard() {
    // File upload handling
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');

    // Drag and drop events
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    // Click on dropzone
    dropzone.addEventListener('click', (e) => {
        if (e.target === dropzone || e.target.closest('#dropzone')) {
            fileInput.click();
        }
    });

    // Upload button
    document.getElementById('btn-upload').addEventListener('click', uploadFile);

    // Template selection
    document.getElementById('template-select').addEventListener('change', handleTemplateSelection);

    // Mapping confirmation
    document.getElementById('btn-confirm-mapping').addEventListener('click', proceedToValidation);

    // Save corrections
    document.getElementById('btn-save-corrections').addEventListener('click', saveCorrections);

    // Proceed to confirm
    document.getElementById('btn-proceed-confirm').addEventListener('click', () => goToStep('confirm'));

    // Final import
    document.getElementById('btn-final-import').addEventListener('click', confirmImport);

    // Save as template checkbox
    document.getElementById('save-as-template').addEventListener('change', function() {
        document.getElementById('template-name-section').style.display = this.checked ? 'block' : 'none';
    });
}

/**
 * Load available templates
 */
async function loadTemplates() {
    try {
        const response = await fetch('/import-curriculum/templates');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('template-select');
            
            data.templates.forEach(template => {
                const option = document.createElement('option');
                option.value = template.id;
                option.textContent = `${template.template_name} (${template.original_filename})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading templates:', error);
    }
}

/**
 * Handle template selection
 */
function handleTemplateSelection(e) {
    const templateId = e.target.value;
    
    if (templateId) {
        // User selected a template
        console.log('Template selected:', templateId);
        // We'll apply it after file upload
    }
}

/**
 * Handle file selection
 */
function handleFileSelect(file) {
    // Validate file
    const validExtensions = ['xlsx', 'xls', 'csv'];
    const extension = file.name.split('.').pop().toLowerCase();
    
    if (!validExtensions.includes(extension)) {
        alert('Por favor selecciona un archivo Excel válido (.xlsx, .xls, .csv)');
        return;
    }

    if (file.size > 10 * 1024 * 1024) { // 10MB
        alert('El archivo es demasiado grande. Máximo 10MB');
        return;
    }

    // Store file
    fileData = file;

    // Show file info
    document.getElementById('file-name').textContent = file.name;
    document.getElementById('file-size').textContent = `(${(file.size / 1024).toFixed(2)} KB)`;
    document.getElementById('file-info').classList.remove('d-none');
    document.getElementById('curriculum-info-form').classList.remove('d-none');
}

/**
 * Clear selected file
 */
function clearFile() {
    fileData = null;
    document.getElementById('file-input').value = '';
    document.getElementById('file-info').classList.add('d-none');
    document.getElementById('curriculum-info-form').classList.add('d-none');
}

/**
 * Upload file to server
 */
async function uploadFile() {
    const curriculumName = document.getElementById('curriculum-name').value.trim();
    const institution = document.getElementById('institution').value.trim();
    const year = document.getElementById('year').value;

    if (!curriculumName || !institution) {
        alert('Por favor completa todos los campos requeridos');
        return;
    }

    if (!fileData) {
        alert('Por favor selecciona un archivo');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileData);
    formData.append('curriculum_name', curriculumName);
    formData.append('institution', institution);
    if (year) formData.append('year', year);

    try {
        showLoading('Subiendo archivo...');

        const response = await fetch('/import-curriculum/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            currentImportId = data.import_id;
            
            // If template was selected, apply it first
            const templateId = document.getElementById('template-select').value;
            if (templateId) {
                await applyTemplate(templateId);
            }
            
            // Proceed to analyze
            await analyzeFile();
        } else {
            alert('Error al subir archivo: ' + data.message);
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Error al subir archivo');
    } finally {
        hideLoading();
    }
}

/**
 * Apply saved template
 */
async function applyTemplate(templateId) {
    try {
        const response = await fetch(`/import-curriculum/${currentImportId}/apply-template`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ template_id: templateId })
        });

        const data = await response.json();
        
        if (data.success) {
            console.log('Template applied successfully');
        }
    } catch (error) {
        console.error('Error applying template:', error);
    }
}

/**
 * Analyze uploaded file
 */
async function analyzeFile() {
    try {
        goToStep('analyze');
        document.getElementById('analysis-loading').style.display = 'block';
        document.getElementById('analysis-result').classList.add('d-none');

        const response = await fetch(`/import-curriculum/${currentImportId}/analyze`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            analysisData = data.analysis;
            displayAnalysisResults(data);
        } else {
            alert('Error al analizar archivo: ' + data.message);
            goToStep('upload');
        }
    } catch (error) {
        console.error('Analysis error:', error);
        alert('Error al analizar archivo');
        goToStep('upload');
    } finally {
        document.getElementById('analysis-loading').style.display = 'none';
    }
}

/**
 * Display analysis results
 */
function displayAnalysisResults(data) {
    const analysis = data.analysis;
    
    // Update summary
    document.getElementById('header-row-number').textContent = analysis.header_row;
    document.getElementById('total-data-rows').textContent = analysis.total_rows - analysis.data_start_row + 1;
    
    const requiredCount = Object.values(analysis.required_fields_status).filter(v => v === true).length;
    document.getElementById('required-fields-count').textContent = requiredCount;

    // Build column mapping table
    const tableBody = document.getElementById('column-mapping-table');
    tableBody.innerHTML = '';

    const columnMapping = analysis.column_mapping;
    const detectedColumns = analysis.detected_columns;
    const previewData = analysis.preview_data;
    const availableFields = data.available_fields;

    // Get Excel columns
    const excelColumns = Object.keys(columnMapping);

    excelColumns.forEach((excelCol, index) => {
        const mappedField = columnMapping[excelCol];
        const confidence = detectedColumns[excelCol]?.confidence || 'low';
        const header = previewData[0]?.[excelCol] || '';
        const preview = previewData.slice(1, 4).map(row => row[excelCol]).filter(v => v).join(', ');

        const row = document.createElement('tr');
        
        // Excel column
        const colCell = document.createElement('td');
        colCell.innerHTML = `<strong>${excelCol}</strong>`;
        row.appendChild(colCell);

        // Header
        const headerCell = document.createElement('td');
        headerCell.textContent = header;
        row.appendChild(headerCell);

        // Mapping dropdown
        const mappingCell = document.createElement('td');
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.dataset.excelColumn = excelCol;
        
        // Add empty option
        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = '-- No mapear --';
        select.appendChild(emptyOption);

        // Add field options
        Object.entries(availableFields).forEach(([fieldKey, fieldInfo]) => {
            const option = document.createElement('option');
            option.value = fieldKey;
            option.textContent = `${fieldInfo.label}${fieldInfo.required ? ' *' : ''}`;
            if (mappedField === fieldKey) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        select.addEventListener('change', updateColumnMapping);
        mappingCell.appendChild(select);
        row.appendChild(mappingCell);

        // Confidence badge
        const confidenceCell = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = `badge confidence-${confidence}`;
        badge.textContent = confidence === 'high' ? 'Alta' : confidence === 'medium' ? 'Media' : 'Baja';
        confidenceCell.appendChild(badge);
        row.appendChild(confidenceCell);

        // Preview
        const previewCell = document.createElement('td');
        previewCell.innerHTML = `<small class="text-muted">${preview || '(vacío)'}</small>`;
        row.appendChild(previewCell);

        tableBody.appendChild(row);
    });

    // Check missing required fields
    const missingFields = data.missing_fields || [];
    if (missingFields.length > 0) {
        document.getElementById('missing-fields-warning').style.display = 'block';
        const list = document.getElementById('missing-fields-list');
        list.innerHTML = '';
        
        missingFields.forEach(field => {
            const li = document.createElement('li');
            li.textContent = availableFields[field]?.label || field;
            list.appendChild(li);
        });
    } else {
        document.getElementById('missing-fields-warning').style.display = 'none';
    }

    document.getElementById('analysis-result').classList.remove('d-none');
}

/**
 * Update column mapping when user changes dropdown
 */
function updateColumnMapping() {
    // Rebuild column mapping object
    const selects = document.querySelectorAll('#column-mapping-table select');
    const newMapping = {};
    
    selects.forEach(select => {
        const excelCol = select.dataset.excelColumn;
        const fieldName = select.value;
        if (fieldName) {
            newMapping[excelCol] = fieldName;
        }
    });

    // Update analysis data
    analysisData.column_mapping = newMapping;

    // Check if all required fields are mapped
    const requiredFields = ['code', 'name', 'semester', 'credits'];
    const mappedFields = Object.values(newMapping);
    const missingFields = requiredFields.filter(field => !mappedFields.includes(field));

    if (missingFields.length > 0) {
        document.getElementById('missing-fields-warning').style.display = 'block';
        const list = document.getElementById('missing-fields-list');
        list.innerHTML = '';
        
        missingFields.forEach(field => {
            const li = document.createElement('li');
            li.textContent = field;
            list.appendChild(li);
        });
    } else {
        document.getElementById('missing-fields-warning').style.display = 'none';
    }
}

/**
 * Proceed to validation step
 */
async function proceedToValidation() {
    // Save mapping if user modified it
    const selects = document.querySelectorAll('#column-mapping-table select');
    const mapping = {};
    
    selects.forEach(select => {
        const excelCol = select.dataset.excelColumn;
        const fieldName = select.value;
        if (fieldName) {
            mapping[excelCol] = fieldName;
        }
    });

    // Check required fields
    const requiredFields = ['code', 'name', 'semester', 'credits'];
    const mappedFields = Object.values(mapping);
    const missingFields = requiredFields.filter(field => !mappedFields.includes(field));

    if (missingFields.length > 0) {
        alert('Por favor mapea todos los campos requeridos: ' + missingFields.join(', '));
        return;
    }

    try {
        // Update mapping on server
        const response = await fetch(`/import-curriculum/${currentImportId}/mapping`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ column_mapping: mapping })
        });

        const data = await response.json();

        if (data.success) {
            // Proceed to validation
            await validateData();
        } else {
            alert('Error al guardar mapeo: ' + data.message);
        }
    } catch (error) {
        console.error('Mapping error:', error);
        alert('Error al guardar mapeo');
    }
}

/**
 * Validate imported data
 */
async function validateData() {
    try {
        goToStep('validate');
        document.getElementById('validation-loading').style.display = 'block';
        document.getElementById('validation-result').classList.add('d-none');

        const response = await fetch(`/import-curriculum/${currentImportId}/validate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            validationData = data;
            displayValidationResults(data);
        } else {
            alert('Error al validar datos: ' + data.message);
            goToStep('analyze');
        }
    } catch (error) {
        console.error('Validation error:', error);
        alert('Error al validar datos');
        goToStep('analyze');
    } finally {
        document.getElementById('validation-loading').style.display = 'none';
    }
}

/**
 * Display validation results
 */
function displayValidationResults(data) {
    // Update counts
    document.getElementById('valid-rows-count').textContent = data.valid_rows;
    document.getElementById('invalid-rows-count').textContent = data.invalid_rows;
    document.getElementById('total-rows-count').textContent = data.total_rows;

    if (data.has_errors) {
        // Show errors section
        document.getElementById('errors-section').style.display = 'block';
        document.getElementById('no-errors-section').style.display = 'none';

        // Build errors table
        const tableBody = document.getElementById('errors-table');
        tableBody.innerHTML = '';

        data.missing_data_rows.forEach(errorRow => {
            const row = document.createElement('tr');
            
            // Row number
            const rowNumCell = document.createElement('td');
            rowNumCell.textContent = errorRow.row_number;
            row.appendChild(rowNumCell);

            // Editable fields
            ['code', 'name', 'semester', 'credits'].forEach(field => {
                const cell = document.createElement('td');
                const value = errorRow.data[field] || '';
                const hasError = errorRow.missing_fields.includes(field);

                if (hasError) {
                    cell.className = 'editable-cell';
                    cell.dataset.row = errorRow.row_number;
                    cell.dataset.field = field;
                    cell.textContent = value;
                    cell.addEventListener('click', makeEditable);
                } else {
                    cell.textContent = value;
                }

                row.appendChild(cell);
            });

            // Errors list
            const errorsCell = document.createElement('td');
            const errorsList = document.createElement('ul');
            errorsList.className = 'mb-0';
            errorRow.errors.forEach(error => {
                const li = document.createElement('li');
                li.innerHTML = `<small class="text-danger">${error}</small>`;
                errorsList.appendChild(li);
            });
            errorsCell.appendChild(errorsList);
            row.appendChild(errorsCell);

            tableBody.appendChild(row);
        });
    } else {
        // No errors - ready to import
        document.getElementById('errors-section').style.display = 'none';
        document.getElementById('no-errors-section').style.display = 'block';
    }

    document.getElementById('validation-result').classList.remove('d-none');
}

/**
 * Make cell editable
 */
function makeEditable(e) {
    const cell = e.target;
    if (cell.querySelector('input')) return; // Already editing

    const currentValue = cell.textContent;
    const input = document.createElement('input');
    input.type = 'text';
    input.value = currentValue;
    input.className = 'form-control form-control-sm';

    input.addEventListener('blur', function() {
        cell.textContent = this.value;
    });

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.blur();
        }
    });

    cell.textContent = '';
    cell.appendChild(input);
    input.focus();
}

/**
 * Save corrections and revalidate
 */
async function saveCorrections() {
    const editableCells = document.querySelectorAll('.editable-cell');
    const completedRows = {};

    editableCells.forEach(cell => {
        const rowNum = cell.dataset.row;
        const field = cell.dataset.field;
        const value = cell.textContent.trim();

        if (!completedRows[rowNum]) {
            completedRows[rowNum] = {};
        }
        completedRows[rowNum][field] = value;
    });

    try {
        showLoading('Guardando correcciones...');

        const response = await fetch(`/import-curriculum/${currentImportId}/fill`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ completed_rows: completedRows })
        });

        const data = await response.json();

        if (data.success) {
            if (data.remaining_errors === 0) {
                // All errors fixed, go to confirm
                goToStep('confirm');
                prepareConfirmation();
            } else {
                // Still have errors, revalidate
                await validateData();
            }
        } else {
            alert('Error al guardar correcciones: ' + data.message);
        }
    } catch (error) {
        console.error('Save corrections error:', error);
        alert('Error al guardar correcciones');
    } finally {
        hideLoading();
    }
}

/**
 * Prepare confirmation step
 */
function prepareConfirmation() {
    // Update summary
    document.getElementById('confirm-filename').textContent = fileData.name;
    document.getElementById('confirm-curriculum-name').textContent = document.getElementById('curriculum-name').value;
    document.getElementById('confirm-institution').textContent = document.getElementById('institution').value;
    document.getElementById('confirm-subject-count').textContent = validationData.valid_rows;

    // We'll populate preview table after import is confirmed
    // For now, leave it empty or add placeholder
}

/**
 * Confirm and execute final import
 */
async function confirmImport() {
    const curriculumName = document.getElementById('curriculum-name').value;
    const institution = document.getElementById('institution').value;
    const year = document.getElementById('year').value;
    const saveAsTemplate = document.getElementById('save-as-template').checked;
    const templateName = document.getElementById('template-name').value;

    if (saveAsTemplate && !templateName) {
        alert('Por favor ingresa un nombre para la plantilla');
        return;
    }

    try {
        showLoading('Importando materias...');

        const response = await fetch(`/import-curriculum/${currentImportId}/confirm`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                curriculum_name: curriculumName,
                institution: institution,
                year: year || null,
                save_as_template: saveAsTemplate,
                template_name: saveAsTemplate ? templateName : null
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show success
            document.getElementById('success-subject-count').textContent = `${data.subjects_imported}`;
            goToStep('success');
        } else {
            alert('Error al importar: ' + data.message);
        }
    } catch (error) {
        console.error('Import error:', error);
        alert('Error al importar');
    } finally {
        hideLoading();
    }
}

/**
 * Navigate to specific step
 */
function goToStep(step) {
    // Hide all steps
    document.querySelectorAll('.wizard-content').forEach(content => {
        content.classList.remove('active');
    });

    // Show target step
    document.getElementById(`step-${step}`).classList.add('active');

    // Update step indicators
    document.querySelectorAll('.step').forEach(stepEl => {
        stepEl.classList.remove('active', 'completed');
    });

    const steps = ['upload', 'analyze', 'mapping', 'validate', 'confirm', 'success'];
    const currentIndex = steps.indexOf(step);

    steps.forEach((s, index) => {
        const stepEl = document.querySelector(`.step[data-step="${s}"]`);
        if (!stepEl) return;

        if (index < currentIndex) {
            stepEl.classList.add('completed');
        } else if (index === currentIndex) {
            stepEl.classList.add('active');
        }
    });

    currentStep = step;
}

/**
 * Show loading overlay
 */
function showLoading(message = 'Cargando...') {
    // You can implement a proper loading overlay here
    console.log('Loading:', message);
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    console.log('Loading complete');
}
