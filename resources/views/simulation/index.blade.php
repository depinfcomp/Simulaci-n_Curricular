@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/simulation.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="main-title">Malla Curricular - Administración de Sistemas Informáticos</h1>
        
        <!-- Statistics -->
        <div class="stats-container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ \App\Models\Subject::count() }}</div>
                        <div class="stat-label">Total Materias</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">10</div>
                        <div class="stat-label">Semestres</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">{{ \App\Models\Student::count() }}</div>
                        <div class="stat-label">Estudiantes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number" id="affected-percentage">0%</div>
                        <div class="stat-label">Estudiantes Afectados</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Curriculum Controls -->
        <div class="curriculum-controls mb-3">
            <div class="row">
                <div class="col-md-6">
                    <button class="btn btn-success" onclick="addNewSubject()">
                        <i class="fas fa-plus me-1"></i>
                        Agregar Materia
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-info" onclick="exportModifiedCurriculum()">
                        <i class="fas fa-download me-1"></i>
                        Exportar Malla Modificada
                    </button>
                </div>
            </div>
        </div>

        <!-- Curriculum Grid -->
        <div class="curriculum-grid">
            @php
                $subjects = \App\Models\Subject::with(['prerequisites', 'requiredFor'])->orderBy('semester')->get();
                $subjectsBySemester = $subjects->groupBy('semester');
            @endphp

            @for ($semester = 1; $semester <= 10; $semester++)
                <div class="semester-column" data-semester="{{ $semester }}">
                    <div class="semester-title">{{ $semester }}° Semestre</div>
                    <div class="subjects-container subject-list">
                        @if(isset($subjectsBySemester[$semester]))
                            @foreach($subjectsBySemester[$semester] as $subject)
                                <div class="subject-card available" 
                                     draggable="true"
                                     data-subject-id="{{ $subject->code }}"
                                     data-prerequisites="{{ $subject->prerequisites->pluck('code')->implode(',') }}"
                                     data-unlocks="{{ $subject->requiredFor->pluck('code')->implode(',') }}">
                                    <div class="subject-name">{{ $subject->name }}</div>
                                    <div class="subject-code">{{ $subject->code }}</div>
                                    <div class="semester-badge">Semestre {{ $semester }}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endfor
        </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/simulation-fallback.js') }}"></script>
    <script src="{{ asset('js/simulation.js') }}"></script>
    <script src="{{ asset('js/debug.js') }}"></script>
@endpush
