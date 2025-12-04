<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cambiar Contraseña - Sistema de Convalidaciones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
</head>
<body class="gradient-animation min-h-screen flex items-center justify-center p-4">
    <div class="change-password-card glass-effect rounded-2xl shadow-2xl w-full max-w-md p-8">
        <!-- Icono de Advertencia -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 mb-4 shadow-lg pulse-warning">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                Cambio de Contraseña Obligatorio
            </h1>
            <p class="text-gray-600 text-sm">
                Por seguridad, debes cambiar tu contraseña por defecto
            </p>
        </div>

        <!-- Alerta de Seguridad -->
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-r-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-sm text-yellow-800">
                        <strong>Importante:</strong> Estás usando una contraseña temporal. Por favor, crea una contraseña segura para proteger tu cuenta.
                    </p>
                </div>
            </div>
        </div>

        <!-- Mensajes de Error -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800">Errores en el formulario</h3>
                        <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Formulario de Cambio de Contraseña -->
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Contraseña Actual -->
            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                    Contraseña Actual
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input 
                        id="current_password" 
                        type="password" 
                        name="current_password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña actual"
                        class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none transition duration-200 text-gray-900"
                    >
                </div>
                @error('current_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Nueva Contraseña -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                    Nueva Contraseña
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                        class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none transition duration-200 text-gray-900"
                    >
                </div>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Usa al menos 8 caracteres con letras, números y símbolos
                </p>
            </div>

            <!-- Confirmar Nueva Contraseña -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                    Confirmar Nueva Contraseña
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <input 
                        id="password_confirmation" 
                        type="password" 
                        name="password_confirmation" 
                        required 
                        autocomplete="new-password"
                        placeholder="Repite la nueva contraseña"
                        class="input-focus block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none transition duration-200 text-gray-900"
                    >
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button 
                    type="submit" 
                    class="btn-primary w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Actualizar Contraseña
                    </span>
                </button>
            </div>
        </form>

        <!-- Logout Option -->
        <div class="mt-6 text-center">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:text-gray-800 underline">
                    Cerrar sesión
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-6 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500">
                Universidad Nacional de Colombia
            </p>
        </div>
    </div>
</body>
</html>
