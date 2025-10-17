<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DocumentService
{
    protected string $nit = '04142309731011';
    protected string $password = '9e.VAGQEVNximSC';

    /** Generar total en letras */

    public function totalEnLetras(float $monto): string
    {
        $formatter = new \NumberFormatter("es", \NumberFormatter::SPELLOUT);

        $entero = floor($monto);
        $centavos = round(($monto - $entero) * 100);
    
        // Convertir parte entera a palabras
        $letrasEntero = $formatter->format($entero);
    
        // Convertir centavos a palabras si es mayor que 0
        if ($centavos > 0) {
            $letrasCentavos = $formatter->format($centavos);
            $resultado = ucfirst($letrasEntero) . " con " . $letrasCentavos . " centavos";
        } else {
            $resultado = ucfirst($letrasEntero) . " con cero centavos";
        }
    
        return $resultado;
    }


    /**
     * Construye el JSON para Factura Electrónica (FE)
     */
    public function buildDTEJsonFE(Sale $sale): array
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
            "tipoDocumento" => $customer->tipoDocumento ?? "36",
            "numDocumento" => $customer->numDocumento ?? "00000000000000",
            "nrc" => $customer->nrc ?? null,
            "nombre" => $customer->nombre,
            "codActividad" => $customer->codActividad ?? null,
            "descActividad" => $customer->descActividad ?? null,
            "direccion" => [
                "departamento" => str_pad((string) ($customer->direccion_departamento ?? "01"), 2, "0", STR_PAD_LEFT),
                "municipio"   => str_pad((string) ($customer->direccion_municipio ?? "01"), 2, "0", STR_PAD_LEFT),
                "complemento" => $customer->direccion_complemento
            ],
            "telefono" => $customer->telefono ?? "00000000",
            "correo" => $customer->correo ?? "cliente@prueba.com"
        ];

        // Cuerpo documento
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

        // Total IVA
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
                "totalLetras" => $this->totalEnLetras($sale->net_amount),
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
    /**
     * Crea el JSON para Credito Fiscal(CF)
     */

    public function buildDTEJsonCF(Sale $sale, array $dteRelacionado = []): array
    {
        $customer = $sale->customer;

        // Cuerpo documento
        $cuerpoDocumento = $sale->details->map(function ($detail, $index) {
            $subtotalConIVA = (float) $detail->subtotal;
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            return [
                "numItem" => $index + 1,
                "tipoItem" => 1,
                "numeroDocumento" => null,
                "codigo" => $detail->productType->code ?? 'NA',
                "codTributo" => null,
                "descripcion" => $detail->productType->name,
                "cantidad" => (float) $detail->quantity,
                "uniMedida" => 59,
                "precioUni" => round((float) $detail->unit_price/1.13, 2),
                "montoDescu" => 0.00,
                "ventaNoSuj" => 0.00,
                "ventaExenta" => 0.00,
                "ventaGravada" => round($baseSinIVA, 2),
                "tributos" => ["20"], // solo código IVA
                "psv" => 0.00,
                "noGravado" => 0.00
            ];
        })->toArray();

        $totalGravada = $sale->details->sum(fn($d) => $d->subtotal / 1.13);
        $totalIva = $sale->details->sum(fn($d) => ($d->subtotal / 1.13) * 0.13);

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => "00",
                "tipoDte" => "03",
                "numeroControl" => $sale->numero_control,
                "codigoGeneracion" => $sale->codigo_generacion,
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "fecEmi" => $sale->sale_date->format('Y-m-d'),
                "horEmi" => now()->format('H:i:s'),
                "tipoMoneda" => $sale->tipo_moneda,
                "tipoContingencia" => null,
                "motivoContin" => null
            ],
            "documentoRelacionado" => $dteRelacionado ?: null,
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
            "receptor" => [
                "nit" => $customer->numDocumento ?? "00000000000000",
                "nrc" => $customer->nrc ?? null,
                "nombre" => $customer->nombre,
                "nombreComercial" => $customer->nombreComercial,
                "codActividad" => $customer->codActividad ?? null,
                "descActividad" => $customer->descActividad ?? null,
                "direccion" => [
                    "departamento" => str_pad((string) ($customer->direccion_departamento ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio"   => str_pad((string) ($customer->direccion_municipio ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $customer->direccion_complemento
                ],
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@prueba.com"
            ],
            "otrosDocumentos" => null,
            "ventaTercero" => null,
            "cuerpoDocumento" => $cuerpoDocumento,
            "resumen" => [
                "totalNoSuj" => 0.00,
                "totalExenta" => 0.00,
                "totalGravada" => round($totalGravada, 2),
                "subTotalVentas" => round($totalGravada, 2),
                "descuNoSuj" => 0.00,
                "descuExenta" => 0.00,
                "descuGravada" => 0.00,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => 0.00,
                "tributos" => [
                    [
                        "codigo" => "20",
                        "descripcion" => "IVA",
                        "valor" => round($totalIva, 2)
                    ]
                ],
                "subTotal" => round($totalGravada, 2),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => 0.00,
                "montoTotalOperacion" => round($sale->net_amount, 2),
                "totalNoGravado" => 0.00,
                "totalPagar" => round($sale->net_amount, 2),
                "totalLetras" => $this->totalEnLetras($sale->net_amount),
                "saldoFavor" => 0.00,
                "condicionOperacion" => 1,
                "pagos" => [
                    [
                        "codigo" => "01",
                        "montoPago" => round($sale->net_amount, 2),
                        "referencia" => null,
                        "plazo" => null,
                        "periodo" => null
                    ]
                ],
                "numPagoElectronico" => null
            ],
            "extension" => [
                "nombEntrega" => "María López",
                "docuEntrega" => "06141234-5",
                "nombRecibe" => "Carlos Pérez",
                "docuRecibe" => "06147895-6",
                "observaciones" => "Gracias por su compra",
                "placaVehiculo" => null
            ],
            "apendice" => [
                [
                    "campo" => "Caja",
                    "etiqueta" => "Número de Caja",
                    "valor" => "01"
                ],
                [
                    "campo" => "Vendedor",
                    "etiqueta" => "Nombre del Vendedor",
                    "valor" => "Ana Torres"
                ]
            ]
        ];
    }



    /**
     * Firma el documento usando el firmador local
     */
    public function signDocument(array $dteJson): array
    {
        $payload = [
            "nit" => $this->nit,
            "passwordPri" => $this->password,
            "dteJson" => $dteJson
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post('http://localhost:8113/firmardocumento/', $payload);

        if ($response->failed()) {
            Log::error('Error firmando documento', $response->json());
            throw new \Exception('Error al firmar documento');
        }

        $signedData = $response->json();

        if (!isset($signedData['status']) || $signedData['status'] !== 'OK') {
            Log::error('Error en la firma del documento', $signedData);
            throw new \Exception('Firma del documento fallida');
        }

        return $signedData;
    }
}
