<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\HaciendaAuthService;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\ProductType;
use App\Models\InvoiceNumber;

class DTEController extends Controller
{
    protected $authService;

    public function __construct(HaciendaAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function generarDTE(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id' => 'required|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'products' => 'required|array|min:1',
            'products.*.product_type_id' => 'required|exists:product_types,id',
            'products.*.quantity' => 'required|numeric|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Generar identificadores
            $randomAlphaNum = strtoupper(Str::random(8));
            $randomNumber15 = str_pad(rand(0, 999999999999999), 15, '0', STR_PAD_LEFT);
            $numeroControl = "DTE-01-{$randomAlphaNum}-{$randomNumber15}";
            $codigoGeneracion = strtoupper(Str::uuid()->toString());

            // Calcular totales
            $totalGravada = 0;
            $totalIVA = 0;

            foreach ($request->products as $item) {
                $product = ProductType::findOrFail($item['product_type_id']);
                $subtotal = $product->price * $item['quantity'];
                $iva = $subtotal * 0.13;
                $totalIVA += $iva;
                $totalGravada += $subtotal;
            }

            $netAmount = $totalGravada + $totalIVA;
            $invoiceNumber = InvoiceNumber::getNextNumber($request->store_id);

            // Crear la venta
            $sale = Sale::create([
                'store_id' => $request->store_id,
                'user_id' => $request->user_id,
                'customers_id' => $request->customer_id,
                'sale_date' => Carbon::now(),
                'total_amount' => $netAmount,
                'tax_amount' => $totalIVA,
                'discount_amount' => 0,
                'net_amount' => $netAmount,
                'tipo_moneda' => 'USD',
                'numero_control' => $numeroControl,
                'codigo_generacion' => $codigoGeneracion,
                'tipo_operacion' => 1,
                'condicion_operacion' => 1,
                'total_no_gravado' => 0,
                'total_exenta' => 0,
                'total_gravada' => $totalGravada,
                'total_iva' => $totalIVA,
                'payment_status' => 'unpaid',
            ]);

            foreach ($request->products as $item) {
                $product = ProductType::findOrFail($item['product_type_id']);
                $subtotal = $product->price * $item['quantity'];

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_type_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();

            // ✅ Construir el JSON DTE
            $dteJson = $this->buildDTEJson($sale);

            // ✅ Obtener token Hacienda
            $token = $this->authService->generateNewToken();
            $payloadFirmador = [
                "nit" => '04142309731011',
                "passwordPri" => '9e.VAGQEVNximSC',
                "dteJson" => $dteJson
            ];
            // ✅ 1️⃣ Firmar documento localmente
            $signResponse = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post('http://localhost:8113/firmardocumento/', $payloadFirmador);

            if ($signResponse->failed()) {
                return response()->json([
                    'error' => 'Error al firmar documento.',
                    'details' => $signResponse->json()
                ], 400);
            }

            $signedData = $signResponse->json();

            if (!isset($signedData['status']) || $signedData['status'] !== 'OK') {
                return response()->json([
                    'error' => 'Error en la firma del DTE.',
                    'response' => $signedData
                ], 400);
            }

            // ✅ 2️⃣ Enviar DTE firmado a Hacienda
            $mhResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
            ->post('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', $signedData["body"]);

            $haciendaResponse = $mhResponse->json();

            return response()->json([
                'message' => 'DTE generado, firmado y enviado correctamente.',
                'sale' => $sale->load('details.productType'),
                'numero_control' => $numeroControl,
                'codigo_generacion' => $codigoGeneracion,
                'invoice_number' => $invoiceNumber,
                'response_firmador' => $signedData,
                'response_hacienda' => $haciendaResponse
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al generar o enviar el DTE.',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    private function buildDTEJson(Sale $sale)
    {
        return [

            "dteJson" => [
                "identificacion" => [
                    "version" => 1,
                    "ambiente" => "00",
                    "tipoDte" => "01",
                    "numeroControl" => $sale->numero_control,
                    "codigoGeneracion" => $sale->codigo_generacion,
                    "tipoModelo" => 1,
                    "tipoOperacion" => $sale->tipo_operacion,
                    "fecEmi" => $sale->sale_date->format('Y-m-d'),
                    "horEmi" => Carbon::now()->format('H:i:s'),
                    "tipoMoneda" => $sale->tipo_moneda,
                ],
                "documentoRelacionado" => null,
                "emisor" => [
                    "nit" => "04142309731011",
                    "nrc" => "2515932",
                    "nombre" => "JOSE ISRAEL RIVERA MORALES",
                    "codActividad" => "56101",
                    "descActividad" => "Comercio de productos varios",
                    "nombreComercial" => "JOSE ISRAEL RIVERA MORALES",
                    "tipoEstablecimiento" => 1,
                    "direccion" => [
                        "departamento" => "06",
                        "municipio" => "20",
                        "complemento" => "Colonia Escalón"
                    ],
                ],
                "resumen" => [
                    "totalGravada" => $sale->total_gravada,
                    "totalIva" => $sale->total_iva,
                    "totalPagar" => $sale->net_amount,
                ],
                "detalle" => $sale->details->map(function ($detail) {
                    return [
                        "cantidad" => $detail->quantity,
                        "descripcion" => $detail->productType->name,
                        "precioUnitario" => $detail->unit_price,
                        "ventaGravada" => $detail->subtotal,
                    ];
                })->toArray(),
            ]
        ];
    }

    public function testDTE()
    {
        $request = new \Illuminate\Http\Request([
            'store_id' => 1, // ID de tu tienda de prueba
            'user_id' => 1, // ID de un usuario de prueba
            'customer_id' => null,
            'products' => [
                [
                    'product_type_id' => 2,
                    'quantity' => 2
                ],
                [
                    'product_type_id' => 2,
                    'quantity' => 1
                ]
            ]
        ]);

        return $this->generarDTE($request);
    }
}
