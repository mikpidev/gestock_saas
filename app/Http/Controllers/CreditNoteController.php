<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Customer;
use App\Models\ProductType;
use App\Models\InvoiceNumber;
use App\Models\Store;
use App\Models\TipoDte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreditNoteController extends Controller
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
        //solicitar lista de notas de crédito
        $creditNotes = $store->creditNotes()->with(['customer', 'sale', 'user'])->orderByDesc('sale_date')->get();

        return view('creditnotes.index', compact('store', 'creditNotes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($store);
        //mostrar ventas

        $sales = $store->sales()->with('customer')->orderByDesc('sale_date')->get();

        return view('creditnotes.create', compact('store', 'sales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        // Validar acceso a la tienda
        $this->validateStoreAccess($store);
    
        // Validar únicamente los campos necesarios del formulario
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'reason' => 'nullable|string|max:500',
        ]);
    
        // Obtener la venta asociada
        $sale = Sale::with('customer')->findOrFail($data['sale_id']);
    
        // Obtener datos del cliente (puede ser null)
        $customer = $sale->customer;

        $tipoDTE = "05"; // Código para Nota de Crédito Electrónica

    
        // Obtener el correlativo (o tu generador interno)
        $invoiceNumber = InvoiceNumber::getNextNumber($store->id, $tipoDTE);
    
        // Crear la nota de crédito usando la información de la venta
        $creditNote = CreditNote::create([
            'store_id' => $store->id,
            'sale_id' => $sale->id,
            'customers_id' => $customer?->id,
            'sale_date' => $sale->sale_date,
            'user_id' => Auth::id(),
            'credit_note_date' => now(),
            'total_amount' => $sale->total_amount,
            'tax_amount' => $sale->tax_amount,
            'discount_amount' => $sale->discount_amount,
            'net_amount' => $sale->net_amount,
            'numero_control' => $invoiceNumber?->numero_control ?? Str::upper(Str::random(8)),
            'codigo_generacion' => $invoiceNumber?->codigo_generacion ?? Str::uuid(),
            'invoice_number' => $invoiceNumber?->number ?? 'NC-' . now()->timestamp,
            'reason' => $data['reason'] ?? null,
        ]);
    
        // Generar DTE Json (manejo de errores incluido)
        try {
            app(\App\Http\Controllers\DTEController::class)->generarDTECreditNote($creditNote, $sale);
        } catch (\Throwable $e) {
            \Log::error('Error generando DTE: ' . $e->getMessage());
        }
    
        return redirect()
            ->route('stores.creditnotes.index', $store->id)
            ->with('success', 'Nota de crédito creada correctamente.');
    }
    
    /**
     * Display the specified resource.
     */
    public function show(CreditNote $creditNote, Store $store)
    {
        //Validar acceso a la tienda

        $this->validateStoreAccess($store);
        if ($creditNote->store_id != $store->id) abort(403, 'No puedes ver una NC de otra tienda.');

        $creditNote->load('customer', 'sale', 'user');
        return view('credit_notes.show', compact('creditNote', 'store'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CreditNote $creditNote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CreditNote $creditNote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, CreditNote $creditNote)
    {
        $this->validateStoreAccess($store);
        if ($creditNote->store_id != $store->id) abort(403, 'No puedes eliminar una NC de otra tienda.');

        $creditNote->delete();
        return redirect()->route('stores.sales.index', $store->id)
            ->with('success', 'Nota de crédito eliminada correctamente.');
    }


}
