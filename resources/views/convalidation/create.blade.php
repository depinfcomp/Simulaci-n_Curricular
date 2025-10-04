@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-upload me-2 text-primary"></i>
                        Cargar Malla Curricular Externa
                    </h1>
                    <p class="text-muted mb-0">Sube un archivo Excel con la malla curricular para realizar convalidaciones</p>
                </div>
                <div>
                    <a href="{{ route('convalidation.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Upload Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-excel me-2"></i>
                                Información de la Malla
                            </h5>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('convalidation.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="name" class="form-label">Nombre de la Malla Curricular *</label>
                                        <input type="text" 
                                               class="form-control @error('name') is-invalid @enderror" 
                                               id="name" 
                                               name="name" 
                                               value="{{ old('name') }}"
                                               placeholder="Ej: Ingeniería de Sistemas - Universidad XYZ"
                                               required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="institution" class="form-label">Institución</label>
                                        <input type="text" 
                                               class="form-control @error('institution') is-invalid @enderror" 
                                               id="institution" 
                                               name="institution" 
                                               value="{{ old('institution') }}"
                                               placeholder="Nombre de la universidad o institución">
                                        @error('institution')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="excel_file" class="form-label">Archivo CSV *</label>
                                        <input type="file" 
                                               class="form-control @error('excel_file') is-invalid @enderror" 
                                               id="excel_file" 
                                               name="excel_file" 
                                               accept=".csv"
                                               required>
                                        @error('excel_file')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            <i class="fas fa-download me-1"></i>
                                            <a href="{{ asset('templates/plantilla_malla_externa.csv') }}" 
                                               class="text-decoration-none" 
                                               download="plantilla_malla_externa.csv">
                                                Descargar plantilla CSV de ejemplo
                                            </a>
                                        </div>
                                    </div> 
                                               accept=".csv,.txt"
                                               required>
                                        @error('excel_file')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            Formatos permitidos: .csv (máximo 10MB)
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label for="description" class="form-label">Descripción</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="3"
                                                  placeholder="Descripción adicional de la malla curricular (opcional)">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-upload me-2"></i>
                                        Cargar Malla Curricular
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Formato del Archivo CSV
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>El archivo CSV debe contener las siguientes columnas en la primera fila:</p>
                            
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Columna</th>
                                            <th>Requerida</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>codigo</code></td>
                                            <td><span class="badge bg-danger">Sí</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>nombre</code></td>
                                            <td><span class="badge bg-danger">Sí</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>creditos</code></td>
                                            <td><span class="badge bg-warning">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>semestre</code></td>
                                            <td><span class="badge bg-warning">No</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>descripcion</code></td>
                                            <td><span class="badge bg-warning">No</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Ejemplo de estructura:
                                </h6>
                                <small>
                                    <strong>Fila 1:</strong> codigo | nombre | creditos | semestre<br>
                                    <strong>Fila 2:</strong> INF101 | Introducción a la Informática | 3 | 1<br>
                                    <strong>Fila 3:</strong> MAT101 | Matemáticas I | 4 | 1
                                </small>
                            </div>

                            <button type="button" class="btn btn-outline-success btn-sm w-100" onclick="downloadTemplate()">
                                <i class="fas fa-download me-2"></i>
                                Descargar Plantilla CSV
                            </button>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog me-2"></i>
                                Proceso de Convalidación
                            </h5>
                        </div>
                        <div class="card-body">
                            <ol class="ps-3">
                                <li class="mb-2">Cargar el archivo CSV con las materias</li>
                                <li class="mb-2">Revisar las materias importadas</li>
                                <li class="mb-2">Realizar convalidaciones manuales una por una</li>
                                <li class="mb-2">Especificar equivalencias o marcar como libre elección</li>
                                <li class="mb-2">Generar reporte final de convalidaciones</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/convalidation-create.js') }}"></script>
@endpush
