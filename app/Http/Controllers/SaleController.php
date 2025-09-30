<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Customer;
use App\Models\ProductType;
use App\Models\InvoiceNumber;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
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

        } else {
            abort(403, 'No tienes permiso para acceder a esta tienda.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        $sales = $store->sales()->with('customer')->get();
        $sales = $store->sales()->with(['customer', 'invoiceNumber'])->get();
        return view('sales.index', compact('store', 'sales'));
    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        $this->validateStoreAccess($store);
    
        $customers = Customer::where('store_id', $store->id)->get();
        $products = ProductType::where('store_id', $store->id)->get();
        
    
        return view('sales.create', compact('store', 'customers', 'products'));
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);
    
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'tax_amount' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'payment_status' => 'required|in:paid,unpaid,partial',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:product_types,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);
    
        // Calcular total
        $totalAmount = 0;
        foreach ($request->products as $product) {
            $totalAmount += $product['quantity'] * $product['price'];
        }
    
        // Aplicar descuento o impuesto si los hay
        $taxAmount = $data['tax_amount'] ?? 0;
        $discountAmount = $data['discount_amount'] ?? 0;
        $netAmount = $totalAmount + $taxAmount - $discountAmount;
    
        $data['total_amount'] = $totalAmount;
        $data['net_amount'] = $netAmount;
    
        // Asignar tienda y usuario
        $data['store_id'] = $store->id;
        $data['user_id'] = Auth::id();
    
        // Generar Invoice number
        $data['invoice_number_id'] = InvoiceNumber::getNextNumber($store->id);
    
        // Crear venta cabecera
        $sale = Sale::create($data);
    
        // Crear detalles del producto
        foreach ($request->products as $product) {
            $sale->details()->create([
                'product_type_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'subtotal' => $product['quantity'] * $product['price'],
            ]);
        }
    
        return redirect()->route('stores.sales.index', $store->id)
        ->with('success', 'Venta creada correctamente.');
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);

        if ($sale->store_id != $store->id) {
            abort(403, 'No puedes ver una venta de otra tienda.');
        }

        $sale->load('customer', 'user', 'invoiceNumber', 'details.productType');

        return view('sales.show', compact('sale', 'store'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);

        if ($sale->store_id != $store->id) {
            abort(403, 'No puedes editar una venta de otra tienda.');
        }

        $customers = Customer::all();
        $products = ProductType::all();
        $sale->load('details.productType');

        return view('sales.edit', compact('sale', 'store', 'customers', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);

        if ($sale->store_id != $store->id) {
            abort(403, 'No puedes actualizar una venta de otra tienda.');
        }

        $data = $request->validate([
            'customers_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'total_amount' => 'required|numeric',
            'tax_amount' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'net_amount' => 'required|numeric',
            'payment_status' => 'required|in:paid,unpaid,partial',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:product_types,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $sale->update($data);

        // Reemplazar detalles antiguos
        $sale->details()->delete();

        foreach ($request->products as $product) {
            $sale->details()->create([
                'product_type_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'subtotal' => $product['quantity'] * $product['price'],
            ]);
        }

        return redirect()->route('sales.index', $store->id)
            ->with('success', 'Venta actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);

        if ($sale->store_id != $store->id) {
            abort(403, 'No puedes eliminar una venta de otra tienda.');
        }

        $sale->delete();

        return redirect()->route('stores.sales.index', $store->id)
        ->with('success', 'Venta creada correctamente.');
    }
}
