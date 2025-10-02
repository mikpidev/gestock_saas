<?php

namespace App\Http\Controllers;

use App\Models\CodActividad;

class ActividadEconomicaController extends Controller
{
    public function index()
    {
        $actividades = CodActividad::all();
        return response()->json($actividades);
    }
}
