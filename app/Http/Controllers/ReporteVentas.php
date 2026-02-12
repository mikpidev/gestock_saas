<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Http\Controllers\DTEController;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DteResponse;
use App\Services\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Services\OCIService;


class ReporteVentas extends Controller
{

    protected $dteService;

    public function __construct(DocumentService $dteService)
    {
        $this->dteService = $dteService;
    }

    //Reporte verntas

    public function index()
    {

        ini_set('memory_limit', '512M');

        $sales = Sale::with((['store', 'user']))->get();
        $saleDetails = SaleDetail::with(['productType'])->get();
        $productType = ProductType::all();

        //Generar reporte de ventas PDF
        $pdf = \PDF::loadView('reportes.ventas', compact('store', 'sales', 'saleDetails', 'productType'));
        return $pdf->download('reporte_ventas.pdf');
    }

    public function dteReporte(OciService $oci)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $basePath = storage_path("app/dte_reportes/");
        $tempPath = storage_path("app/dte_reportes/temp/");

        if (!file_exists($basePath)) mkdir($basePath, 0777, true);
        if (!file_exists($tempPath)) mkdir($tempPath, 0777, true);

        $zipPrincipalName = 'dtes_export_' . date('Y-m-d') . '.zip';
        $zipPrincipalPath = $basePath . $zipPrincipalName;

        $zipPrincipal = new \ZipArchive;
        $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $query = Sale::with([
            'store.taxInfo',
            'customer',
            'details.productType',
            'creditNotes.creditNoteDetails.productType',
            'debitNotes.debitNoteDetails.productType',
            'tipoDte'
        ])
            ->where('dte_status', 'PROCESADO')
            ->orWhere('dte_status', 'PENDIENTE')
            ->whereDate('sale_date', '>=', now()->subDays(21));

        $query->chunk(110, function ($sales) use ($basePath, $tempPath, $zipPrincipal, $oci) {

            foreach ($sales as $sale) {

                $sale->load([
                    'store.taxInfo',
                    'customer',
                    'details.productType',
                    'creditNotes.creditNoteDetails.productType',
                    'debitNotes.debitNoteDetails.productType',
                    'tipoDte'
                ]);

                // llamar SelloDTE
                $dteResponse = DteResponse::where('sale_id', $sale->id)->first();

                $tipoDteDescripcion = [
                    '01' => 'Factura',
                    '03' => 'Crédito Fiscal',
                    '14' => 'Factura Sujeto Excluido',
                ];

                $tipo = strtolower($sale->tipoDte->codigo ?? '');

                switch ($tipo) {
                    case '01':
                        $json = $this->dteService->buildDTEJsonFE($sale);
                        //agregar selloDTE al JSON para el PDF
                        if ($dteResponse) {
                            $json['sello_recibido'] = $dteResponse->sello_recibido;
                        }

                        break;

                    case '03':
                        $json = $this->dteService->buildDTEJsonCF($sale);
                        //agregar selloDTE al JSON para el PDF
                        if ($dteResponse) {
                            $json['sello_recibido'] = $dteResponse->sello_recibido;
                        }
                        break;

                    case '14':
                        $json = $this->dteService->buildDTEJsonSE($sale);
                        //agregar selloDTE al JSON para el PDF
                        if ($dteResponse) {
                            $json['sello_recibido'] = $dteResponse->sello_recibido;
                        }
                        break;

                    default:
                        \Log::warning("Tipo DTE desconocido", [
                            "sale_id" => $sale->id,
                            "tipo" => $tipo
                        ]);
                        continue 2;
                }

                $codigoGen = $sale->codigo_generacion;

                // Guardar JSON
                $jsonFilename = "{$tempPath}/dte_{$codigoGen}.json";
                file_put_contents($jsonFilename, json_encode($json, JSON_PRETTY_PRINT));

                // Generar QR
                $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                    . "?ambiente=01"
                    . "&codGen={$json['identificacion']['codigoGeneracion']}"
                    . "&fechaEmi=" . date('Y-m-d', strtotime($json['identificacion']['fecEmi']));

                $renderer = new ImageRenderer(
                    new RendererStyle(150),
                    new SvgImageBackEnd()
                );

                $writer = new Writer($renderer);
                $qrImage = base64_encode($writer->writeString($urlQR));

                // Generar PDF
                $pdf = Pdf::loadView('reportes.ventas', [
                    'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
                    'dte'      => $json,
                    'emisor'   => $json['emisor'],
                    //validar si es SE - pass sujetoExcluido en lugar de receptor
                    'receptor' => $tipo === '14' ? $json['sujetoExcluido'] : $json['receptor'],
                    'resumen'  => $json['resumen'],
                    'qrImage'  => $qrImage,
                    'dteResponse' => $dteResponse
                ]);

                $pdfFilename = "{$tempPath}/dte_{$codigoGen}.pdf";
                $pdf->save($pdfFilename);

                // Crear ZIP individual
                $zipIndividualName = "DTE_{$codigoGen}.zip";
                $zipIndividualPath = "{$tempPath}/{$zipIndividualName}";

                $zipIndividual = new \ZipArchive;
                $zipIndividual->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                $zipIndividual->addFile($jsonFilename, "dte_{$codigoGen}.json");
                $zipIndividual->addFile($pdfFilename, "dte_{$codigoGen}.pdf");

                $zipIndividual->close();

                // Agregar al ZIP principal
                $zipPrincipal->addFile($zipIndividualPath, $zipIndividualName);



                \Log::info("DTE generado y enviado a OCI", [
                    "sale_id" => $sale->id,
                    "zip" => $zipIndividualPath
                ]);
            }
        });

