@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-layer-group me-2"></i>
                    Materias de Nivelación
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Materia de Nivelación
                </button>
            </div>


            <!-- Important Information Alert -->
            <div class="alert alert-info mb-4">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>
                    Información Importante sobre Materias de Nivelación
                </h6>
                <hr>
                <ul class="mb-0">
                    <li><strong>Materias "Oficiales":</strong> Solo las materias creadas directamente <strong>desde la malla de simulación</strong> serán mostradas con el badge <span class="badge bg-primary"><i class="fas fa-graduation-cap"></i> Oficial</span>.</li>
                    <li><strong>Materias creadas aquí:</strong> Las materias que agregues en este apartado <strong>NO aparecerán en la malla de simulación</strong>.</li>
                    <li><strong>Importación de historia académica:</strong> <strong>Todas las materias</strong> (oficiales y no oficiales) serán tomadas en cuenta durante el proceso de importación de historias académicas.</li>
                </ul>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-pink text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['total'] }}</h5>
                                    <p class="card-text">Total Materias</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-layer-group fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $stats['in_curriculum'] }}</h5>
                                    <p class="card-text">En Malla Oficial</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-graduation-cap fa-2x opacity-75"></i>
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
                                    <h5 class="card-title">{{ $stats['total_credits'] }}</h5>
                                    <p class="card-text">Créditos Totales</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calculator fa-2x opacity-75"></i>
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
                                    <h5 class="card-title">12</h5>
                                    <p class="card-text">Créditos Mínimos</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leveling Subjects Table -->
            <div class="card">
                <div class="card-header bg-pink text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Materias de Nivelación Registradas
                        <span class="badge bg-white text-pink ms-2">{{ $levelingSubjects->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($levelingSubjects->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Código</th>
                                        <th>Nombre</th>
                                        <th class="text-center">Créditos</th>
                                        <th class="text-center">Horas Clase</th>
                                        <th class="text-center">Horas Estudiante</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($levelingSubjects as $leveling)
                                        <tr>
                                            <td class="text-center">
                                                <strong>{{ $leveling->code }}</strong>
                                                @if($leveling->is_in_official_curriculum)
                                                    <br><span class="badge bg-primary badge-sm mt-1" title="Esta materia fue creada desde la malla de simulación">
                                                        <i class="fas fa-graduation-cap"></i> Oficial
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    {{ $leveling->name }}
                                                    @if($leveling->description)
                                                        <br><small class="text-muted">{{ Str::limit($leveling->description, 60) }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $leveling->credits }}</span>
                                            </td>
                                            <td class="text-center">{{ $leveling->classroom_hours }}h</td>
                                            <td class="text-center">{{ $leveling->student_hours }}h</td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="editLeveling({{ $leveling->id }})"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteLeveling({{ $leveling->id }}, '{{ $leveling->name }}')"
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
                            <h6>No hay materias de nivelación registradas</h6>
                            <p class="text-muted mb-0">Comienza agregando materias que los estudiantes necesitan para nivelarse</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#createModal">
                                <i class="fas fa-plus me-2"></i>
                                Agregar Primera Materia
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Materia de Nivelación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_code" class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create_code" name="code" required maxlength="10" placeholder="Ej: 1000044">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_credits" class="form-label">Créditos <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="create_credits" name="credits" required min="1" max="20" value="3">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Nombre de la Materia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_name" name="name" required maxlength="255" placeholder="Ej: INGLÉS I">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_classroom_hours" class="form-label">Horas de Clase</label>
                            <input type="number" class="form-control" id="create_classroom_hours" name="classroom_hours" value="4" min="0" max="168">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_student_hours" class="form-label">Horas de Trabajo Estudiante</label>
                            <input type="number" class="form-control" id="create_student_hours" name="student_hours" value="5" min="0" max="168">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="create_description" name="description" rows="3" maxlength="1000" placeholder="Descripción opcional"></textarea>
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Materia de Nivelación
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
                            <label for="edit_credits" class="form-label">Créditos <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_credits" name="credits" required min="1" max="20">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nombre de la Materia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_classroom_hours" class="form-label">Horas de Clase</label>
                            <input type="number" class="form-control" id="edit_classroom_hours" name="classroom_hours" min="0" max="168">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_student_hours" class="form-label">Horas de Trabajo Estudiante</label>
                            <input type="number" class="form-control" id="edit_student_hours" name="student_hours" min="0" max="168">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="1000"></textarea>
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
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar la materia de nivelación?</p>
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

<style>
.bg-pink {
    background-color: #ff69b4 !important;
}
.text-pink {
    color: #ff69b4 !important;
}
</style>

@endsection

@push('scripts')
    <script src="{{ asset('js/leveling-subjects.js') }}?v={{ time() }}"></script>
@endpush
