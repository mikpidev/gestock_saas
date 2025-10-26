<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use App\Models\DebitNoteDetail;
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


class DebitNoteController extends Controller
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
        //solicitar lista de notas de debito
        $debitNotes = $store->debitNotes()->with(['customer', 'sale', 'user'])->orderByDesc('sale_date')->get();

        return view('debitnotes.index', compact('store', 'debitNotes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //validar acceso a la tienda
        $this->validateStoreAccess($store);
        //mostrar ventas

        $sales = $store->sales()
            ->with(['customer', 'details.productType'])
            ->orderByDesc('sale_date')
            ->get();


        return view('debitnotes.create', compact('store', 'sales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        // Validar acceso a la tienda
        $this->validateStoreAccess($store);

        // Validar campos incluyendo los items a a debitar
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'debit_note_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.sale_detail_id' => 'required|exists:sale_details,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        // Obtener la venta asociada con sus detalles
        $sale = Sale::with(['customer', 'details', 'details.productType'])->findOrFail($data['sale_id']);
        $customer = $sale->customer;

        $tipoDTE = "06"; // Código para Nota de Crédito Electrónica

        // Calcular totales basados en los items a a debitar
        $totales = $this->calcularTotalesNotaDebito($data['items'], $sale);

        // Obtener el correlativo
        $invoiceNumber = InvoiceNumber::getNextNumber($store->id, $tipoDTE);



        // Crear la nota de crédito
        $debitNote = DebitNote::create([
            'store_id' => $store->id,
            'sale_id' => $sale->id,
            'customers_id' => $customer?->id,
            'sale_date' => $sale->sale_date,
            'user_id' => Auth::id(),
            'debit_note_date' => $data['debit_note_date'],
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

                DebitNoteDetail::create([
                    'debit_note_id' => $debitNote->id,
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
        $debitNote->load(['debitNoteDetails.saleDetail.productType', 'debitNoteDetails.productType']);

        // Generar DTE
        try {
            app(\App\Http\Controllers\DTEController::class)->generarDTEDebitNote($debitNote, $sale);
        } catch (\Throwable $e) {
            \Log::error('Error generando DTE: ' . $e->getMessage());
        }


        return redirect()
            ->route('stores.debitnotes.index', $store->id)
            ->with('success', 'Nota de debito creada correctamente.');
    }

    /**
     * Calcular totales para la nota de crédito basado en los items seleccionados
     */
    private function calcularTotalesNotaDebito($items, $sale)
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
    public function show(DebitNote $debitNote, Store $store)
    {
        //Validar acceso a la tienda

        $this->validateStoreAccess($store);
        if ($debitNote->store_id != $store->id) abort(403, 'No puedes ver una ND de otra tienda.');

        $debitNote->load('customer', 'sale', 'user');
        return view('debit_notes.show', compact('debitNote', 'store'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DebitNote $debitNote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DebitNote $debitNote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store, DebitNote $debitNote)
    {
        $this->validateStoreAccess($store);
        if ($debitNote->store_id != $store->id) abort(403, 'No puedes eliminar una ND de otra tienda.');

        $debitNote->delete();
        return redirect()->route('stores.debitnotes.index', $store->id)
            ->with('success', 'Nota de crédito eliminada correctamente.');
    }
}
