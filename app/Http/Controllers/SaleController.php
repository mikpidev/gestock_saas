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
use Illuminate\Support\Str;

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
        return view('sales.create', compact('store', 'customers', 'products'));
    }

    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);
    
        // Validar request
        $data = $request->validate([
            'customers_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'tax_amount' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'payment_status' => 'required|in:paid,unpaid,partial',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:product_types,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);
    
        // Calcular totales
        $totalAmount = collect($request->products)->sum(function ($p) {
            return $p['quantity'] * $p['price'];
        });
    
        $taxAmount = $data['tax_amount'] ?? 0;
        $discountAmount = $data['discount_amount'] ?? 0;
        $netAmount = $totalAmount + $taxAmount - $discountAmount;
    
        // Totales para DTE
        $total_no_gravado = 0;
        $total_exenta = 0;
        $total_gravada = $netAmount;
        $total_iva = $taxAmount;
    
        // Obtener siguiente número de factura
        $invoiceNumber = InvoiceNumber::getNextNumber($store->id);
    
        // Generar número de control siguiendo patrón aceptado por Hacienda
        $prefix = "DTE-01-";
        
        // Parte central: S###P###
        $partCentral = 'S' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT) 
                       . 'P' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        // Parte final: 15 dígitos numéricos
        $partFinal = str_pad(rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
        
        // Combinar todo
        $numeroControl = $prefix . $partCentral . '-' . $partFinal;
        
        // Código de generación: UUID en mayúsculas
        $codigoGeneracion = strtoupper(Str::uuid()->toString());
        
    
        // Crear venta
        $sale = Sale::create([
            'customers_id' => $data['customers_id'] ?? null,
            'sale_date' => $data['sale_date'],
            'payment_status' => $data['payment_status'],
            'total_amount' => $totalAmount,
            'net_amount' => $netAmount,
            'store_id' => $store->id,
            'user_id' => Auth::id(),
            'tipo_moneda' => 'USD',
            'tipo_operacion' => 1,
            'condicion_operacion' => 1,
            'total_no_gravado' => $total_no_gravado,
            'total_exenta' => $total_exenta,
            'total_gravada' => $total_gravada,
            'total_iva' => $total_iva,
            'numero_control' => $numeroControl,
            'codigo_generacion' => $codigoGeneracion,
            'invoice_number' => $invoiceNumber->number,
        ]);
    
        // Crear detalles de venta
        foreach ($request->products as $product) {
            $sale->details()->create([
                'product_type_id' => $product['id'],
                'quantity' => $product['quantity'],
                'unit_price' => $product['price'],
                'subtotal' => $product['quantity'] * $product['price'],
            ]);
        }
    
    
        // Generar DTE
        try {
            app(\App\Http\Controllers\DTEController::class)->generarDTE($sale);
        } catch (\Throwable $e) {
            // Log si falla la generación del DTE, pero no interrumpe la venta
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
