<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Customer;
use App\Models\ProductType;
use App\Models\InvoiceNumber;
use App\Models\Store;
use App\Models\TipoDte;
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

    public function index(Store $store)
    {
        $sales = $store->sales()->with(['customer', 'details.productType'])->get();
        return view('sales.index', compact('store', 'sales'));
    }

    public function create(Store $store)
    {
        $this->validateStoreAccess($store);
        $customers = Customer::where('store_id', $store->id)->get();
        $products = ProductType::where('store_id', $store->id)->get();
        $tipoDocumentos = TipoDte::all();
        return view('sales.create', compact('store', 'tipoDocumentos', 'customers', 'products'));
    }

    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);
    
        // Validar request
        $data = $request->validate([
            'customers_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'payment_status' => 'required|in:paid,unpaid,partial',
            'discount_amount' => 'nullable|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:product_types,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'tipo_documento_id' => 'required|exists:tipo_documento,id',
        ]);
    
        // Calcular totales
        $discountAmount = $data['discount_amount'] ?? 0;
        $totalAmount = 0;
        $totalIva = 0;
        $totalGravada = 0;
    
        foreach ($request->products as $p) {
            $product = ProductType::findOrFail($p['id']); // precio seguro

            $cantidad = $p['quantity'];
            $precioConIVA = $p['price'];
            $subtotalConIVA = $cantidad * $precioConIVA;
    
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;
    
            $totalAmount += $subtotalConIVA;
            $totalGravada += $baseSinIVA;
            $totalIva += $ivaItem;
        }
    
        $netAmount = $totalAmount - $discountAmount;
        $total_no_gravado = 0;
        $total_exenta = 0;
        $total_gravada = round($totalGravada, 2);
        $total_iva = round($totalIva, 2);
        
        $tipoDTE = $data['tipo_documento_id'] ? TipoDte::find($data['tipo_documento_id'])->codigo : null;

        // Generar next invoice y número de control
        $invoiceNumber = InvoiceNumber::getNextNumber($store->id, $tipoDTE);

        // Crear la venta
        $sale = Sale::create([
            'customers_id' => $data['customers_id'] ?? null,
            'sale_date' => $data['sale_date'],
            'payment_status' => $data['payment_status'],
            'total_amount' => round($totalAmount, 2),
            'net_amount' => round($netAmount, 2),
            'store_id' => $store->id,
            'user_id' => auth()->id(),
            'tipo_moneda' => 'USD',
            'tipo_operacion' => 1,
            'condicion_operacion' => 1,
            'total_no_gravado' => $total_no_gravado,
            'total_exenta' => $total_exenta,
            'total_gravada' => $total_gravada,
            'total_iva' => $total_iva,
            'numero_control' => $invoiceNumber->numero_control,
            'codigo_generacion' => $invoiceNumber->codigo_generacion,
            'invoice_number' => $invoiceNumber->number,
            'tipo_documento_id' => $data['tipo_documento_id'], // tipo DTE
        ]);
    
        // Crear detalles
        foreach ($request->products as $product) {
            $precioConIVA = $product['price'];
            $subtotalConIVA = $product['quantity'] * $precioConIVA;
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;
    
            $sale->details()->create([
                'product_type_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $precioConIVA,
                'subtotal' => $subtotalConIVA,
                'iva_item' => round($ivaItem, 2),
            ]);
        }
    
        // Generar DTE según tipo de documento
        try {
            app(\App\Http\Controllers\DTEController::class)->generarDTE($sale);
        } catch (\Throwable $e) {
            \Log::error('Error generando DTE: ' . $e->getMessage());
        }
    
        return redirect()->route('stores.sales.index', $store->id)
            ->with('success', 'Venta creada correctamente.');
    }
    
    
    public function show(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);
        if ($sale->store_id != $store->id) abort(403, 'No puedes ver una venta de otra tienda.');

        $sale->load('customer', 'user', 'details.productType');
        return view('sales.show', compact('sale', 'store'));
    }

    public function edit(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);
        if ($sale->store_id != $store->id) abort(403, 'No puedes editar una venta de otra tienda.');

        $customers = Customer::all();
        $products = ProductType::all();
        $sale->load('details.productType');

        return view('sales.edit', compact('sale', 'store', 'customers', 'products'));
    }

    public function update(Request $request, Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);
        if ($sale->store_id != $store->id) abort(403, 'No puedes actualizar una venta de otra tienda.');

        $data = $request->validate([
            'customers_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'tax_amount' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
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

        return redirect()->route('stores.sales.index', $store->id)
            ->with('success', 'Venta actualizada correctamente.');
    }

    public function destroy(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);
        if ($sale->store_id != $store->id) abort(403, 'No puedes eliminar una venta de otra tienda.');

        $sale->delete();
        return redirect()->route('stores.sales.index', $store->id)
            ->with('success', 'Venta eliminada correctamente.');
    }
}
