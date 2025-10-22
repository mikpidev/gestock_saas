<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\CreditNote;


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
        $storeTaxInfo = $sale->store->taxInfo;

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
                "nit" => $storeTaxInfo->nit,
                "nrc" => $storeTaxInfo->nrc,
                "nombre" => $storeTaxInfo->actividad_economica,
                "codActividad" => $storeTaxInfo->codActividad,
                "descActividad" => $storeTaxInfo->razon_social,
                "nombreComercial" => $storeTaxInfo->actividad_economica,
                "tipoEstablecimiento" => "01",
                "direccion" => [
                    "departamento" =>  str_pad((string) ($storeTaxInfo->direccion_departamento ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio" =>  str_pad((string) ($storeTaxInfo->direccion_municipio ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $storeTaxInfo->direccion_fiscal,
                ],
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
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
        $storeTaxInfo = $sale->store->taxInfo;


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
                "precioUni" => round((float) $detail->unit_price / 1.13, 2),
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
                "nit" => $storeTaxInfo->nit,
                "nrc" => $storeTaxInfo->nrc,
                "nombre" => $storeTaxInfo->actividad_economica,
                "codActividad" => $storeTaxInfo->codActividad,
                "descActividad" => $storeTaxInfo->razon_social,
                "nombreComercial" => $storeTaxInfo->actividad_economica,
                "tipoEstablecimiento" => "01",
                "direccion" => [
                    "departamento" =>  str_pad((string) ($storeTaxInfo->direccion_departamento ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio" =>  str_pad((string) ($storeTaxInfo->direccion_municipio ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $storeTaxInfo->direccion_fiscal,
                ],
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
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

    /** Crea el Json para NC (Notas de credito) */

    public function buildDTEJsonNC(CreditNote $creditNote, Sale $sale): array
    {
        $customer = $creditNote->customer ?? $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;

        // USAR creditNoteDetails() con valores POSITIVOS
        $cuerpoDocumento = $creditNote->creditNoteDetails->map(function ($creditNoteDetail, $index) use ($creditNote) {
            $detail = $creditNoteDetail->saleDetail; // Detalle original de la venta
            $subtotalConIVA = (float) $creditNoteDetail->subtotal;
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            // Para nota de crédito, usamos valores POSITIVOS
            $cantidad = (float) $creditNoteDetail->quantity; // POSITIVO
            $precioUni = round((float) $creditNoteDetail->unit_price / 1.13, 2);
            $ventaGravada = $precioUni * $cantidad; // POSITIVO

            return [
                "numItem" => $index + 1,
                "tipoItem" => 1,
                "numeroDocumento" => $creditNote->documento_relacionado,
                "codigo" => $creditNoteDetail->productType->code ?? $detail->productType->code ?? 'NA',
                "codTributo" => null,
                "descripcion" => "NOTA CRÉDITO - " . ($creditNoteDetail->productType->name ?? $detail->productType->name),
                "cantidad" => $cantidad, // POSITIVO
                "uniMedida" => 59,
                "precioUni" => $precioUni, // POSITIVO
                "montoDescu" => 0.00,
                "ventaNoSuj" => 0.00,
                "ventaExenta" => 0.00,
                "ventaGravada" => round($ventaGravada, 2), // POSITIVO
                "tributos" => ["20"],
            ];
        })->toArray();
        $now = now()->setTimezone('America/El_Salvador');

        // Función interna para redondear con precisión a 2 decimales
        $round2 = fn($value) => round((float)$value, 2, PHP_ROUND_HALF_UP);
        // Cálculos redondeados para el resumen
        $totalGravada = $round2($creditNote->subtotal);
        $iva = $round2($creditNote->total_iva);
        $montoTotal = $round2($creditNote->total_amount);
        $subTotalVentas = $round2($totalGravada);
        $totalDescu = 0.00;

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => "00",
                "tipoDte" => "05", // ← Esto indica que es Nota de Crédito
                "numeroControl" => $creditNote->numero_control,
                "codigoGeneracion" => $creditNote->codigo_generacion,
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "fecEmi" => $creditNote->credit_note_date,
                "horEmi" => now()->format('H:i:s'),
                "tipoMoneda" => "USD",
                "tipoContingencia" => null,
                "motivoContin" => null
            ],
            "documentoRelacionado" => [
                [
                    "tipoDocumento" => str_pad((string) ($sale->tipoDte->codigo), 2, "0", STR_PAD_LEFT),
                    "tipoGeneracion" => 2,
                    "numeroDocumento" => $sale->codigo_generacion,
                    "fechaEmision" => $sale->sale_date->format('Y-m-d')
                ]
            ],
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nrc" => $storeTaxInfo->nrc,
                "nombre" => $storeTaxInfo->actividad_economica,
                "codActividad" => $storeTaxInfo->codActividad,
                "descActividad" => $storeTaxInfo->razon_social,
                "nombreComercial" => $storeTaxInfo->actividad_economica,
                "tipoEstablecimiento" => "01",
                "direccion" => [
                    "departamento" =>  str_pad((string) ($storeTaxInfo->direccion_departamento ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio" =>  str_pad((string) ($storeTaxInfo->direccion_municipio ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $storeTaxInfo->direccion_fiscal,
                ],
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
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
            "ventaTercero" => null,
            "cuerpoDocumento" => $cuerpoDocumento,
            "resumen" => [
                "totalNoSuj" => 0.00,
                "totalExenta" => 0.00,
                "totalGravada" => $creditNote->total_gravada,
                "subTotalVentas" => $creditNote->total_gravada,
                "descuNoSuj" => 0.00,
                "descuExenta" => 0.00,
                "descuGravada" => 0.00,
                "totalDescu" => $totalDescu,
                "tributos" => [
                    [
                        "codigo" => "20",
                        "descripcion" => "IVA",
                        "valor" => $iva
                    ]
                ],
                "subTotal" => $round2($creditNote->total_gravada - $totalDescu),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => 0.00,
                "montoTotalOperacion" => $montoTotal,
                "totalLetras" => $this->totalEnLetras($montoTotal),
                "condicionOperacion" => 1, // contado
            ],

            "extension" => null,
            "apendice" => null
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
