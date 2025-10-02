<?php

namespace App\Http\Controllers;

use App\Models\Municipio;

class MunicipioController extends Controller
{
    public function index()
    {
        $municipios = Municipio::all();
        return response()->json($municipios);
    }

    // Para obtener municipios por departamento (útil en el formulario)
    public function byDepartamento($codigo)
    {
        $municipios = Municipio::where('codigo_departamento', $codigo)->get();
        return response()->json($municipios);
    }
}
