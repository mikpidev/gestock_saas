<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomersController extends Controller
{
    /**
     * Validar acceso a la tienda según el rol del usuario.
     */
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
            if ($store->id != $user->store_id) {
                abort(403, 'No tienes permiso para acceder a esta tienda.');
            }
        } else {
            abort(403, 'No tienes permiso para acceder a esta tienda.');
        }
    }

    /**
     * Mostrar todos los clientes de la tienda.
     */
    public function index(Store $store)
    {
        $this->validateStoreAccess($store);
        $customers = $store->customers()->get();

        return view('customers.index', compact('customers', 'store'));
    }

    /**
     * Formulario para crear un nuevo cliente.
     */
    public function create(Store $store)
    {
        $this->validateStoreAccess($store);
        return view('customers.create', compact('store'));
    }

    /**
     * Guardar un nuevo cliente.
     */
    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);

        $request->validate([
            'nit' => 'required|string|max:14|unique:customers,nit',
            'nrc' => 'nullable|string|max:10',
            'nombre' => 'required|string|max:200',
            'codActividad' => 'required|string|max:10',
            'descActividad' => 'nullable|string|max:255',
            'nombreComercial' => 'nullable|string|max:200',
            'direccion_departamento' => 'required|string|max:2',
            'direccion_municipio' => 'required|string|max:2',
            'direccion_complemento' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:15',
            'correo' => 'nullable|email|max:100',
        ]);

        Customer::create([
            'nit' => $request->nit,
            'nrc' => $request->nrc,
            'nombre' => $request->nombre,
            'codActividad' => $request->codActividad,
            'descActividad' => $request->descActividad,
            'nombreComercial' => $request->nombreComercial,
            'direccion_departamento' => $request->direccion_departamento,
            'direccion_municipio' => $request->direccion_municipio,
            'direccion_complemento' => $request->direccion_complemento,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'store_id' => $store->id,
            'company_id' => $store->company_id,
        ]);

        return redirect()
            ->route('stores.customers.index', $store)
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Mostrar un cliente específico.
     */
    public function show(Store $store, Customer $customer)
    {
        $this->validateStoreAccess($store);

        if ($customer->store_id !== $store->id) {
            abort(403, 'No tienes permiso para acceder a este cliente.');
        }

        return view('customers.show', compact('customer', 'store'));
    }

    /**
     * Formulario de edición de cliente.
     */
    public function edit(Store $store, Customer $customer)
    {
        $this->validateStoreAccess($store);

        if ($customer->store_id !== $store->id) {
            abort(403, 'No tienes permiso para acceder a este cliente.');
        }

        return view('customers.edit', compact('store', 'customer'));
    }

    /**
     * Actualizar cliente.
     */
    public function update(Request $request, Store $store, Customer $customer)
    {
        $this->validateStoreAccess($store);

        if ($customer->store_id !== $store->id) {
            abort(403, 'No tienes permiso para acceder a este cliente.');
        }

        $request->validate([
            'nit' => 'required|string|max:14|unique:customers,nit,' . $customer->id,
            'nrc' => 'nullable|string|max:10',
            'nombre' => 'required|string|max:200',
            'codActividad' => 'required|string|max:10',
            'descActividad' => 'nullable|string|max:255',
            'nombreComercial' => 'nullable|string|max:200',
            'direccion_departamento' => 'required|string|max:2',
            'direccion_municipio' => 'required|string|max:2',
            'direccion_complemento' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:15',
            'correo' => 'nullable|email|max:100',
        ]);

        $customer->update($request->only([
            'nit',
            'nrc',
            'nombre',
            'codActividad',
            'descActividad',
            'nombreComercial',
            'direccion_departamento',
            'direccion_municipio',
            'direccion_complemento',
            'telefono',
            'correo',
        ]));

        return redirect()
            ->route('stores.customers.index', $store)
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Eliminar cliente.
     */
    public function destroy(Store $store, Customer $customer)
    {
        $this->validateStoreAccess($store);

        if ($customer->store_id !== $store->id) {
            abort(403, 'No tienes permiso para acceder a este cliente.');
        }

        $customer->delete();

        return redirect()
            ->route('stores.customers.index', $store)
            ->with('success', 'Cliente eliminado exitosamente.');
    }
}
