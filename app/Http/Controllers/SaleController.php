<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Customer;
use App\Models\DteResponse;
use App\Models\ProductType;
use App\Models\InvoiceNumber;
use App\Models\Store;
use App\Models\TipoDte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ConsultaService;
use App\Services\HaciendaAuthService;
use Carbon\Carbon;
use App\Services\DocumentService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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
        } elseif ($user->hasRole('user')) {
            if ($store->company_id != $user->company_id) {
                abort(403, 'No tienes permiso para acceder a esta tienda.');
            }
        } else {
            abort(403, 'No tienes permiso para acceder a esta tienda.');
        }
    }

    protected $dteService;

    public function __construct(DocumentService $dteService)
    {
        $this->dteService = $dteService;
    }


    public function index(Request $request, Store $store)
    {

        $authService = app(HaciendaAuthService::class);

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


        //obtener datos para mostrar en el filtro
        $sales = Sale::with('customer')
            ->where('store_id', $store->id)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->when($customers_id, fn($q) => $q->where('customers_id', $customers_id))
            ->when($codigo_generacion_filter, fn($q) => $q->where('codigo_generacion', $codigo_generacion_filter))
            ->when($dte_status, fn($q) => $q->where('dte_status', $dte_status))
            ->orderByDesc('sale_date')
            ->get();


        // Solo ventas no procesadas
        $pendingSales = $sales->filter(function ($sale) {
            return !empty($sale->codigo_generacion)
                && $sale->dte_status !== 'PROCESADO';
        });
        if ($pendingSales->isNotEmpty()) {

            try {
                $token = $authService->getToken($store);
            } catch (\Exception $e) {
                $token = null;
            }

            if ($token) {
                foreach ($pendingSales as $sale) {
                    $consultaService->consultarSale($sale, $token);
                }
            }
        }



        return view('sales.index', compact('store', 'sales', 'dateFrom', 'dateTo', 'customers', 'customers_id', 'codigo_generacion_filter', 'dte_status', 'dteStatuses'));
    }

    public function refreshDTE(Store $store, Sale $sale, ConsultaService $consultaService)
    {

        try {

            // Validar tiempo permitido
            if ($sale->created_at->diffInHours(now()) > 48) {
                return back()->with(
                    'error',
                    'El tiempo permitido para reenviar este DTE expiró.'
                );
            }

            // No reenviar si ya está procesado
            if ($sale->dte_status === 'PROCESADO') {
                return back()->with(
                    'error',
                    'Este DTE ya fue procesado.'
                );
            }

            app(\App\Http\Controllers\DTEController::class)
                ->generarDTE($sale);


            $token = app(HaciendaAuthService::class)
                ->getToken($store);

            $response = $consultaService
                ->consultarSale($sale, $token);


            $sale->dte_status = $response['estado'] ?? 'PENDIENTE';
            $sale->save();


            if ($sale->dte_status = 'PROCESADO') {
                try {
                    app(\App\Http\Controllers\OCIController::class)->emailSend($store, $sale);
                } catch (\Throwable $e) {

                    \Log::error("Error Enviando correo DTE: {$e->getMessage()}", [

                        'sale_id' => $sale->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }


            return back()->with(
                'success',
                'DTE reenviado correctamente.'
            );
        } catch (\Throwable $e) {

            \Log::error("Error reenviando DTE", [
                'sale_id' => $sale->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }


    public function create(Store $store)
    {
        $this->validateStoreAccess($store);
        $customers = Customer::where('store_id', $store->id)->get();
        $products = ProductType::where('store_id', $store->id)->get();

        //Agrupar productos por categoria alfabeticamente para mostrar en el select del formulario de creación de venta
        $categories = $products->groupBy('category')->sortKeys();
        $tipoDocumentos = TipoDte::all();


        return view('sales.create', compact('store', 'tipoDocumentos', 'customers', 'products', 'categories'));
    }

    public function store(Request $request, Store $store)
    {
        $this->validateStoreAccess($store);

        // Validar request
        $data = $request->validate([
            'customers_id' => 'nullable|exists:customers,id',
            'sale_date' => 'required|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:product_types,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'tipo_documento_id' => 'required|exists:tipo_documento,id',
            'payment_method' => 'required|in:Efectivo,Tarjeta,Transferencia',
        ]);

        // logs data for debugging
        \Log::info('Creating sale with data: ', $data);

        // Calcular totales
        $discountPercent = $data['discount_amount'] ?? 0; // valor como 0.10 = 10%
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

        // Aplicar porcentaje
        $discountAmount = $totalAmount * $discountPercent;

        // Neto después del descuento
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
            'dte_status'   => 'PENDIENTE', // Valor inicial por defecto
            'discount_amount' => round($discountAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'net_amount' => round($netAmount, 2),
            'store_id' => $store->id,
            'user_id' => auth()->id(),
            'tipo_moneda' => 'USD',
            'tipo_operacion' => 1,
            'condicion_operacion' => 1,
            'payment_method' => $data['payment_method'],
            'total_exenta' => $total_exenta,
            'total_gravada' => $netAmount,
            'total_iva' => $total_iva,
            'numero_control' => $invoiceNumber->numero_control,
            'codigo_generacion' => $invoiceNumber->codigo_generacion,
            'invoice_number' => $invoiceNumber->number,
            'tipo_documento_id' => $data['tipo_documento_id'], // tipo DTE
            'environment' => $store->environment ?? 'Production'
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
        try {
            // Obtener token válido
            $token = app(HaciendaAuthService::class)->getToken($store);

            // Consultar DTE inmediatamente después de enviar
            $consultaService = new ConsultaService();
            $response = $consultaService->consultarSale($sale, $token);

            // Actualizar estado de la venta con el estado real
            $sale->dte_status = $response['estado'] ?? 'PENDIENTE';
            $sale->save();


            //enviar correo en automatico

            if ($sale->dte_status = 'PROCESADO') {
                try {
                    app(\App\Http\Controllers\OCIController::class)->emailSend($store, $sale);
                } catch (\Throwable $e) {

                    \Log::error("Error Enviando correo DTE: {$e->getMessage()}", [

                        'sale_id' => $sale->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Guardar response en sesión para mostrar en index
            session()->flash('dte_response', $response);
        } catch (\Throwable $e) {
            \Log::error("Error consultando DTE al crear venta: {$e->getMessage()}", [
                'sale_id' => $sale->id,
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('dte_response', ['error' => $e->getMessage()]);
        }
        if ($request->ajax()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Venta creada y DTE enviado correctamente',
                'ticket_url'  => route('ticket.print', [$store->id, $sale->id]),
                'dte_status'  => $sale->dte_status
            ]);
        }

        return response()->json([
            'success' => true,
            'ticket_url' => route('ticket.print', [$store->id, $sale->id]),
            'pre_order_url' => route('ticket.preorder', [$store->id, $sale->id]),
            'sale_id' => $sale->id
        ]);

        return redirect()->route('stores.sales.index', $store->id)
            ->with('success', 'Venta creada correctamente. El DTE se generará en breve.');
    }


    public function show(Store $store, string $codigo)
    {


        $sale = Sale::with([
            'store.taxInfo',
            'customer',
            'details.productType',
            'creditNotes.creditNoteDetails.productType',
            'debitNotes.debitNoteDetails.productType',
            'tipoDte'
        ])->where('codigo_generacion', $codigo)->firstOrFail();
        $dteResponse = DteResponse::where('sale_id', $sale->id)->first();

        $store = $sale->store->store_name;


        // Mapeo de descripciones
        $tipoDteDescripcion = [
            '01' => 'Factura',
            '03' => 'Crédito Fiscal',
            '14' => 'Factura Sujeto Excluido',
            // agregar los necesarios
        ];

        $tipo = $sale->tipoDte->codigo ?? null;

        // Construcción del JSON
        switch ($tipo) {
            case '01':
                $json = $this->dteService->buildDTEJsonFE($sale);
                break;
            case '03':
                $json = $this->dteService->buildDTEJsonCF($sale);
                break;
            case '14':
                $json = $this->dteService->buildDTEJsonSE($sale);
                break;
            default:
                abort(404, "Tipo DTE desconocido.");
        }

        // QR
        $urlQR =
            "https://admin.factura.gob.sv/consultaPublica"
            . "?ambiente=00"
            . "&codGen={$json['identificacion']['codigoGeneracion']}"
            . "&fechaEmi=" . date('Y-m-d', strtotime($json['identificacion']['fecEmi']));

        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrImage = base64_encode($writer->writeString($urlQR));

        return view('sales.show', [
            'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
            'dte'      => $json,
            'store' => $store,
            'emisor'   => $json['emisor'],
            //validar si es SE - pass sujetoExcluido en lugar de receptor
            'receptor' => $tipo === '14' ? $json['sujetoExcluido'] : $json['receptor'],
            'resumen'  => $json['resumen'],
            'qrImage'  => $qrImage,
            'dteResponse' => $dteResponse
        ]);
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

        if ($sale->store_id != $store->id) {
            abort(403, 'No puedes eliminar una venta de otra tienda.');
        }

        // Si ya pasaron más de 24 horas, no se puede anular
        if ($sale->created_at->lessThan(now()->subHours(24))) {
            return redirect()->back()->withErrors('No se puede anular una venta con más de 24 horas de antigüedad.');
        }

        try {
            // Llamar al VoidDTEController para generar la anulación
            $voidController = app(\App\Http\Controllers\VoidDTEController::class);
            $response = $voidController->voidDTE($sale);

            if (($response->getData()->estado ?? '') !== 'PROCESADO') {
                return redirect()->back()->withErrors('Hacienda no confirmó la anulación.');
            }

            // Soft delete solo si Hacienda respondió correctamente
            $sale->delete();

            return redirect()->route('stores.sales.index', $store->id)
                ->with('success', 'Venta anulada correctamente.');
        } catch (\Throwable $th) {
            \Log::error('Error anulando venta: ' . $th->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $th->getTraceAsString()
            ]);

            return redirect()->back()->withErrors('Error generando la anulación: ' . $th->getMessage());
        }
    }
}
