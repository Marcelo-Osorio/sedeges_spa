<?php

namespace App\Http\Middleware;

use App\Models\Modulo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermisoUsuarioMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (Auth::check()) {
            $permisos = Auth::user()->permisos;
            // Log::debug($request->route()->getName());
            // Log::debug($request->route()->getPrefix());
            $prefijo = $request->route()->getPrefix();
            $nom_ruta = $request->route()->getName();

            $continuar = true;

            if ($prefijo == 'admin') {
                if (is_array($permisos)) {
                    $modulos = Modulo::pluck("nombre")->toArray();
                    if (in_array($nom_ruta, $modulos)) {
                        if (!in_array($nom_ruta, $permisos)) {
                            $continuar = false;
                        }
                    }
                }
            }

            if (!$continuar) {
                abort(401, "Acceso denegado");
            }
        }

        return $next($request);
    }
}
