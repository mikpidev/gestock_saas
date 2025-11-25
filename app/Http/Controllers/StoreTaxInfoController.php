<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use App\Models\StoreTaxInfo;
use Illuminate\Http\Request;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;

class StoreTaxInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     * No se usará porque se mostrará en el perfil de la tienda.
     */
    public function index()
    {
        abort(404);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        $actividades = \App\Models\CodActividad::all();
        $departamentos = \App\Models\Departamento::all();
        $municipios = \App\Models\Municipio::all();

        // Pasamos la tienda para asignar company_id automáticamente
        return view('stores_tax_info.create', compact('store', 'actividades', 'departamentos', 'municipios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        $validated = $request->validate([
            'nit' => 'required|max:20',
            'nrc' => 'required|max:20',
            'razon_social' => 'required|max:200',
            'actividad_economica' => 'required|max:200',
            'direccion_fiscal' => 'required|max:200',
            'direccion_departamento' => 'required|exists:departamentos,id',
            'direccion_municipio' => 'required|exists:municipios,id',
            'codActividad' => 'required|exists:cod_actividad,codigo',
            'email' => 'required|email|max:100',
            'telefono' => 'required|max:8',
            'cert_firma_digital' => 'required|max:200',
            'estado' => 'required|in:activo,suspendido,vencido',
            'comentarios' => 'nullable|max:500',
        ]);

        // Asignamos company_id automáticamente
        $validated['company_id'] = $store->company_id;

        // Crear info fiscal
        $store->taxInfo()->create($validated);

        return redirect()->route('stores.show', $store->id)
            ->with('success', 'Información fiscal registrada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        $storeTaxInfo = $store->taxInfo;

        if (!$storeTaxInfo) {
            return redirect()->route('store_tax_info.create', $store->id)
                ->with('info', 'Esta tienda aún no tiene información fiscal.');
        }

        $storeTaxInfo->load('store.company');

        return view('stores_tax_info.show', compact('store', 'storeTaxInfo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, StoreTaxInfo $storeTaxInfo)
    {
        $store = $storeTaxInfo->store;

        if (!$store) {
            return redirect()->back()->with('error', 'La tienda asociada no existe.');
        }

        $company = $store->company;

        $actividades = \App\Models\CodActividad::all();
        $departamentos = \App\Models\Departamento::all();
        $municipios = \App\Models\Municipio::all();

        return view('stores_tax_info.edit', compact('store', 'company', 'storeTaxInfo', 'actividades', 'departamentos', 'municipios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StoreTaxInfo $storeTaxInfo, Company $company)
    {

        $store = $storeTaxInfo->store;


        $validated = $request->validate([
            'nit' => 'required|max:20',
            'nrc' => 'required|max:20',
            'razon_social' => 'required|max:200',
            'actividad_economica' => 'required|max:200',
            'direccion_fiscal' => 'required|max:200',
            'direccion_departamento' => 'required|exists:departamentos,id',
            'direccion_municipio' => 'required|exists:municipios,id',
            'codActividad' => 'required|exists:cod_actividad,codigo',
            'email' => 'required|email|max:100',
            'telefono' => 'required|max:8',
            'cert_firma_digital' => 'required|max:200',
            'estado' => 'required|in:activo,suspendido,vencido',
            'comentarios' => 'nullable|max:500',
        ]);

        // Asignar company automáticamente

        $storeTaxInfo->update($validated);

        return redirect()
            ->route('stores.show', $storeTaxInfo->store_id)
            ->with('success', 'Información fiscal actualizada correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StoreTaxInfo $storeTaxInfo)
    {
        $storeTaxInfo->delete();

        return redirect()->route('stores.show', $storeTaxInfo->store_id)
            ->with('success', 'Información fiscal eliminada correctamente.');
    }
}
