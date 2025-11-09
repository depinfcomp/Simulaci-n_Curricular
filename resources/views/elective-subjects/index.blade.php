@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Materias Optativas Ofertadas
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Materia Optativa
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['total'] }}</h5>
                                    <p class="card-text">Total Optativas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-book fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['fundamental'] }}</h5>
                                    <p class="card-text">Optativas Fundamentales</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['professional'] }}</h5>
                                    <p class="card-text">Optativas Disciplinares</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-laptop-code fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['active'] }}</h5>
                                    <p class="card-text">Activas</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="row">
                <!-- LEFT COLUMN: Professional/Disciplinary Electives (GREEN) -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-laptop-code me-2"></i>
                                Optativas Disciplinares/Profesionales
                                <span class="badge bg-white text-success ms-2">{{ $stats['professional'] }}</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            @if($professionalElectives->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center">Código</th>
                                                <th>Nombre</th>
                                                <th class="text-center">Créditos</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($professionalElectives as $elective)
                                                <tr class="{{ !$elective->is_active ? 'table-secondary' : '' }}">
                                                    <td class="text-center">
                                                        <strong>{{ $elective->code }}</strong>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            {{ $elective->name }}
                                                            @if($elective->description)
                                                                <br><small class="text-muted">{{ Str::limit($elective->description, 60) }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary">{{ $elective->credits }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-{{ $elective->is_active ? 'success' : 'secondary' }}">
                                                            {{ $elective->is_active ? 'Activa' : 'Inactiva' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    onclick="editElective({{ $elective->id }})"
                                                                    title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-{{ $elective->is_active ? 'warning' : 'success' }}"
                                                                    onclick="toggleStatus({{ $elective->id }})"
                                                                    title="{{ $elective->is_active ? 'Desactivar' : 'Activar' }}">
                                                                <i class="fas fa-{{ $elective->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="deleteElective({{ $elective->id }}, '{{ $elective->name }}')"
                                                                    title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h6>No hay optativas disciplinares</h6>
                                    <p class="text-muted mb-0">Agrega materias optativas profesionales</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Fundamental Electives (ORANGE) -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-book-open me-2"></i>
                                Optativas Fundamentales
                                <span class="badge bg-white text-warning ms-2">{{ $stats['fundamental'] }}</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            @if($fundamentalElectives->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center">Código</th>
                                                <th>Nombre</th>
                                                <th class="text-center">Créditos</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fundamentalElectives as $elective)
                                                <tr class="{{ !$elective->is_active ? 'table-secondary' : '' }}">
                                                    <td class="text-center">
                                                        <strong>{{ $elective->code }}</strong>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            {{ $elective->name }}
                                                            @if($elective->description)
                                                                <br><small class="text-muted">{{ Str::limit($elective->description, 60) }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary">{{ $elective->credits }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-{{ $elective->is_active ? 'success' : 'secondary' }}">
                                                            {{ $elective->is_active ? 'Activa' : 'Inactiva' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    onclick="editElective({{ $elective->id }})"
                                                                    title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-{{ $elective->is_active ? 'warning' : 'success' }}"
                                                                    onclick="toggleStatus({{ $elective->id }})"
                                                                    title="{{ $elective->is_active ? 'Desactivar' : 'Activar' }}">
                                                                <i class="fas fa-{{ $elective->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="deleteElective({{ $elective->id }}, '{{ $elective->name }}')"
                                                                    title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h6>No hay optativas fundamentales</h6>
                                    <p class="text-muted mb-0">Agrega materias optativas de fundamentación</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Elective Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Materia Optativa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_code" class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create_code" name="code" required maxlength="10">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_elective_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_elective_type" name="elective_type" required>
                                <option value="">Seleccione...</option>
                                <option value="optativa_profesional">Optativa Disciplinar/Profesional</option>
                                <option value="optativa_fundamental">Optativa Fundamental</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Nombre de la Materia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_name" name="name" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="create_credits" class="form-label">Créditos <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="create_credits" name="credits" required min="1" max="20">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="create_semester" class="form-label">
                                Semestre Sugerido
                                <i class="fas fa-info-circle text-muted" 
                                   title="Placeholder orientativo: las optativas no tienen semestre fijo"></i>
                            </label>
                            <input type="number" class="form-control" id="create_semester" name="semester" min="1" max="10" placeholder="Ej: 7">
                            <small class="text-muted">Campo orientativo (opcional) - Las optativas no tienen semestre fijo</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="create_is_active" class="form-label">Estado</label>
                            <select class="form-select" id="create_is_active" name="is_active">
                                <option value="1" selected>Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_classroom_hours" class="form-label">Horas Presenciales</label>
                            <input type="number" class="form-control" id="create_classroom_hours" name="classroom_hours" value="0" min="0" max="168">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_student_hours" class="form-label">Horas Independientes</label>
                            <input type="number" class="form-control" id="create_student_hours" name="student_hours" value="0" min="0" max="168">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="create_description" name="description" rows="3" maxlength="1000"></textarea>
                        <small class="text-muted">Máximo 1000 caracteres</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Elective Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Materia Optativa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <input type="hidden" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_code" class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_code" name="code" required maxlength="10">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_elective_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_elective_type" name="elective_type" required>
                                <option value="">Seleccione...</option>
                                <option value="optativa_profesional">Optativa Disciplinar/Profesional</option>
                                <option value="optativa_fundamental">Optativa Fundamental</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nombre de la Materia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_credits" class="form-label">Créditos <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_credits" name="credits" required min="1" max="20">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_semester" class="form-label">
                                Semestre Sugerido
                                <i class="fas fa-info-circle text-muted" 
                                   title="Placeholder orientativo: las optativas no tienen semestre fijo"></i>
                            </label>
                            <input type="number" class="form-control" id="edit_semester" name="semester" min="1" max="10" placeholder="Ej: 7">
                            <small class="text-muted">Campo orientativo (opcional) - Las optativas no tienen semestre fijo</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_is_active" class="form-label">Estado</label>
                            <select class="form-select" id="edit_is_active" name="is_active">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_classroom_hours" class="form-label">Horas Presenciales</label>
                            <input type="number" class="form-control" id="edit_classroom_hours" name="classroom_hours" min="0" max="168">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_hours" class="form-label">Horas Independientes</label>
                            <input type="number" class="form-control" id="edit_student_hours" name="student_hours" min="0" max="168">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="1000"></textarea>
                        <small class="text-muted">Máximo 1000 caracteres</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar la materia optativa?</p>
                <p class="text-danger"><strong id="delete_name"></strong></p>
                <p class="text-muted">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-1"></i>
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/elective-subjects.js') }}?v={{ time() }}"></script>
@endpush
