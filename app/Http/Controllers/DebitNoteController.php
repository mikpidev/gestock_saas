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
use App\Services\ConsultaService;
use App\Services\HaciendaAuthService;
use Carbon\Carbon;
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
    public function index(Request $request, Store $store)
    {

        $authService = app(HaciendaAuthService::class);


        // Primero obtenemos la fecha del request (si no viene, usa hoy)
        $consultaService = new ConsultaService();
        $customers = Customer::where('store_id', $store->id)->get();
        $dteStatuses = Sale::where('store_id', $store->id)->select('dte_status')->distinct()->pluck('dte_status');


        // Primero obtenemos la fecha del request (si no viene, usa hoy)
        $dateFrom = $request->from
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->startOfDay();

        $dateTo = $request->to
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();

        //obtener filtro clientes, codigo generacion y estado DTE para mostrar en el index

        $customers_id = $request->customer_id;
        $codigo_generacion_filter = $request->codigo_generacion;
        $dte_status = $request->dte_status;


        // Filtramos ventas de ESTA tienda y de ESA fecha
        $debitNotes = DebitNote::with('customer')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($customers_id, fn($q) => $q->where('customers_id', $customers_id))
            ->when($codigo_generacion_filter, fn($q) => $q->where('codigo_generacion', $codigo_generacion_filter))
            ->when($dte_status, fn($q) => $q->where('dte_status', $dte_status))
            ->orderByDesc('created_at')
            ->get();


        // Solo ventas no procesadas
        $pendingND = $debitNotes->filter(function ($debitNotes) {
            return !empty($creditNotes->codigo_generacion)
                && $creditNotes->dte_status !== 'PROCESADO';
        });

        if ($pendingND->isNotEmpty()) {

            try {
                $token = $authService->getToken($store);
            } catch (\Exception $e) {
                $token = null;
            }

            if ($token) {
                foreach ($pendingND as $pendingND) {
                    $consultaService->consultarND($pendingND, $token);
                }
            }
        }

        return view('debitnotes.index', compact('store', 'debitNotes', 'dateFrom', 'dateTo', 'customers', 'customers_id', 'codigo_generacion_filter', 'dte_status', 'dteStatuses'));
    }


    public function refreshDTE(Store $store, DebitNote $debitNote, ConsultaService $consultaService)
    {
        $token = app(HaciendaAuthService::class)->getToken();
        $consultaService->consultarND($debitNote, $token);


        return redirect()->back()->with('success', 'Estado DTE actualizado.');
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
            ->orderBy('created_at', 'desc') // Ordena por fecha descendente
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

        $establecimiento = $store->establecimiento;


        $puntoVenta = $store->punto_venta;

        // Obtener el correlativo
        $invoiceNumber = InvoiceNumber::getNextNumber($store->id, $tipoDTE, $establecimiento, $puntoVenta);



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

        //Consultar si DTE fue aceptado antes de redirigir
        try {
            // Obtener token válido
            $token = app(HaciendaAuthService::class)->getToken($store);

            // Consultar DTE inmediatamente después de enviar
            $consultaService = new ConsultaService();
            $response = $consultaService->consultarND($debitNote, $token);

            // Actualizar estado de la venta con el estado real
            $debitNote->dte_status = $response['estado'] ?? 'PENDIENTE';
            $debitNote->save();

            // Guardar response en sesión para mostrar en index
            session()->flash('dte_response', $response);
        } catch (\Throwable $e) {
            \Log::error("Error consultando DTE al crear venta: {$e->getMessage()}", [
                'debit_note_id' => $debitNote->id,
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('dte_response', ['error' => $e->getMessage()]);
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

        if ($debitNote->store_id != $store->id) {
            \Log::info('Verificación de tienda en destroy ND', [
                'store_id_param' => $store->id,
                'store_id_nd' => $debitNote->store_id,
                'user_id' => Auth::id(),
            ]);
            abort(403, 'No puedes eliminar una ND de otra tienda.');
        }

        // Si ya pasaron más de 24 horas, no se puede anular
        if ($debitNote->created_at->lessThan(now()->subHours(24))) {
            return redirect()->back()->withErrors('No se puede anular una venta con más de 24 horas de antigüedad.');
        }

        try {
            // Llamar al VoidDTEController para generar la anulación de la ND
            $voidController = app(\App\Http\Controllers\VoidNDController::class);
            $response = $voidController->voidND($debitNote, $debitNote->sale);

            // Verificar que Hacienda haya confirmado la anulación
            if (($response->getData()->estado ?? '') !== 'PROCESADO') {
                return redirect()->back()->withErrors('Hacienda no confirmó la anulación de la ND.');
            }

            // Soft delete solo si Hacienda respondió correctamente
            $debitNote->delete();

            return redirect()->route('stores.sales.index', $store->id)
                ->with('success', 'Nota de debito anulada correctamente.');
        } catch (\Throwable $th) {
            \Log::error('Error anulando ND: ' . $th->getMessage(), [
                'debit_note_id' => $debitNote->id,
                'trace' => $th->getTraceAsString()
            ]);

            return redirect()->back()->withErrors('Error generando la anulación de la ND: ' . $th->getMessage());
        }
    }
}