        $zipPrincipal->close();
        // SUBIR A OCI
        $oci->uploadReportsToOCI(
            $zipPrincipalName,  // nombre en OCI
            $zipPrincipalPath,   // ruta local
            "application/zip"
        );

        // DESCARGAR ZIP PRINCIPAL LOCAL
        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }


    public function dteReporteNC(OciService $oci)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(120);

        $basePath = storage_path("app/dtesNC_reportes/");
        $tempPath = storage_path("app/dtesNC_reportes/temp/");

        if (!file_exists($basePath)) mkdir($basePath, 0777, true);
        if (!file_exists($tempPath)) mkdir($tempPath, 0777, true);

        $zipPrincipalName = 'dtesNC_export_' . date('Y-m-d') . '.zip';
        $zipPrincipalPath = $basePath . $zipPrincipalName;

        $zipPrincipal = new \ZipArchive;
        $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $result = $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            \Log::error("ERROR al abrir ZIP principal", [
                'path' => $zipPrincipalPath,
                'error' => $result
            ]);

            return back()->with('error', 'No se pudo crear el archivo ZIP principal.');
        }

        $query = CreditNote::with([
            'sale',
            'store.taxInfo',
            'customer',
            'creditNoteDetails.productType',
            'tipoDte'
        ])
            ->where('dte_status', 'PROCESADO')
            ->whereDate('sale_date', '>=', now()->subDays(16));

        $query->chunk(110, function ($notasCredito) use ($basePath, $tempPath, $zipPrincipal, $oci) {

            foreach ($notasCredito as $nc) {

                $nc->load([
                    'store.taxInfo',
                    'sale',
                    'customer',
                    'creditNoteDetails.productType',
                    'tipoDte'
                ]);

                $tipo = '05';
                $tipoDteDescripcion = ['05' => 'Nota de Crédito'];
                $sale = $nc->sale ?? (isset($nc->sale_id) ? \App\Models\Sale::find($nc->sale_id) : null);

                switch ($tipo) {
                    case '05':
                        $json = $this->dteService->buildDTEJsonNC($nc, $sale);
                        break;

                    default:
                        \Log::warning("Tipo DTE desconocido", [
                            "sale_id" => $nc->id,
                            "tipo" => $tipo
                        ]);
                        continue 2;
                }

                // Código generación — usar el del JSON (más confiable)
                $codigoGen = $json['identificacion']['codigoGeneracion'];

                // Guardar JSON
                $jsonFilename = "{$tempPath}/dte_{$codigoGen}.json";
                file_put_contents($jsonFilename, json_encode($json, JSON_PRETTY_PRINT));

                // Generar QR
                $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                    . "?ambiente=01"
                    . "&codGen={$json['identificacion']['codigoGeneracion']}"
                    . "&fechaEmi=" . date(
                        'Y-m-d',
                        strtotime($json['identificacion']['fecEmi'])
                    );

                $renderer = new ImageRenderer(
                    new RendererStyle(150),
                    new SvgImageBackEnd()
                );
                $writer = new Writer($renderer);
                $qrImage = base64_encode($writer->writeString($urlQR));

                // PDF
                $pdf = Pdf::loadView('Reportes.notascredito', [
                    'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
                    'dte'      => $json,
                    'emisor'   => $json['emisor'],
                    'receptor' => $json['receptor'],
                    'resumen'  => $json['resumen'],
                    'qrImage'  => $qrImage
                ]);

                $pdfFilename = "{$tempPath}/dte_{$codigoGen}.pdf";
                $pdf->save($pdfFilename);

                // ZIP individual
                $zipIndividualName = "DTE_{$codigoGen}.zip";
                $zipIndividualPath = "{$tempPath}/{$zipIndividualName}";

                $zipIndividual = new \ZipArchive;
                $zipIndividual->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                $zipIndividual->addFile($jsonFilename, "dte_{$codigoGen}.json");
                $zipIndividual->addFile($pdfFilename, "dte_{$codigoGen}.pdf");

                $zipIndividual->close();

                $zipPrincipal->addFile($zipIndividualPath, $zipIndividualName);

                \Log::info("DTE generado y enviado a OCI", [
                    "credit_note_id" => $nc->id,
                    "zip" => $zipIndividualPath
                ]);
            }
        });

        $zipPrincipal->close();

        // SUBIR ZIP FINAL A OCI
        $oci->uploadReportsToOCI(
            $zipPrincipalName,
            $zipPrincipalPath,
            "application/zip"
        );

        // DESCARGAR ZIP
        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }


    public function dteReporteND(OciService $oci)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(120);

        $basePath = storage_path("app/dtesND_reportes/");
        $tempPath = storage_path("app/dtesND_reportes/temp/");

        if (!file_exists($basePath)) mkdir($basePath, 0777, true);
        if (!file_exists($tempPath)) mkdir($tempPath, 0777, true);

        $zipPrincipalName = 'dtesND_export_' . date('Y-m-d') . '.zip';
        $zipPrincipalPath = $basePath . $zipPrincipalName;

        $zipPrincipal = new \ZipArchive;
        $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        $result = $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            \Log::error("ERROR al abrir ZIP principal", [
                'path' => $zipPrincipalPath,
                'error' => $result
            ]);

            return back()->with('error', 'No se pudo crear el archivo ZIP principal.');
        }

        $query = DebitNote::with([
            'sale',
            'store.taxInfo',
            'customer',
            'debitNoteDetails.productType',
            'tipoDte'
        ])
            ->where('dte_status', 'PROCESADO')
            ->whereDate('sale_date', '>=', now()->subDays(16));

        $query->chunk(110, function ($notasDebito) use ($basePath, $tempPath, $zipPrincipal, $oci) {

            foreach ($notasDebito as $nd) {

                $nd->load([
                    'store.taxInfo',
                    'sale',
                    'customer',
                    'debitNoteDetails.productType',
                    'tipoDte'
                ]);

                $tipo = '06';
                $tipoDteDescripcion = ['06' => 'Nota de Debito'];
                $sale = $nd->sale ?? (isset($nd->sale_id) ? \App\Models\Sale::find($nd->sale_id) : null);

                switch ($tipo) {
                    case '06':
                        $json = $this->dteService->buildDTEJsonND($nd, $sale);
                        break;

                    default:
                        \Log::warning("Tipo DTE desconocido", [
                            "sale_id" => $nd->id,
                            "tipo" => $tipo
                        ]);
                        continue 2;
                }

                // Código generación — usar el del JSON (más confiable)
                $codigoGen = $json['identificacion']['codigoGeneracion'];

                // Guardar JSON
                $jsonFilename = "{$tempPath}/dte_{$codigoGen}.json";
                file_put_contents($jsonFilename, json_encode($json, JSON_PRETTY_PRINT));

                // Generar QR
                $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                    . "?ambiente=00"
                    . "&codGen={$json['identificacion']['codigoGeneracion']}"
                    . "&fechaEmi=" . date(
                        'Y-m-d',
                        strtotime($json['identificacion']['fecEmi'])
                    );

                $renderer = new ImageRenderer(
                    new RendererStyle(150),
                    new SvgImageBackEnd()
                );
                $writer = new Writer($renderer);
                $qrImage = base64_encode($writer->writeString($urlQR));

                // PDF
                $pdf = Pdf::loadView('Reportes.notasdebito', [
                    'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
                    'dte'      => $json,
                    'emisor'   => $json['emisor'],
                    'receptor' => $json['receptor'],
                    'resumen'  => $json['resumen'],
                    'qrImage'  => $qrImage
                ]);

                $pdfFilename = "{$tempPath}/dte_{$codigoGen}.pdf";
                $pdf->save($pdfFilename);

                // ZIP individual
                $zipIndividualName = "DTE_{$codigoGen}.zip";
                $zipIndividualPath = "{$tempPath}/{$zipIndividualName}";

                $zipIndividual = new \ZipArchive;
                $zipIndividual->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                $zipIndividual->addFile($jsonFilename, "dte_{$codigoGen}.json");
                $zipIndividual->addFile($pdfFilename, "dte_{$codigoGen}.pdf");

                $zipIndividual->close();

                $zipPrincipal->addFile($zipIndividualPath, $zipIndividualName);

                \Log::info("DTE generado y enviado a OCI", [
                    "debit_note_id" => $nd->id,
                    "zip" => $zipIndividualPath
                ]);
            }
        });

        $zipPrincipal->close();

        // SUBIR ZIP FINAL A OCI
        $oci->uploadReportsToOCI(
            $zipPrincipalName,
            $zipPrincipalPath,
            "application/zip"
        );

        // DESCARGAR ZIP
        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }
}
