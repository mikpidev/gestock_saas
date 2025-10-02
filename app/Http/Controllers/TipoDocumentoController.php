<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;


class TipoDocumentoController extends Controller
{
    public function index()
    {
        $tipos = TipoDocumento::all();
        return response()->json($tipos);
    }


}
