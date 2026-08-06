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
        $tiposDte = TipoDte::all();
        $correlativos = CorrelativoStore::where('store_id', $storeId)
            ->with('tipoDte')
            ->get()
            ->keyBy('tipo_documento_id');



        return view('correlativos.edit', compact('store', 'tiposDte', 'correlativos'));
    }


    // Actualizar correlativos
    public function update(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);

        $validatedData = $request->validate([
            'correlativos.*.id' => 'nullable|exists:correlativo_stores,id',
            'correlativos.*.tipo_documento_id' => 'required|exists:tipo_documento,id',

            'correlativos.*.correlativo' => 'integer|min:0',
        ]);


        foreach ($validatedData['correlativos'] as $correlativoData) {
            CorrelativoStore::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'tipo_documento_id' => $correlativoData['tipo_documento_id'],
                ],
                [
                    'correlativo' => $correlativoData['correlativo'],
                ]
            );
        }

        foreach ($request->correlativos as $item) {
        }


        return redirect()
            ->route('correlativos.edit', ['store' => $storeId])
            ->with('success', 'Correlativos actualizados correctamente.');
    }
}
