<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Solo SuperAdmin puede acceder a compañías
        if (!$user || !$user->hasRole('SuperAdmin')) {
            abort(403, 'Acceso no autorizado.');
        }

        // Inicializar la sesión con la compañía seleccionada si no existe
        if (!$request->session()->has('selected_company_id')) {
            $firstCompanyId = $user->companies->first()->id ?? null;
            if ($firstCompanyId) {
                $request->session()->put('selected_company_id', $firstCompanyId);
            }
        }

        return $next($request);
    }
}
