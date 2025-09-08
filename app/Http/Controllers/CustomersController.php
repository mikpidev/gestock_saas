<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Store;
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

        // obtener clientes solo de la tienda actual
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

        // validación condicional para NRC
        $request->validate([
            'nombre' => 'required|string|max:200',
            'tipo_documento' => 'required|in:DUI,NIT,Pasaporte',
            'numero_documento' => 'required|string|max:20',
            'nrc' => $request->tipo_cliente === 'Juridico' ? 'required|string|max:20' : 'nullable|string|max:20',
            'razon_social' => 'required|string|max:200',
            'actividad_economica' => 'required|string|max:200',
            'direccion_fiscal' => 'required|string',
            'email' => 'required|email|max:100',
            'telefono' => 'required|string|max:8',
            'tipo_cliente' => 'required|in:Natural,Juridico',
            'comentarios' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento debe ser DUI, NIT o Pasaporte.',
            'email.email' => 'El correo electrónico no es válido.',
            'telefono.max' => 'El teléfono no debe exceder los 8 caracteres.',
            'tipo_cliente.in' => 'El tipo de cliente debe ser Natural o Jurídico.',
        ]);

        // crear cliente
        Customer::create([
            'nombre' => $request->nombre,
            'tipo_documento' => $request->tipo_documento,
            'numero_documento' => $request->numero_documento,
            'nrc' => $request->nrc,
            'razon_social' => $request->razon_social,
            'actividad_economica' => $request->actividad_economica,
            'direccion_fiscal' => $request->direccion_fiscal,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'tipo_cliente' => $request->tipo_cliente,
            'comentarios' => $request->comentarios,
            'store_id' => $store->id,
            'company_id' => $store->company_id,
        ]);

        return redirect()
            ->route('stores.customers.index', $store)
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Mostrar cliente específico.
     */
    public function show(Store $store, Customer $customer)
    {
        $store = $customer->store;
        $this->validateStoreAccess($store);

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
            'nombre' => 'required|string|max:200',
            'tipo_documento' => 'required|in:DUI,NIT,Pasaporte',
            'numero_documento' => 'required|string|max:20',
            'nrc' => $request->tipo_cliente === 'Juridico' ? 'required|string|max:20' : 'nullable|string|max:20',
            'razon_social' => 'required|string|max:200',
            'actividad_economica' => 'required|string|max:200',
            'direccion_fiscal' => 'required|string',
            'email' => 'required|email|max:100',
            'telefono' => 'required|string|max:8',
            'tipo_cliente' => 'required|in:Natural,Juridico',
            'comentarios' => 'nullable|string',
        ]);
    
        $customer->update($request->only([
            'nombre',
            'tipo_documento',
            'numero_documento',
            'nrc',
            'razon_social',
            'actividad_economica',
            'direccion_fiscal',
            'email',
            'telefono',
            'tipo_cliente',
            'comentarios'
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
