<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMustChangePassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Si el usuario está autenticado y debe cambiar su contraseña
        if ($user && $user->must_change_password) {
            // Permitir acceso solo a la ruta de cambio de contraseña y logout
            if (!$request->routeIs('password.change', 'password.update', 'logout')) {
                return redirect()->route('password.change');
            }
        }
        
        return $next($request);
    }
}
