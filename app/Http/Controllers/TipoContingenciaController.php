<?php

namespace App\Http\Controllers;

use App\Models\TipoContingencia;
use Illuminate\Http\Request;

class TipoContingenciaController extends Controller
{
    public function index()
    {
        $tipocontingencias = TipoContingencia::all();
        return response()->json($tipocontingencias);
    }


}
