<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class ProductTypeController extends Controller
{

    //validacion de accesos
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
        //validar acceso a la tienda
        $this->validateStoreAccess($store);

        // Mostrar solo los tipos de productos de esta tienda
        $productTypes = ProductType::all();
        return view('productType.index', compact('productTypes', 'store'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($store);

        return view('productType.create', compact('store'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($store);

        // Validar los datos del formulario
        $request->validate(
            [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'category' => 'string|max:255',
                'description' => 'nullable|string',
            ],
            //mensajes personalizados
            [
                'name.required' => 'El nombre es obligatorio.',
                'price.required' => 'El precio es obligatorio.',
                'price.numeric' => 'El precio debe ser un número entero o Decimal.',
                'stock.required' => 'La cantidad es obligatoria.',
                'stock.integer' => 'La cantidad debe ser un número entero.',

            ]
        );

        // Crear el nuevo tipo de producto asociado a la tienda y compañía
        ProductType::create([
            'name' => $request->name,
            'price' => $request->price,
            'stock' => $request->stock,
            'category' => $request->category,
            'description' => $request->description,
            'store_id' => $store->id,
            'company_id' => $store->company_id,
        ]);


        return redirect()
            ->route('stores.product_types.index', $store)
            ->with('success', 'Tipo de producto creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductType $productType)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($productType->store);

        //verificar que usuario pertenezca a esta tienda
        if ($productType->store_id !== Auth::user()->store_id) {
            abort(404, 'Tipo de producto no encontrado en esta tienda.');
        }

        return view('productType.show', compact('productType', 'store'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, ProductType $productType)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($productType->store);

        //verificar que usuario pertenezca a esta tienda
        if ($productType->store_id !== Auth::user()->store_id) {
            abort(404, 'Tipo de producto no encontrado en esta tienda.');
        }
        // Obtener la tienda desde el producto
        $store = $productType->store;

        return view('productType.edit', compact('productType', 'store'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, ProductType $productType)
    {
        //validar que usuario pertenezca a esta tienda
        if ($productType->store_id !== Auth::user()->store_id) {
            abort(404, 'Tipo de producto no encontrado en esta tienda.');
        }

        //validar acceso a la tienda
        $this->validateStoreAccess($store);



        //validar formulario
        $request->validate(
            [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'category' => 'string|max:255',
                'description' => 'nullable|string',
            ],
            //mensajes personalizados
            [
                'name.required' => 'El nombre es obligatorio.',
                'price.required' => 'El precio es obligatorio.',
                'price.numeric' => 'El precio debe ser un número entero o Decimal.',
                'stock.required' => 'La cantidad es obligatoria.',
                'stock.integer' => 'La cantidad debe ser un número entero.',

            ]
        );

        //actualizar
        $productType->update([
            'name' => $request->name,
            'price' => $request->price,
            'stock' => $request->stock,
            'category' => $request->category,
            'description' => $request->description,
        ]);


        //regresar a vista
        return redirect()
            ->route('stores.product_types.index', $store)
            ->with('success', 'Tipo de producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, ProductType $productType)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($productType->store);

        //validar que usuario pertenezca a esta tienda
        if ($productType->store_id !== Auth::user()->store_id) {
            abort(404, 'Tipo de producto no encontrado en esta tienda.');
        }

        $productType->delete();

        //regresar a vista
        return redirect()
            ->route('stores.product_types.index', $productType->store)
            ->with('success', 'Tipo de producto eliminado exitosamente.');
    }

    public function createSale(Store $store)
    {
        // Trae todos los productos de la tienda
        $products = ProductType::where('store_id', $store->id)->get();

        // Inicializa una fila vacía para el formulario
        $oldProducts = old('products', [
            ['id' => '', 'quantity' => 1, 'price' => 0]
        ]);

        return view('sales.create', compact('store', 'products', 'oldProducts'));
    }
}
