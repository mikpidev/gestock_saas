<?php

namespace App\Http\Controllers;

use App\Models\MHAccess;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;


class MHAccessController extends Controller
{

    // Validación de accesos
    private function validateStoreAccess(Store $store)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicia sesión.');
        }

        if ($user->hasRole('superadmin')) {
            $companyId = session('selected_company_id');
            if ($store->company_id != $companyId) {
                abort(403, 'No tienes permiso para acceder a esta tienda.');
            }
        } elseif ($user->hasRole('admin')) {
            if ($store->company_id != $user->company_id) {
                abort(403, 'No tienes permiso para acceder a esta tienda.');
            }
        } elseif ($user->hasRole('user')) {
            if ($store->company_id != $user->company_id) {
                abort(403, 'No tienes permiso para acceder a esta tienda.');
            }
        } else {
            abort(403, 'No tienes permiso para acceder a esta tienda.');
        }
    }

    public function index()
    {
        abort(404);
    }

    public function create(Store $store)
    {
        $this->validateStoreAccess($store);

        return view('mh_access.create', compact('store'));
    }

    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);

        $validated = $request->validate([
            'api_key' => 'nullable|string|max:255',
            'password_pri' => 'nullable|string|max:255',
            'port_firma_digital' => 'nullable|integer',
        ]);

        //asignamos el store_id automáticamente
        $validated['store_id'] = $request->input('store_id');

        $store->mh_access()->create($validated);

        return redirect()->route('stores.show', $store->id)->with('success', 'MH Access creado exitosamente.');
    }

    public function show($id)
    {
        abort(404);
    }

    public function edit(Store $store, MHAccess $mhAccess)
    {
        $this->validateStoreAccess($store);

        $mh_access = $store->mhAccess;


        if (!$store) {
            return redirect()->back()->with('error', 'La tienda asociada no existe.');
        }

        return view('mh_access.edit', compact('store', 'mh_access'));
    }

    public function update(Request $request, Store $store, MHAccess $mhAccess)
    {
        $this->validateStoreAccess($store);

        $mhAccess = $store->mh_access;

        // Validar que el mh_access pertenece a la tienda
        if (!$mhAccess) {
            abort(404);
        }


        $validated = $request->validate([
            'api_key' => 'nullable|string|max:255',
            'password_pri' => 'nullable|string|max:255',
            'port_firma_digital' => 'nullable|integer',
        ]);

        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }
        if (empty($validated['password_pri'])) {
            unset($validated['password_pri']);
        }

        $mhAccess->update($validated);

        return redirect()
            ->route('stores.show', $store)
            ->with('success', 'MH Access actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $mh_access = \App\Models\MHAccess::findOrFail($id);
        if ($mh_access->store_id != auth()->user()->store_id) {
            abort(403);
        }

        $mh_access->delete();

        return redirect()->route('stores.show', $mh_access->store_id)->with('success', 'MH Access eliminado exitosamente.');
    }
}
