<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CreditNoteDetail;
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
        $creditNotes = $store->creditNotes()->with(['customer', 'sale', 'user'])->orderBy('created_at', 'desc')->get();

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

        $sales = Sale::where('store_id', $store->id)
        ->orderBy('created_at', 'desc') // 👈 Ordena por fecha descendente
        ->get();

        return view('creditnotes.create', compact('store', 'sales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        // Validar acceso a la tienda
        $this->validateStoreAccess($store);

        // Validar campos incluyendo los items a acreditar
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'credit_note_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.sale_detail_id' => 'required|exists:sale_details,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // Obtener la venta asociada con sus detalles
        $sale = Sale::with(['customer', 'details', 'details.productType'])->findOrFail($data['sale_id']);
        $customer = $sale->customer;

        $tipoDTE = "05"; // Código para Nota de Crédito Electrónica

        // Calcular totales basados en los items a acreditar
        $totales = $this->calcularTotalesNotaCredito($data['items'], $sale);

        // Obtener el correlativo
        $invoiceNumber = InvoiceNumber::getNextNumber($store->id, $tipoDTE);



        // Crear la nota de crédito
        $creditNote = CreditNote::create([
            'store_id' => $store->id,
            'sale_id' => $sale->id,
            'customers_id' => $customer?->id,
            'sale_date' => $sale->sale_date,
            'user_id' => Auth::id(),
            'credit_note_date' => $data['credit_note_date'],
            'total_amount' => $totales['total_amount'],
            'tax_amount' => $totales['tax_amount'],
            'discount_amount' => 0.00,
            'net_amount' => $totales['net_amount'],
            'numero_control' => $invoiceNumber?->numero_control ?? Str::upper(Str::random(8)),
            'codigo_generacion' => $invoiceNumber?->codigo_generacion ?? Str::uuid(),
            'invoice_number' => $invoiceNumber?->number ?? 'NC-' . now()->timestamp,
            'reason' => $data['reason'] ?? null,
            'tipo_moneda' => 'USD',
            'tipo_operacion' => 1,
            'condicion_operacion' => 1,
            // Campos de totales desglosados
            'total_no_gravado' => 0.00,
            'total_exenta' => 0.00,
            'total_gravada' => $totales['subtotal'], // Base sin IVA
            'total_iva' => $totales['tax_amount'],
            'payment_status' => 'unpaid',
            'documento_relacionado' => $sale->codigo_generacion, // Referencia a la factura original
        ]);

        // Crear los detalles de la nota de crédito
        foreach ($data['items'] as $itemData) {
            $saleDetail = $sale->details->where('id', $itemData['sale_detail_id'])->first();
            if ($saleDetail) {
                $unitPrice = $saleDetail->unit_price;
                $quantity = (float) $itemData['quantity'];
                $subtotal = $unitPrice * $quantity;
                $taxAmount = $subtotal * 0.13; // Asumiendo 13% IVA

                CreditNoteDetail::create([
                    'credit_note_id' => $creditNote->id,
                    'sale_detail_id' => $saleDetail->id,
                    'product_type_id' => $saleDetail->product_type_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                ]);

                \Log::info('ITEM DATA', $itemData);
                \Log::info('SALE DETAIL ENCONTRADO', [$saleDetail]);
            }
        }

        // En tu método store, DESPUÉS de crear los detalles:
        $creditNote->load(['creditNoteDetails.saleDetail.productType', 'creditNoteDetails.productType']);

        // Generar DTE
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
     * Calcular totales para la nota de crédito basado en los items seleccionados
     */
    private function calcularTotalesNotaCredito($items, $sale)
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($items as $itemData) {
            $saleDetail = $sale->details->where('id', $itemData['sale_detail_id'])->first();

            if ($saleDetail) {
                $quantity = (float) $itemData['quantity'];
                $unitPrice = (float) $saleDetail->unit_price; // Precio con IVA

                // Obtener base sin IVA
                $baseSinIVA = $unitPrice / 1.13;
                $subtotalItem = $baseSinIVA * $quantity;
                $ivaItem = $subtotalItem * 0.13;

                $subtotal += $subtotalItem;
                $taxAmount += $ivaItem;
            }
        }

        $totalAmount = $subtotal + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),       // base sin IVA
            'net_amount' => round($subtotal, 2),     // mismo valor
            'tax_amount' => round($taxAmount, 2),    // IVA
            'total_amount' => round($totalAmount, 2) // total con IVA
        ];
    }

    public function getSaleDetails(Store $store, Sale $sale)
    {
        $this->validateStoreAccess($store);

        // Verificar que la venta pertenezca a la tienda
        if ($sale->store_id !== $store->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $details = $sale->details()->with('productType')->get();

        return response()->json([
            'details' => $details
        ]);
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

    public function destroy(Store $store, CreditNote $creditNote)
    {
        $this->validateStoreAccess($store);
    
        if ($creditNote->store_id != $store->id) {
            abort(403, 'No puedes eliminar una NC de otra tienda.');
        }
    
        try {
            // Llamar al VoidDTEController para generar la anulación de la NC
            $voidController = app(\App\Http\Controllers\VoidNCController::class);
            $response = $voidController->voidNC($creditNote, $creditNote->sale);
    
            // Verificar que Hacienda haya confirmado la anulación
            if (($response->getData()->estado ?? '') !== 'PROCESADO') {
                return redirect()->back()->withErrors('Hacienda no confirmó la anulación de la NC.');
            }
    
            // Soft delete solo si Hacienda respondió correctamente
            $creditNote->delete();
    
            return redirect()->route('stores.sales.index', $store->id)
                ->with('success', 'Nota de crédito anulada correctamente.');
    
        } catch (\Throwable $th) {
            \Log::error('Error anulando NC: ' . $th->getMessage(), [
                'credit_note_id' => $creditNote->id,
                'trace' => $th->getTraceAsString()
            ]);
    
            return redirect()->back()->withErrors('Error generando la anulación de la NC: ' . $th->getMessage());
        }
    }
    
}
