@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/simulation.css') }}?v={{ time() }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="main-title">Malla Curricular - Administración de Sistemas Informáticos</h1>
        
        <!-- Legend -->
        <div class="mb-4">
            <div class="d-flex align-items-center gap-4 justify-content-center flex-wrap" style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #ff9800 0%, #ffb74d 50%, #ffe0b2 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Fundamentales</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #ff9800 0%, #ffb74d 50%, #ffe0b2 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Optativas Fundamentación</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #66bb6a 0%, #81c784 50%, #c8e6c9 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Profesionales</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #66bb6a 0%, #81c784 50%, #c8e6c9 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Optativas Profesionales</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #df8c9d 0%, #e9b5bf 50%, #f5dbdf 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Nivelación</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #66bb6a 0%, #81c784 50%, #c8e6c9 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Trabajo de Grado</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 50px; height: 30px; background: linear-gradient(90deg, #42a5f5 0%, #64b5f6 50%, #bbdefb 100%); border: 1px solid #ddd; border-radius: 4px;"></div>
                    <span style="font-weight: 500;">Libre Elección</span>
                </div>
                <div class="d-flex align-items-center gap-3 ms-4" style="border-left: 2px solid #ddd; padding-left: 20px;">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 25px; height: 25px; background: #e53935; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                            </svg>
                        </div>
                        <span style="font-weight: 500;">Obligatoria</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 25px; height: 25px; background: #42a5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </div>
                        <span style="font-weight: 500;">Optativa</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-4" style="border-left: 2px solid #ddd; padding-left: 20px; font-size: 11px;">
                    <div style="display: flex; gap: 8px;">
                        <div style="text-align: center; padding: 3px 6px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="font-size: 13px; font-weight: bold;">4</div>
                            <div style="font-size: 8px; color: #666;">Créditos</div>
                        </div>
                        <div style="text-align: center; padding: 3px 6px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="font-size: 13px; font-weight: bold;">4</div>
                            <div style="font-size: 8px; color: #666;">Hora presencial</div>
                        </div>
                        <div style="text-align: center; padding: 3px 6px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="font-size: 13px; font-weight: bold;">4</div>
                            <div style="font-size: 8px; color: #666;">Horas estudiante</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="row">
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number">{{ \App\Models\Subject::count() }}</div>
                        <div class="stat-label">Total Materias</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number" id="career-credits">{{ \App\Models\Subject::where('type', '!=', 'nivelacion')->sum('credits') }}</div>
                        <div class="stat-label">Créditos Carrera</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number" id="total-credits">{{ \App\Models\Subject::sum('credits') }}</div>
                        <div class="stat-label">Créditos Totales</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number">10</div>
                        <div class="stat-label">Semestres</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number">{{ \App\Models\Student::count() }}</div>
                        <div class="stat-label">Estudiantes</div>
                    </div>
                </div>
                <div class="col-md-2">
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
                    <button class="btn btn-primary ms-2" onclick="showComponentCredits()">
                        <i class="fas fa-chart-bar me-1"></i>
                        Créditos por Componente
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
                                <div class="subject-card {{ $subject->type }}" 
                                     draggable="true"
                                     data-subject-id="{{ $subject->code }}"
                                     data-type="{{ $subject->type }}"
                                     data-prerequisites="{{ $subject->prerequisites->pluck('code')->implode(',') }}"
                                     data-unlocks="{{ $subject->requiredFor->pluck('code')->implode(',') }}">
                                    
                                    <!-- Header with info boxes -->
                                    <div class="subject-card-header">
                                        <div class="info-box">
                                            <span class="info-value">{{ $subject->credits }}</span>
                                        </div>
                                        <div class="info-box">
                                            <span class="info-value">{{ $subject->classroom_hours }}</span>
                                        </div>
                                        <div class="info-box">
                                            <span class="info-value">{{ $subject->student_hours }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Body with subject name -->
                                    <div class="subject-card-body">
                                        <div class="subject-name">{{ $subject->name }}</div>
                                    </div>
                                    
                                    <!-- Footer with code and icon -->
                                    <div class="subject-card-footer">
                                        <div class="subject-code">{{ $subject->code }}</div>
                                        <div class="subject-icon {{ $subject->is_required ? 'required' : 'elective' }}">
                                            @if($subject->is_required)
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                                                </svg>
                                            @else
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endfor
        </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/simulation-fallback.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/simulation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/debug.js') }}?v={{ time() }}"></script>
    <script>
        // Debug: Check subject types and colors
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== COLOR DEBUG ===');
            const cards = document.querySelectorAll('.subject-card');
            console.log(`Total cards found: ${cards.length}`);
            
            const typeCount = {};
            cards.forEach(card => {
                const type = card.dataset.type || 'undefined';
                const classes = card.className;
                const hasTypeClass = card.classList.contains('fundamental') || 
                                    card.classList.contains('profesional') || 
                                    card.classList.contains('optativa_profesional') ||
                                    card.classList.contains('optativa_fundamentacion') ||
                                    card.classList.contains('nivelacion') ||
                                    card.classList.contains('trabajo_grado') ||
                                    card.classList.contains('libre_eleccion');
                
                typeCount[type] = (typeCount[type] || 0) + 1;
                
                if (!hasTypeClass) {
                    console.warn(`Card ${card.dataset.subjectId} has no type class! Classes: ${classes}`);
                }
            });
            
            console.log('Type distribution:', typeCount);
            console.log('Sample card styles:');
            if (cards.length > 0) {
                const firstCard = cards[0];
                console.log(`  Card: ${firstCard.dataset.subjectId}`);
                console.log(`  Type: ${firstCard.dataset.type}`);
                console.log(`  Classes: ${firstCard.className}`);
                console.log(`  Background: ${window.getComputedStyle(firstCard).background.substring(0, 100)}`);
            }
            console.log('=== END COLOR DEBUG ===');
        });
    </script>

    <!-- Modal: Créditos por Componente -->
    <div class="modal fade" id="componentCreditsModal" tabindex="-1" aria-labelledby="componentCreditsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="componentCreditsModalLabel">
                        <i class="fas fa-chart-bar me-2"></i>Créditos por Componente Curricular
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="component-credits-table">
                        <div class="credit-row">
                            <span class="component-name">DISCIPLINAR OPTATIVA</span>
                            <span class="component-credits" id="credit-optativa-profesional">0</span>
                        </div>
                        <div class="credit-row">
                            <span class="component-name">FUND. OBLIGATORIA</span>
                            <span class="component-credits" id="credit-fundamental">0</span>
                        </div>
                        <div class="credit-row">
                            <span class="component-name">FUND. OPTATIVA</span>
                            <span class="component-credits" id="credit-optativa-fundamentacion">0</span>
                        </div>
                        <div class="credit-row">
                            <span class="component-name">DISCIPLINAR OBLIGATORIA</span>
                            <span class="component-credits" id="credit-profesional">0</span>
                        </div>
                        <div class="credit-row">
                            <span class="component-name">LIBRE ELECCIÓN</span>
                            <span class="component-credits" id="credit-libre-eleccion">0</span>
                        </div>
                        <div class="credit-row">
                            <span class="component-name">TRABAJO DE GRADO</span>
                            <span class="component-credits" id="credit-trabajo-grado">0</span>
                        </div>
                        <div class="credit-row total-row">
                            <span class="component-name"><strong>TOTAL</strong></span>
                            <span class="component-credits"><strong id="credit-total">0</strong></span>
                        </div>
                        <div class="credit-row separator-row">
                            <span class="component-name">NIVELACIÓN</span>
                            <span class="component-credits" id="credit-nivelacion">0</span>
                        </div>
                        <div class="credit-row grand-total-row">
                            <span class="component-name"><strong>TOTAL ESTUDIANTE</strong></span>
                            <span class="component-credits"><strong id="credit-grand-total">0</strong></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .component-credits-table {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .credit-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .credit-row:hover {
            background: #e9ecef;
        }
        
        .component-name {
            font-size: 14px;
            color: #495057;
        }
        
        .component-credits {
            font-size: 16px;
            font-weight: 600;
            color: #0d6efd;
            min-width: 50px;
            text-align: right;
        }
        
        .total-row {
            background: #e7f1ff;
            border: 2px solid #0d6efd;
            margin-top: 8px;
        }
        
        .total-row .component-name,
        .total-row .component-credits {
            color: #0d6efd;
            font-size: 16px;
        }
        
        .separator-row {
            background: #fff3cd;
            border: 1px solid #ffc107;
            margin-top: 8px;
        }
        
        .separator-row .component-name,
        .separator-row .component-credits {
            color: #664d03;
        }
        
        .grand-total-row {
            background: #d1e7dd;
            border: 2px solid #198754;
            margin-top: 4px;
        }
        
        .grand-total-row .component-name,
        .grand-total-row .component-credits {
            color: #198754;
            font-size: 17px;
        }

        /* Prerequisite Selector Styles */
        .prerequisite-card {
            background: #ffffff;
            border: 2px solid #dee2e6 !important;
        }
        
        .prerequisite-card:hover {
            background: #f8f9fa;
            border-color: #0d6efd !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .prerequisite-card.selected {
            background: linear-gradient(135deg, #e7f3ff 0%, #cfe7ff 100%) !important;
            border-color: #0d6efd !important;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }
        
        .prerequisite-card.selected:hover {
            background: linear-gradient(135deg, #d0e9ff 0%, #b8dcff 100%) !important;
        }
        
        .prerequisite-card .fa-check-circle {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .prerequisite-card.selected .fa-check-circle {
            animation: checkPulse 0.5s ease;
        }
        
        @keyframes checkPulse {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
@endpush
