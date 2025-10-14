<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public function generarDTE(Sale $sale)
    {
        try {
            // Construir JSON DTE
            $dteJson = $this->buildDTEJson($sale);

            Log::info('DTE antes de firmar', $dteJson);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            // Firmar documento
            $payloadFirmador = [
                "nit" => '04142309731011',
                "passwordPri" => '9e.VAGQEVNximSC',
                "dteJson" => $dteJson
            ];

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
            Log::info('Respuesta del firmador', $signedData);

            if (!isset($signedData['status']) || $signedData['status'] !== 'OK') {
                return response()->json([
                    'error' => 'Error en la firma del DTE.',
                    'response' => $signedData
                ], 400);
            }
            $mhResponse = Http::withHeaders([
                'Authorization' => $token, 
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', [
                    'ambiente' => '00',
                    'idEnvio' => 1,
                    'version' => 1,
                    'tipoDte' => '01',
                    'codigoGeneracion' => $sale->codigo_generacion,
                    'documento' => $signedData["body"]
                ]);

            Log::info('Hacienda Response', [
                'status' => $mhResponse->status(),
                'body' => $mhResponse->body()
            ]);

            $haciendaResponse = $mhResponse->json();

            // Guardar info del DTE en la venta
            $sale->update([
                'dte_codigo' => $sale->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return $haciendaResponse;
        } catch (\Throwable $th) {
            Log::error('Error generando DTE: ' . $th->getMessage());
            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    private function buildDTEJson(Sale $sale)
    {
        $customer = $sale->customer;
        $isConsumidorFinal = !$customer;

        // Receptor
        $receptor = $isConsumidorFinal ? [
            "tipoDocumento" => null,
            "numDocumento" => null,
            "nrc" => null,
            "nombre" => "Consumidor Final",
            "codActividad" => null,
            "descActividad" => null,
            "direccion" => [
                "departamento" => "01",
                "municipio" => "01",
                "complemento" => "N/A"
            ],
            "telefono" => "00000000",
            "correo" => "consumidor@final.com"
        ] : [
            "tipoDocumento" => $customer->tipo_documento ?? "36",
            "numDocumento" => $customer->num_documento ?? "00000000000000",
            "nrc" => $customer->nrc ?? null,
            "nombre" => $customer->nombre,
            "codActividad" => $customer->cod_actividad ?? null,
            "descActividad" => $customer->desc_actividad ?? null,
            "direccion" => [
                "departamento" => str_pad((string) ($customer->direccion_departamento ?? "01"), 2, "0", STR_PAD_LEFT),
                "municipio"   => str_pad((string) ($customer->direccion_municipio ?? "01"), 2, "0", STR_PAD_LEFT),
                "complemento" => $customer->direccion_complemento
            ],
            "telefono" => $customer->telefono ?? "00000000",
            "correo" => $customer->correo ?? "cliente@prueba.com"
        ];

        // Cuerpo documento (productos con IVA incluido)
        $cuerpoDocumento = $sale->details->map(function ($detail, $index) {
            $subtotalConIVA = (float) $detail->subtotal;
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            return [
                "numItem" => $index + 1,
                "tipoItem" => 1,
                "numeroDocumento" => null,
                "codigo" => null,
                "codTributo" => null,
                "descripcion" => $detail->productType->name,
                "cantidad" => (float) $detail->quantity,
                "uniMedida" => 59,
                "precioUni" => round((float) $detail->unit_price, 2),
                "montoDescu" => 0,
                "ventaNoSuj" => 0,
                "ventaExenta" => 0,
                "ventaGravada" => round($subtotalConIVA, 2),
                "tributos" => null,
                "psv" => 0,
                "noGravado" => 0,
                "ivaItem" => round($ivaItem, 2)
            ];
        })->toArray();

        // Total IVA para el resumen
        $totalIva = $sale->details->sum(function ($detail) {
            $subtotalConIVA = (float) $detail->subtotal;
            $baseSinIVA = $subtotalConIVA / 1.13;
            return $baseSinIVA * 0.13;
        });

        return [
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
                "tipoContingencia" => null,
                "motivoContin" => null
            ],
            "documentoRelacionado" => null,
            "emisor" => [
                "nit" => "04142309731011",
                "nrc" => "2515932",
                "nombre" => "JOSE ISRAEL RIVERA MORALES",
                "codActividad" => "56101",
                "descActividad" => "Comercio de productos varios",
                "nombreComercial" => "JOSE ISRAEL RIVERA MORALES",
                "tipoEstablecimiento" => "01",
                "direccion" => [
                    "departamento" => "06",
                    "municipio" => "20",
                    "complemento" => "Colonia Escalón"
                ],
                "telefono" => "22223333",
                "correo" => "contacto@ejemplo.com",
                "codEstableMH" => null,
                "codEstable" => null,
                "codPuntoVentaMH" => null,
                "codPuntoVenta" => null
            ],
            "receptor" => $receptor,
            "otrosDocumentos" => null,
            "ventaTercero" => null,
            "cuerpoDocumento" => $cuerpoDocumento,
            "resumen" => [
                "totalNoSuj" => 0.00,
                "totalExenta" => 0.00,
                "totalGravada" => round($sale->net_amount, 2),
                "subTotalVentas" => round($sale->net_amount, 2),
                "descuNoSuj" => 0.00,
                "descuExenta" => 0.00,
                "descuGravada" => 0.00,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => 0.00,
                "tributos" => null,
                "subTotal" => round($sale->net_amount, 2),
                "ivaRete1" => 0.00,
                "reteRenta" => 0.00,
                "montoTotalOperacion" => round($sale->net_amount, 2),
                "totalNoGravado" => 0.00,
                "totalPagar" => round($sale->net_amount, 2),
                "totalLetras" => $sale->total_letras ?? "SEIS DÓLARES 78/100",
                "totalIva" => round($totalIva, 2),
                "saldoFavor" => 0.00,
                "condicionOperacion" => 1,
                "pagos" => null,
                "numPagoElectronico" => null
            ],
            "extension" => null,
            "apendice" => [
                [
                    "campo" => "observacionExtra",
                    "etiqueta" => "Nota adicional",
                    "valor" => "Factura generada en modo de prueba"
                ]
            ]
        ];
    }
}
