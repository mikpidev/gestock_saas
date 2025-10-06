<?php
namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Validation\Rule;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Sale;

class StoreController extends Controller
{



    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Por favor, inicia sesión.');
        }

        if ($user->hasRole('superadmin')) {
            $companyId = session('selected_company_id');
            $stores = Store::where('company_id', $companyId)->get();
        } elseif ($user->hasRole('admin')) {
            $stores = Store::where('company_id', $user->company_id)->get();
        } else {
            abort(403, 'Acceso no autorizado.');
        }

        return view('store.index', compact('stores'));
    }


    public function create(Company $company)
    {   
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }  
          
        
        // Lógica para mostrar el formulario de creación de tienda ya inclye la compania previamente creada   
        return view('store.create', compact('company'));
    }

    public function store(Request $request, Company $company)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $validated = $request->validate([
            'store_name' => 'required|max:200',
            'address'    => 'required',
            'phone'      => 'required|size:8',
            'manager'    => 'required|max:100',
            'email'      => 'required|email|unique:stores,email',
            'status'     => 'required|in:activa,suspendida,inactiva',
            'comments'   => 'nullable',
        ]);
    
        $store = $company->stores()->create($validated);
    
        return redirect()->route('stores_tax_info.create', ['store' => $store->id])
        ->with('success', 'Tienda creada, ahora crea la información fiscal de la tienda.');

    }
    

    public function show(Store $store)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
    
        // 🔒 Validar acceso según rol
        if ($user->hasRole('superadmin')) {
            $companyId = session('selected_company_id');
            if (!$companyId) abort(403, 'Se requiere compañía.');
            if ($store->company_id != $companyId) abort(403, 'Acceso no autorizado.');
        } elseif ($user->hasRole('admin') || $user->hasRole('user')) {
            if ($store->company_id != $user->company_id) abort(403, 'Acceso no autorizado.');
        } else {
            abort(403, 'Acceso no autorizado.');
        }
    
        // 🔗 Cargar relaciones necesarias
        $store->load(['taxInfo', 'company']);
    
        // 💰 Obtener las últimas 5 ventas de esta tienda
        $ventas = Sale::where('store_id', $store->id)
                        ->latest('created_at')
                        ->take(5)
                        ->get();
    
        // 📤 Enviar los datos a la vista
        return view('store.show', compact('store', 'ventas'));
    }
    

    public function edit(Request $request, Store $store)
    {

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        //$store = Store::findOrFail($id);
        return view('store.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {  
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        } 

        // Validación de los datos del formulario
        $validated = $request->validate([
            'store_name' => 'required|max:200',
            'address' => 'required',
            'phone' => 'required|size:8',
            'manager' => 'required|max:100',
            'email' => ['required','email','max:100', Rule::unique('stores')->ignore($store->id)->whereNull('deleted_at'),],  // Solo verifica registros activos
            'status' => 'required|in:activa,suspendida,inactiva',
            'comments' => 'nullable',
        ]);

        // Actualización de la tienda
        $store->update($validated);

        return redirect()->route('stores.index')->with('success', 'Tienda actualizada exitosamente.');
    }

    public function destroy(Request $request, Store $store)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Lógica para eliminar una tienda (soft delete)
        $store->delete();
        return redirect()->route('store.show')->with('success', 'Tienda eliminada exitosamente.');
    }
}



