<?php

namespace App\Services;

use App\Models\Contingencia;
use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\InvoiceNumber;
use App\Models\VoidDTE;
use App\Models\VoidNC;
use App\Models\VoidND;


class DocumentService
{

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


        // Datos del cliente y tienda
        $customer = $sale->customer;
        $isConsumidorFinal = !$customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $environment = $sale->environment == 'Production' ? '01' : '00';


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
            "tipoDocumento" => $customer->tipoDocumento,
            "numDocumento" => $customer->numDocumento,
            "nrc" => $customer->nrc ?? null,
            "nombre" => $customer->nombre,
            "codActividad" => $customer->codActividad ?? null,
            "descActividad" => $customer->descActividad ?? null,
            "direccion" => [
                "departamento" => str_pad((string) ($customer->departamento->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                "municipio"   => str_pad((string) ($customer->municipio->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                "complemento" => $customer->direccion_complemento
            ],
            "telefono" => $customer->telefono ?? "00000000",
            "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
        ];

        // Cuerpo documento
        $totalVenta = $sale->details->sum('subtotal');

        $cuerpoDocumento = $sale->details->map(function ($detail, $index) use ($sale, $totalVenta) {

            $proporcion = $detail->subtotal / $totalVenta;
            $descuentoItem = round($sale->discount_amount * $proporcion, 2);

            $subtotalConIVA = max($detail->subtotal - $descuentoItem, 0);
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
                "montoDescu" => $descuentoItem,
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
                "ambiente" => $environment,
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
                    "departamento" =>  str_pad((string) ($storeTaxInfo->departamento->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio" =>  str_pad((string) ($storeTaxInfo->municipio->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
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
                "totalDescu" => round($sale->discount_amount, 2),
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
            "apendice" => null
        ];
    }


    /**
     * Construye el JSON para Anular Factura Electrónica (FE)
     * 
     */
    public function buildDTEJsonVoidFE(Sale $sale, VoidDTE $void): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $sale->load('dteResponses');
        $selloRecibidoOriginal = $sale->dteResponses()
            ->where('estado', 'PROCESADO') // solo DTE exitosos
            ->orderBy('created_at', 'asc') // tomar el primero, que es la venta original
            ->first()?->sello_recibido;

        $environment = $sale->environment == 'Production' ? '01' : '00';
        $voidNumber = InvoiceNumber::getCGVoidNumber($sale->store->id);

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => $environment,
                //"tipoDte" => "03",
                // "numeroControl" => $sale->numero_control,
                "codigoGeneracion" => $voidNumber,
                //"tipoModelo" => 1,
                // "tipoOperacion" => 1,
                "fecEmi" => $sale->sale_date->format('Y-m-d'),
                "horEmi" => now()->format('H:i:s'),
                "fusion" => null

                // "tipoMoneda" => $sale->tipo_moneda,
            ],
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nombre" => $storeTaxInfo->actividad_economica,
                //"nomEstablecimiento" => $storeTaxInfo->actividad_economica,
                // "tipoEstablecimiento" => "01",
                "codEstableMH" => "S001",
                "codEstable" => "S001",
                "codPuntoVentaMH" => "P001",
                "codPuntoVenta" => "P001",
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
            ],
            "documento" => [
                "tipoDte" => "01",
                "codigoGeneracion" => $sale->codigo_generacion,
                "selloRecibido" => $selloRecibidoOriginal,
                "numeroControl" => $sale->numero_control,
                "fecEmi" => $sale->sale_date->format('Y-m-d'),
                "codigoGeneracionR" => null,
                "tipoDocumento" => $customer->tipoDocumento,
                "numDocumento" => $customer->numDocumento,
                "nombre" => $customer->nombre,
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "motivo" => [
                "tipoAnulacion" => 2,
                "motivoAnulacion" => $void->desc,
                "nombreResponsable" => $storeTaxInfo->actividad_economica,
                "tipDocResponsable" => "36",
                "numDocResponsable" => $storeTaxInfo->nit,
                "nombreSolicita" => $customer->nombre,
                "tipDocSolicita" => $customer->tipoDocumento ?? "36",
                "numDocSolicita" => $customer->numDocumento ?? "00000000000000"
            ]
        ];
    }

    /**
     * Crea el JSON para Anular Credito Fiscal(CF)
     */

    public function buildDTEJsonCF(Sale $sale, array $dteRelacionado = []): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;

        $discount_amount = $sale->discount_amount ?? 0.00;
        $totalGravada = 0.00;
        $totalIva = 0.00;
        $environment = $sale->environment == 'Production' ? '01' : '00';



        // ==============================
        // CUERPO DOCUMENTO
        // ==============================
        $cuerpoDocumento = $sale->details->map(function ($detail, $index) use (&$totalGravada, &$totalIva, &$discount_amount) {

            $cantidad = (float) $detail->quantity;

            // Precio viene CON IVA
            $precioConIva = round((float) $detail->unit_price, 4);

            // Precio SIN IVA (MH exige esto) 
            $precioSinIva = round($precioConIva / 1.13, 8);

            $descuento = round((float) ($discount_amount ?? 0), 2);

            // Bruto SIN IVA
            $brutoSinIva = round($cantidad * $precioSinIva, 2);

            // Venta gravada neta
            $ventaGravada = round($brutoSinIva - $discount_amount, 2);

            // IVA del ítem
            $ivaItem = round($ventaGravada * 0.13, 2);

            $totalGravada += $ventaGravada;
            $totalIva += $ivaItem;

            return [
                "numItem" => $index + 1,
                "tipoItem" => 1,
                "numeroDocumento" => null,
                "codigo" => $detail->productType->code ?? 'NA',
                "codTributo" => null,
                "descripcion" => $detail->productType->name,
                "cantidad" => $cantidad,
                "uniMedida" => 59,
                "precioUni" => $precioSinIva,
                "montoDescu" => $descuento,
                "ventaNoSuj" => 0.00,
                "ventaExenta" => 0.00,
                "ventaGravada" => round($ventaGravada, 2),
                "tributos" => ["20"],
                "psv" => 0.00,
                "noGravado" => 0.00
            ];
        })->toArray();

        $montoTotalOperacion = $totalGravada + $totalIva;

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => $environment,
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
                    "departamento" => str_pad((string) ($customer->departamento->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio"   => str_pad((string) ($customer->municipio->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $customer->direccion_complemento
                ],
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "otrosDocumentos" => null,
            "ventaTercero" => null,
            "cuerpoDocumento" => $cuerpoDocumento,
            "resumen" => [
                "totalNoSuj" => 0.00,
                "totalExenta" => 0.00,
                "totalGravada" => round($totalGravada, 2),
                "subTotalVentas" => round($sale->total_gravada - $sale->total_iva, 2),
                "descuNoSuj" => 0.00,
                "descuExenta" => 0.00,
                "descuGravada" => 0.00,
                "porcentajeDescuento" => 0.00,
                "totalDescu" => round($discount_amount, 2),

                "tributos" => [
                    [
                        "codigo" => "20",
                        "descripcion" => "IVA",
                        "valor" => round($sale->total_iva, 2)
                    ]
                ],

                "subTotal" => round($sale->total_gravada - $sale->total_iva, 2),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => 0.00,

                "montoTotalOperacion" => round($sale->total_gravada, 2),
                "totalNoGravado" => 0.00,
                "totalPagar" => round($sale->total_gravada, 2),

                "totalLetras" => $this->totalEnLetras($sale->total_gravada),
                "saldoFavor" => 0.00,
                "condicionOperacion" => 1,

                "pagos" => [
                    [
                        "codigo" => "01",
                        "montoPago" => round($sale->total_gravada, 2),
                        "referencia" => null,
                        "plazo" => null,
                        "periodo" => null
                    ]
                ],
                "numPagoElectronico" => null
            ],
            "extension" => null,
            "apendice" => null
        ];
    }

    /**
     * Construye el JSON para Anular Factura Electrónica (FE)
     * 
     */

    public function buildDTEJsonVoidCF(Sale $sale, VoidDTE $void): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $environment = $sale->environment == 'Production' ? '01' : '00';
        $sale->load('dteResponses');
        $selloRecibidoOriginal = $sale->dteResponses()
            ->where('estado', 'PROCESADO') // solo DTE exitosos
            ->orderBy('created_at', 'asc') // tomar el primero, que es la venta original
            ->first()?->sello_recibido;

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => $environment,
                "codigoGeneracion" => $void->codigo_generacion,
                "fecEmi" => $sale->sale_date->format('Y-m-d'),
                "horEmi" => now()->format('H:i:s'),
                "fusion" => null
            ],
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nombre" => $storeTaxInfo->actividad_economica,
                "codEstableMH" => "S001",
                "codEstable" => null,
                "codPuntoVentaMH" => "P001",
                "codPuntoVenta" => null,
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
            ],
            "documento" => [
                "tipoDte" => "03",
                "codigoGeneracion" => $sale->codigo_generacion,
                "selloRecibido" => $selloRecibidoOriginal,
                "numeroControl" => $sale->numero_control,
                "fecEmi" => $sale->sale_date->format('Y-m-d'),
                //"montoIva" => (float) $sale->total_iva,
                "codigoGeneracionR" => null,
                "tipoDocumento" => $customer->tipoDocumento ?? "36",
                "numDocumento" => $customer->numDocumento ?? "00000000000000",
                "nombre" => $customer->nombre,
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "motivo" => [
                "tipoAnulacion" => 2,
                "motivoAnulacion" => $void->desc,
                "nombreResponsable" => $storeTaxInfo->actividad_economica,
                "tipDocResponsable" => "36",
                "numDocResponsable" => $storeTaxInfo->nit,
                "nombreSolicita" => $customer->nombre,
                "tipDocSolicita" => $customer->tipoDocumento ?? "36",
                "numDocSolicita" => $customer->numDocumento ?? "00000000000000"
            ]
        ];
    }

    /**
     * Crea el JSON para Sujeto Excluido (SE)
     */

    public function buildDTEJsonSE(Sale $sale, array $dteRelacionado = []): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $environment = $sale->environment == 'Production' ? '01' : '00';


        // Cuerpo documento
        $cuerpoDocumento = $sale->details->map(function ($detail, $index) use ($sale) {
            $discount_amount = $sale->discount_amount ?? 0.00;
            $subtotalConIVA = (float) $detail->subtotal - $discount_amount;
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            return [
                "numItem" => $index + 1,
                "tipoItem" => 1,
                "codigo" => $detail->productType->code ?? 'NA',
                "descripcion" => $detail->productType->name,
                "cantidad" => (float) $detail->quantity,
                "uniMedida" => 59,
                "precioUni" => round((float) $detail->unit_price / 1.13, 2),
                "montoDescu" => round($discount_amount, 2),
                "compra" => round((float)$baseSinIVA, 2),
            ];
        })->toArray();

        $totalGravada = $sale->details->sum(fn($d) => $d->subtotal / 1.13);
        $totalIva = $sale->details->sum(fn($d) => ($d->subtotal / 1.13) * 0.13);

        return [
            "identificacion" => [
                "version" => 1,
                "ambiente" => $environment,
                "tipoDte" => "14",
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
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nrc" => $storeTaxInfo->nrc,
                "nombre" => $storeTaxInfo->actividad_economica,
                "codActividad" => $storeTaxInfo->codActividad,
                "descActividad" => $storeTaxInfo->razon_social,
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
            "sujetoExcluido" => [
                "tipoDocumento" => $customer->tipoDocumento ?? "36",
                "numDocumento" => $customer->numDocumento ?? "00000000000000",
                "nombre" => $customer->nombre,
                "codActividad" => $customer->codActividad ?? null,
                "descActividad" => $customer->descActividad ?? null,
                "direccion" => [
                    "departamento" => str_pad((string) ($customer->departamento->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio"   => str_pad((string) ($customer->municipio->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $customer->direccion_complemento
                ],
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "cuerpoDocumento" => $cuerpoDocumento,
            "resumen" => [
                "totalCompra" => round($totalGravada, 2),
                "descu" => 0.00,
                "totalDescu" => 0.00,
                "subTotal" => round($totalGravada, 2),
                "ivaRete1" => 0.00,
                "reteRenta" => 0.00,
                "totalPagar" => round($totalGravada, 2),
                "totalLetras" => $this->totalEnLetras($totalGravada),
                "condicionOperacion" => 1,
                "pagos" => [
                    [
                        "codigo" => "01",
                        "montoPago" =>  round($totalGravada, 2),
                        "referencia" => null,
                        "plazo" => null,
                        "periodo" => null
                    ]
                ],
                "observaciones" => null
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
     * Construye el JSON para Anular Sujeto Excluido (SE)
     * 
     */

    public function buildDTEJsonVoidSE(Sale $sale, VoidDTE $void): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $environment = $sale->environment == 'Production' ? '01' : '00';
        $sale->load('dteResponses');
        $selloRecibidoOriginal = $sale->dteResponses()
            ->where('estado', 'PROCESADO') // solo DTE exitosos
            ->orderBy('created_at', 'asc') // tomar el primero, que es la venta original
            ->first()?->sello_recibido;

        return [
            "identificacion" => [
                "version" => 2,
                "ambiente" => $environment,
                "codigoGeneracion" => $void->codigo_generacion,
                "fecAnula" => $void->void_date->format('Y-m-d'),
                "horAnula" => now()->format('H:i:s'),
            ],
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nombre" => $storeTaxInfo->actividad_economica,
                "nomEstablecimiento" => $storeTaxInfo->actividad_economica,
                "tipoEstablecimiento" => "01",
                "codEstableMH" => null,
                "codEstable" => null,
                "codPuntoVentaMH" => null,
                "codPuntoVenta" => null,
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
            ],
            "documento" => [
                "tipoDte" => "14",
                "codigoGeneracion" => $sale->codigo_generacion,
                "selloRecibido" => $selloRecibidoOriginal,
                "numeroControl" => $sale->numero_control,
                "fecEmi" => $sale->sale_date->format('Y-m-d'),
                "montoIva" => 0,
                "codigoGeneracionR" => null,
                "tipoDocumento" => $customer->tipoDocumento ?? "36",
                "numDocumento" => $customer->numDocumento ?? "00000000000000",
                "nombre" => $customer->nombre,
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "motivo" => [
                "tipoAnulacion" => 2,
                "motivoAnulacion" => $void->desc,
                "nombreResponsable" => $storeTaxInfo->actividad_economica,
                "tipDocResponsable" => "36",
                "numDocResponsable" => $storeTaxInfo->nit,
                "nombreSolicita" => $customer->nombre,
                "tipDocSolicita" => $customer->tipoDocumento ?? "36",
                "numDocSolicita" => $customer->numDocumento ?? "00000000000000"
            ]
        ];
    }

    /** Crea el Json para NC (Notas de credito) */

    public function buildDTEJsonNC(CreditNote $creditNote, Sale $sale): array
    {
        $customer = $creditNote->customer ?? $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;

        //Llamamos el ambiente
        $environment = $sale->environment == 'Production' ? '01' : '00';

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
                "ambiente" => $environment,
                "tipoDte" => "05", // ← Esto indica que es Nota de Crédito
                "numeroControl" => $creditNote->numero_control,
                "codigoGeneracion" => $creditNote->codigo_generacion,
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "fecEmi" => $creditNote->credit_note_date->format('Y-m-d'),
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
                    "departamento" => str_pad((string) ($customer->departamento->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio"   => str_pad((string) ($customer->municipio->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $customer->direccion_complemento
                ],
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
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

    /** Crea el JSON para Anular NC (Notas de Crédito) */
    public function buildDTEJsonVoidNC(CreditNote $creditNote, Sale $sale, VoidNC $void): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        //Llamamos ambiente desde venta
        $environment = $sale->environment == 'Production' ? '01' : '00';

        // Tomar solo DTE procesados de la venta original
        $selloRecibidoOriginal = $creditNote->dteResponses()
            ->where('estado', 'PROCESADO') // solo DTE exitosos
            ->orderBy('created_at', 'asc') // tomar el primero, que es la venta original
            ->first()?->sello_recibido;

        return [
            "identificacion" => [
                "version" => 2,
                "ambiente" => $environment,
                "codigoGeneracion" => $void->codigo_generacion,
                "fecAnula" => Carbon::parse($void->void_date)->format('Y-m-d'),
                "horAnula" => now()->format('H:i:s'),
            ],
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nombre" => $storeTaxInfo->actividad_economica,
                "nomEstablecimiento" => $storeTaxInfo->actividad_economica,
                "tipoEstablecimiento" => "01",
                "codEstableMH" => null,
                "codEstable" => null,
                "codPuntoVentaMH" => null,
                "codPuntoVenta" => null,
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
            ],
            "documento" => [
                "tipoDte" => "05",
                "codigoGeneracion" => $creditNote->codigo_generacion,
                "selloRecibido" => $selloRecibidoOriginal,
                "numeroControl" => $creditNote->numero_control,
                "fecEmi" => $creditNote->credit_note_date->format('Y-m-d'),
                "montoIva" => 0,
                "codigoGeneracionR" => null,
                "tipoDocumento" => $customer->tipoDocumento ?? "36",
                "numDocumento" => $customer->numDocumento ?? "00000000000000",
                "nombre" => $customer->nombre,
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "motivo" => [
                "tipoAnulacion" => 2,
                "motivoAnulacion" => $void->desc,
                "nombreResponsable" => $storeTaxInfo->actividad_economica,
                "tipDocResponsable" => "36",
                "numDocResponsable" => $storeTaxInfo->nit,
                "nombreSolicita" => $customer->nombre,
                "tipDocSolicita" => $customer->tipoDocumento ?? "36",
                "numDocSolicita" => $customer->numDocumento ?? "00000000000000"
            ]
        ];
    }



    /** Crea el Json para ND (Notas de debito) */

    public function buildDTEJsonND(DebitNote $debitNote, Sale $sale): array
    {
        $customer = $debitNote->customer ?? $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $environment = $sale->environment == 'Production' ? '01' : '00';
        // USAR debitNoteDetails() con valores POSITIVOS
        $cuerpoDocumento = $debitNote->debitNoteDetails->map(function ($debitNoteDetail, $index) use ($debitNote) {
            $detail = $debitNoteDetail->saleDetail; // Detalle original de la venta
            $subtotalConIVA = (float) $debitNoteDetail->subtotal;
            $baseSinIVA = $subtotalConIVA / 1.13;
            $ivaItem = $baseSinIVA * 0.13;

            // Para nota de crédito, usamos valores POSITIVOS
            $cantidad = (float) $debitNoteDetail->quantity; // POSITIVO
            $precioUni = round((float) $debitNoteDetail->unit_price / 1.13, 2);
            $ventaGravada = $precioUni * $cantidad; // POSITIVO

            return [
                "numItem" => $index + 1,
                "tipoItem" => 1,
                "numeroDocumento" => $debitNote->documento_relacionado,
                "codigo" => $debitNoteDetail->productType->code ?? $detail->productType->code ?? 'NA',
                "codTributo" => null,
                "descripcion" => "NOTA DEBITO - " . ($debitNoteDetail->productType->name ?? $detail->productType->name),
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
        $totalGravada = $round2($debitNote->subtotal);
        $iva = $round2($debitNote->total_iva);
        $montoTotal = $round2($debitNote->total_amount);
        $subTotalVentas = $round2($totalGravada);
        $totalDescu = 0.00;

        return [
            "identificacion" => [
                "version" => 3,
                "ambiente" => $environment,
                "tipoDte" => "06", // ← Esto indica que es Nota de Crédito
                "numeroControl" => $debitNote->numero_control,
                "codigoGeneracion" => $debitNote->codigo_generacion,
                "tipoModelo" => 1,
                "tipoOperacion" => 1,
                "fecEmi" => $debitNote->debit_note_date->format('Y-m-d'),
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
                    "departamento" => str_pad((string) ($customer->departamento->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "municipio"   => str_pad((string) ($customer->municipio->codigo ?? "01"), 2, "0", STR_PAD_LEFT),
                    "complemento" => $customer->direccion_complemento
                ],
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "ventaTercero" => null,
            "cuerpoDocumento" => $cuerpoDocumento,
            "resumen" => [
                "totalNoSuj" => 0.00,
                "totalExenta" => 0.00,
                "totalGravada" => $debitNote->total_gravada,
                "subTotalVentas" => $debitNote->total_gravada,
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
                "subTotal" => $round2($debitNote->total_gravada - $totalDescu),
                "ivaPerci1" => 0.00,
                "ivaRete1" => 0.00,
                "reteRenta" => 0.00,
                "montoTotalOperacion" => $montoTotal,
                "totalLetras" => $this->totalEnLetras($montoTotal),
                "condicionOperacion" => 1, // contado
                "numPagoElectronico" => null
            ],

            "extension" => null,
            "apendice" => null
        ];
    }

    /**
     * Construye el JSON para Anular ND (Notas de debito)
     * 
     */

    public function buildDTEJsonVoidND(DebitNote $debitNote, Sale $sale,  VoidND $void): array
    {
        $customer = $sale->customer;
        $storeTaxInfo = $sale->store->taxInfo;
        $environment = $sale->environment == 'Production' ? '01' : '00';

        // Tomar solo DTE procesados de la venta original
        $selloRecibidoOriginal = $debitNote->dteResponses()
            ->where('estado', 'PROCESADO') // solo DTE exitosos
            ->orderBy('created_at', 'asc') // tomar el primero, que es la venta original
            ->first()?->sello_recibido;

        return [
            "identificacion" => [
                "version" => 2,
                "ambiente" => $environment,
                "codigoGeneracion" => $void->codigo_generacion,
                "fecAnula" => Carbon::parse($void->void_date)->format('Y-m-d'),
                "horAnula" => now()->format('H:i:s'),
            ],
            "emisor" => [
                "nit" => $storeTaxInfo->nit,
                "nombre" => $storeTaxInfo->actividad_economica,
                "nomEstablecimiento" => $storeTaxInfo->actividad_economica,
                "tipoEstablecimiento" => "01",
                "codEstableMH" => null,
                "codEstable" => null,
                "codPuntoVentaMH" => null,
                "codPuntoVenta" => null,
                "telefono" => $storeTaxInfo->telefono,
                "correo" => $storeTaxInfo->email,
            ],
            "documento" => [
                "tipoDte" => "06",
                "codigoGeneracion" => $debitNote->codigo_generacion,
                "selloRecibido" => $selloRecibidoOriginal,
                "numeroControl" => $debitNote->numero_control,
                "fecEmi" => $debitNote->debit_note_date->format('Y-m-d'),
                "montoIva" => 0,
                "codigoGeneracionR" => null,
                "tipoDocumento" => $customer->tipoDocumento ?? "36",
                "numDocumento" => $customer->numDocumento ?? "00000000000000",
                "nombre" => $customer->nombre,
                "telefono" => $customer->telefono ?? "00000000",
                "correo" => $customer->correo ?? "cliente@consumidorfinal.com"
            ],
            "motivo" => [
                "tipoAnulacion" => 2,
                "motivoAnulacion" => $void->desc,
                "nombreResponsable" => $storeTaxInfo->actividad_economica,
                "tipDocResponsable" => "36",
                "numDocResponsable" => $storeTaxInfo->nit,
                "nombreSolicita" => $customer->nombre,
                "tipDocSolicita" => $customer->tipoDocumento ?? "36",
                "numDocSolicita" => $customer->numDocumento ?? "00000000000000"
            ]
        ];
    }

    public function buildDTEJsonContingencia(Contingencia $contingencia): array
    {
        $contingencia->loadMissing([
            'store.taxInfo',
            'sales.tipoDte',
            'debitNotes',
            'creditNotes',
            'tipoContingencia'
        ]);

        $storeTaxInfo = $contingencia->store->taxInfo;
        $environment = $contingencia->store->environment == 'Production' ? '01' : '00';

        $detalleDTE = [];
        $noItem = 1;

        foreach ($contingencia->sales as $sale) {
            $detalleDTE[] = [
                'noItem' => $noItem++,
                'codigoGeneracion' => $sale->codigo_generacion,
                'tipoDoc' => $sale->tipoDte->codigo
            ];
        }

        foreach ($contingencia->debitNotes as $nd) {
            $detalleDTE[] = [
                'noItem' => $noItem++,
                'codigoGeneracion' => $nd->codigo_generacion,
                'tipoDoc' => '05'
            ];
        }

        foreach ($contingencia->creditNotes as $nc) {
            $detalleDTE[] = [
                'noItem' => $noItem++,
                'codigoGeneracion' => $nc->codigo_generacion,
                'tipoDoc' => '06'
            ];
        }

        if (empty($detalleDTE)) {
            throw new \Exception('No existen DTE asociados a la contingencia');
        }


        return [
            'identificacion' => [
                'version' => 3,
                'ambiente' => $environment,
                'codigoGeneracion' => $contingencia->codigo_generacion,
                'fTransmision' => $contingencia->fecha_hora_fin->format('Y-m-d'),
                'hTransmision' => $contingencia->fecha_hora_fin->format('H:i:s'),
            ],

            'emisor' => [
                'nit' => $storeTaxInfo->nit,
                'nombre' => $storeTaxInfo->razon_social,
                'nombreResponsable' => $storeTaxInfo->razon_social,
                'tipoDocResponsable' => '36',
                'numeroDocResponsable' => $storeTaxInfo->nit,
                'tipoEstablecimiento' => '01',
                'codEstableMH' => null,
                'codPuntoVenta' => null,
                'telefono' => $storeTaxInfo->telefono,
                'correo' => $storeTaxInfo->email
            ],

            'detalleDTE' => $detalleDTE,

            'motivo' => [
                'fInicio' => $contingencia->fecha_hora_inicio->format('Y-m-d'),
                'fFin' => $contingencia->fecha_hora_fin->format('Y-m-d'),
                'hInicio' => $contingencia->fecha_hora_inicio->format('H:i:s'),
                'hFin' => $contingencia->fecha_hora_fin->format('H:i:s'),
                'tipoContingencia' => $contingencia->tipoContingencia->codigo,
                'motivoContingencia' => $contingencia->motivo_contingencia
            ]
        ];
    }



    /**
     * Firma el documento usando el firmador local
     */
    public function signDocument(array $dteJson, string $nit, string $password_pri, string $cert_firma_digital): array
    {
        // Obtener el puerto del certificado desde DTEController
        $port = [
            "port" => $cert_firma_digital ?? '1234'

        ];
        $payload = [
            "nit" => $nit ?? '00000000000000',
            "passwordPri" => $password_pri ?? 'default_password',
            "dteJson" => $dteJson,
        ];

        //debug log del payload antes de enviarlo al firmador
        Log::debug('Puerto Certificado', $port);

        //Logs del payload antes de enviarlo al firmador
        Log::debug('Payload para firmar documento', $payload);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json'
        ])->post("http://localhost:{$port['port']}/firmardocumento/", $payload);

        // logs request antes de firmar

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
