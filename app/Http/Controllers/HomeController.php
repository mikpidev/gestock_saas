<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;



class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Super admin: accede a todas las compañías
        if ($user->hasRole('superadmin')) {
            return redirect()->route('companies.index');
        }

        // Admin: accede a la tienda asignada
        if ($user->hasRole('admin')) {
            $store = $user->store; // asegúrate que la relación está definida
            return redirect()->route('stores.index', $store->id);
        }

        //User: accede a la tienda que pertenece
        if($user->hasRole('user')){
            $store = $user->store;
            return redirect()->route('stores.show', $store->id);
        }

        // Otros roles: 403
        abort(403, 'Acceso no autorizado');
    }
}
