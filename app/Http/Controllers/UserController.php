<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class UserController extends Controller
{

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
        } else {
            abort(403, 'No tienes permiso para acceder a esta tienda.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {

        //validacion de acceso a la tienda
        $this->validateStoreAccess($store);

        // Mostrar solo los usuarios de esta tienda
        $roles = Role::all(); // Obtener todos los roles disponibles

        $users = $store->users()->with('roles')->get();
        return view('users.index', compact('users', 'store', 'roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //validacion de acceso a la tienda
        $this->validateStoreAccess($store);

        //formulario para crear usuarios segun roles
        $roles = Role::all(); // Obtener todos los roles disponibles
        // Pasar los roles a la vista
        // Mostrar el formulario de creación de usuario y pasar la tienda
        return view('users.create', compact('store', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $store->company_id,
            'store_id' => $store->id,
        ]);

        // Asignar rol
        $user->assignRole($request->role);

        return redirect()->route('stores.users.index', $store->id) // ← Verificar ruta
            ->with('success', 'Usuario creado exitosamente.');
    }
    /**
     * Display the specified resource.
     */
    public function show(Store $store, User $user)
    {
        $this->validateStoreAccess($store);

        // Verificar que el usuario pertenezca a esta tienda
        if ($user->store_id !== $store->id) {
            abort(404, 'Usuario no encontrado en esta tienda.');
        }

        return view('users.show', compact('user', 'store'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, User $user)
    {
        $this->validateStoreAccess($store);

        // Verificar que el usuario pertenezca a esta tienda
        if ($user->store_id !== $store->id) {
            abort(404, 'Usuario no encontrado en esta tienda.');
        }

        $roles = Role::all();
        return view('users.edit', compact('user', 'store', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, User $user)
    {
        $this->validateStoreAccess($store);

        if ($user->store_id !== $store->id) {
            abort(404, 'Usuario no encontrado en esta tienda.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user->update($request->only('name', 'email'));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return redirect()
            ->route('stores.users.index', $store->id)
            ->with('success', 'Usuario actualizado exitosamente.');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, User $user)
    {
        $this->validateStoreAccess($store);

        // Verificar que el usuario pertenezca a esta tienda (HasMany)
        if ($user->store_id !== $store->id) {
            abort(404, 'Usuario no encontrado en esta tienda.');
        }

        $user->delete();

        return redirect()->route('stores.users.index', $store->id)
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
