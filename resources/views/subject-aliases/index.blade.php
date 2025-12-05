@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-code-branch"></i> Gestión de Alias de Materias</h2>
            <p class="text-muted">Administra códigos alternativos (alias) para las materias. Los alias permiten importar historias académicas con códigos antiguos que se mapean automáticamente a los códigos actuales.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="obligatory-tab" data-bs-toggle="tab" data-bs-target="#obligatory" type="button" role="tab">
                <i class="fas fa-book"></i> Materias Obligatorias
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="elective-tab" data-bs-toggle="tab" data-bs-target="#elective" type="button" role="tab">
                <i class="fas fa-book-open"></i> Materias Optativas
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Obligatory Subjects Tab -->
        <div class="tab-pane fade show active" id="obligatory" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Créditos</th>
                                    <th>Componente</th>
                                    <th>Alias</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($obligatorySubjects as $subject)
                                <tr>
                                    <td><code>{{ $subject->code }}</code></td>
                                    <td>{{ $subject->name }}</td>
                                    <td><span class="badge bg-info">{{ $subject->credits }} créditos</span></td>
                                    <td>
                                        <span class="badge bg-{{ $subject->component_color }}">
                                            {{ $subject->component_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="alias-list" id="aliases-{{ $subject->code }}">
                                            @if(isset($aliases[$subject->code]))
                                                @foreach($aliases[$subject->code] as $alias)
                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                    {{ $alias->alias_code }}
                                                    <button type="button" class="btn-close btn-close-sm ms-1" 
                                                            onclick="deleteAlias({{ $alias->id }})" 
                                                            style="font-size: 0.6rem; vertical-align: middle;"></button>
                                                </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted fst-italic">Sin alias</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="showAddAliasModal('{{ $subject->code }}', '{{ $subject->name }}', 'obligatory')">
                                            <i class="fas fa-plus"></i> Agregar Alias
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Elective Subjects Tab -->
        <div class="tab-pane fade" id="elective" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Créditos</th>
                                    <th>Tipo</th>
                                    <th>Alias</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($electiveSubjects as $subject)
                                <tr>
                                    <td><code>{{ $subject->code }}</code></td>
                                    <td>{{ $subject->name }}</td>
                                    <td><span class="badge bg-info">{{ $subject->credits }} créditos</span></td>
                                    <td>
                                        <span class="badge bg-{{ $subject->type_color }} {{ $subject->type_color === 'warning' ? 'text-dark' : '' }}">
                                            {{ $subject->type_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="alias-list" id="aliases-{{ $subject->code }}">
                                            @if(isset($aliases[$subject->code]))
                                                @foreach($aliases[$subject->code] as $alias)
                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                    {{ $alias->alias_code }}
                                                    <button type="button" class="btn-close btn-close-sm ms-1" 
                                                            onclick="deleteAlias({{ $alias->id }})" 
                                                            style="font-size: 0.6rem; vertical-align: middle;"></button>
                                                </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted fst-italic">Sin alias</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="showAddAliasModal('{{ $subject->code }}', '{{ $subject->name }}', 'elective')">
                                            <i class="fas fa-plus"></i> Agregar Alias
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar alias -->
<div class="modal fade" id="addAliasModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Alias</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAliasForm">
                    <input type="hidden" id="subject_code" name="subject_code">
                    <input type="hidden" id="subject_type" name="subject_type">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Materia:</label>
                        <p id="subject_name" class="text-muted"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alias_code" class="form-label">Código Alias <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="alias_code" name="alias_code" required 
                               placeholder="Ej: 4200924">
                        <small class="form-text text-muted">Código antiguo o alternativo de la materia</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (Opcional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Ej: Código usado hasta 2020"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveAlias()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
        </div>
</div>

<script src="{{ asset('js/subject-aliases.js') }}?v={{ time() }}"></script>
<script>
    // Initialize with Laravel routes and CSRF token
    initSubjectAliases({
        storeRoute: '{{ route("subject-aliases.store") }}',
        csrfToken: '{{ csrf_token() }}'
    });
</script>

@endsection
