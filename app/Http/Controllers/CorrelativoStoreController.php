<?php

namespace App\Http\Controllers;

use App\Models\CorrelativoStore;
use App\Models\Store;
use App\Models\TipoDte;
use Illuminate\Http\Request;

class CorrelativoStoreController extends Controller
{
    // Mostrar formulario de edición
    public function edit($storeId)
    {
        $store = Store::findOrFail($storeId);

        $correlativos = CorrelativoStore::where('store_id', $storeId)
            ->with('tipoDte')
            ->get();



        return view('correlativos.edit', compact('store', 'correlativos'));
    }


    // Actualizar correlativos
    public function update(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);

        $validatedData = $request->validate([
            'correlativos.*.id' => 'required|exists:correlativo_stores,id',
            'correlativos.*.correlativo' => 'required|integer|min:0',
        ]);


        foreach ($validatedData['correlativos'] as $correlativoData) {

            CorrelativoStore::where('id', $correlativoData['id'])
                ->where('store_id', $store->id)
                ->update([
                    'correlativo' => $correlativoData['correlativo'],
                ]);
        }


        return redirect()
            ->route('correlativos.edit', ['store' => $storeId])
            ->with('success', 'Correlativos actualizados correctamente.');
    }
}
