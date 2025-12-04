<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Simulación Curricular') }}</title>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link href="{{ asset('css/simulation.css') }}" rel="stylesheet">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @stack('styles')
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <a class="navbar-brand" href="{{ route('simulation.index') }}">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Simulación Curricular
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <!-- Simulación -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('simulation.index') }}">
                                <i class="fas fa-project-diagram me-1"></i>
                                Simulación
                            </a>
                        </li>

                        <!-- Gestión de Mallas -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="mallasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-th-list me-1"></i>
                                Gestión de Mallas
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="mallasDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('convalidation.index') }}">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        Convalidaciones
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('elective-subjects.index') }}">
                                        <i class="fas fa-book-open me-2"></i>
                                        Materias Optativas
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('leveling-subjects.index') }}">
                                        <i class="fas fa-layer-group me-2"></i>
                                        Materias de Nivelación
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('subject-aliases.index') }}">
                                        <i class="fas fa-code-branch me-2"></i>
                                        Alias de Materias
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Gestión de Estudiantes -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="estudiantesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-graduate me-1"></i>
                                Importar
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="estudiantesDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('academic-history.index') }}">
                                        <i class="fas fa-history me-2"></i>
                                        Historias Académicas
                                    </a>
                                </li>
                                <li>
                                    <h6 class="dropdown-header">Importar Datos</h6>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('import.index') }}">
                                        <i class="fas fa-file-excel me-2"></i>
                                        Importar Malla Curricular
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    
                    <!-- User Menu -->
                    <ul class="navbar-nav ms-auto">
                        @auth
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <i class="fas fa-user me-2"></i>
                                            {{ Auth::user()->email }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('password.change') }}">
                                            <i class="fas fa-key me-2"></i>
                                            Cambiar Contraseña
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-sign-out-alt me-2"></i>
                                                Cerrar Sesión
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-4">
            @yield('content')
        </main>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        @stack('scripts')
    </body>
</html>
