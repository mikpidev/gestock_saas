<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\Store;


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
    public function dteReporte(Request $request, Store $store, OCIService $oci)
    { 
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $basePath = storage_path("app/dte_reportes");
        $tempPath = storage_path("app/dte_reportes/temp");

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
            \Log::info("Directorio base creado", ['path' => $basePath]);
        }

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
            \Log::info("Directorio temporal creado", ['path' => $tempPath]);
        }

        foreach (glob($tempPath . '/*') as $file) {

            if (is_file($file)) {
                unlink($file);
                \Log::info("Archivo temporal eliminado", ['file' => $file]);
            }
        }
        $zipPrincipalName = 'dtes_export_' . date('Y-m-d') . '_' . time() . '.zip';
        $zipPrincipalPath = storage_path("app/dte_reportes/{$zipPrincipalName}");


        $zipPrincipal = new \ZipArchive;

        $result = $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            \Log::error('No se pudo abrir el ZIP', [
                'path' => $zipPrincipalPath,
                'result' => $result
            ]);

            return back()->with('error', 'No se pudo crear el archivo ZIP.');
        }

        //Inicializar consulta con relaciones necesarias para evitar N+1
        $dateFrom = $request->dateFrom ? Carbon::parse($request->dateFrom)->startOfDay() : Carbon::now()->startOfDay();
        $dateTo = $request->dateTo ? Carbon::parse($request->dateTo)->endOfDay() : Carbon::now()->endOfDay();
        $storeId = $store->id;
        //llamar store id para filtrar ventas


        $query = Sale::with([
            'store',
            'store.taxInfo',
            'customer',
            'details.productType',
            'creditNotes.creditNoteDetails.productType',
            'debitNotes.debitNoteDetails.productType',
            'tipoDte'
        ])
            ->where(function ($q) {
                $q->where('dte_status', 'PROCESADO');
            })

            ->where('store_id', $storeId)
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->where('environment', 'Development');
        //logs para verificar las tiendas y fechas

        $storeName = $store->store_name;
        \Log::info('DEBUG QUERY', [
            'store_id' => $storeId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'count' => $query->count()
        ]);


        $query->chunk(40, function ($sales) use ($zipPrincipal) {

            $saleIds = $sales->pluck('id');

            $dteResponses = DteResponse::whereIn('sale_id', $saleIds)
                ->get()
                ->keyBy('sale_id');

            foreach ($sales as $sale) {

                $dteResponse = $dteResponses[$sale->id] ?? null;

                $tipo = strtolower($sale->tipoDte->codigo ?? '');

                $tipoDteDescripcion = [
                    '01' => 'Factura',
                    '03' => 'Crédito Fiscal',
                    '14' => 'Factura Sujeto Excluido',
                ];

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
                        \Log::warning("Tipo DTE desconocido", [
                            "sale_id" => $sale->id,
                            "tipo" => $tipo
                        ]);
                        continue 2;
                }

                if ($dteResponse) {
                    $json['sello_recibido'] = $dteResponse->sello_recibido;
                }

                $codigoGen = $sale->codigo_generacion;

                // Guardar JSON temporal
                $jsonFilename = storage_path("app/dte_reportes/temp/dte_{$codigoGen}.json");
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
                    'dte' => $json,
                    'store' => $sale->store->store_name,
                    'emisor' => $json['emisor'],
                    'receptor' => $tipo === '14' ? $json['sujetoExcluido'] : $json['receptor'],
                    'resumen' => $json['resumen'],
                    'qrImage' => $qrImage,
                    'dteResponse' => $dteResponse
                ]);

                $pdfFilename = storage_path("app/dte_reportes/temp/dte_{$codigoGen}.pdf");
                $pdf->save($pdfFilename);

                // Agregar directo al ZIP principal
                $zipIndividualName = "DTE_{$codigoGen}.zip";
                $zipIndividualPath = storage_path("app/dte_reportes/temp/{$zipIndividualName}");

                $zipIndividual = new \ZipArchive;
                $result = $zipIndividual->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                if ($result !== true) {
                    \Log::error("No se pudo crear ZIP individual", [
                        "codigoGen" => $codigoGen
                    ]);
                    continue;
                }

                $zipIndividual->addFile($jsonFilename, "dte_{$codigoGen}.json");
                $zipIndividual->addFile($pdfFilename, "dte_{$codigoGen}.pdf");

                $zipIndividual->close();

                $zipPrincipal->addFile($zipIndividualPath, $zipIndividualName);
            }
        });

        $zipPrincipal->close();

        // borrar temporales para no llenar disco
        //unlink($jsonFilename);
        //unlink($pdfFilename);

        if (!file_exists($zipPrincipalPath)) {

            \Log::error("ZIP principal no existe", [
                "path" => $zipPrincipalPath
            ]);

            return back()->with('error', 'No se pudo generar el ZIP.');
        }

        \Log::info("ZIP principal creado", [
            "path" => $zipPrincipalPath,
            "size" => filesize($zipPrincipalPath)
        ]);

        // Subir a OCI
        $oci->uploadReportsToOCI(
            $zipPrincipalName,
            $zipPrincipalPath
        );

        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }

    // Descargar ZIP desde OCI Bucket
    public function downloadDteReporteFromOCI(OciService $oci)
    {
        $zipName = 'dtes_export_' . date('Y-m-d') . '.zip';
        $localPath = storage_path("app/dte_reportes/{$zipName}");

        // Lógica para descargar el archivo desde OCI
        $oci->downloadReportFromOCI($zipName, $localPath);

        return response()->download($localPath)->deleteFileAfterSend(true);
    }


    public function dteReporteNC(Request $request, Store $store, OCIService $oci)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $basePath = storage_path("app/dteNC_reportes");
        $tempPath = storage_path("app/dteNC_reportes/temp");

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
            Log::info("Directorio NC base creado", ['path' => $basePath]);
        }

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
            Log::info("Directorio NC temp creado", ['path' => $tempPath]);
        }

        foreach (glob($tempPath . '/*') as $file) {
            if (is_file($file)) unlink($file);
        }

        $zipPrincipalName = 'dtesNC_export_' . date('Y-m-d') . '_' . time() . '.zip';
        $zipPrincipalPath = storage_path("app/dteNC_reportes/{$zipPrincipalName}");

        $zipPrincipal = new \ZipArchive;

        $result = $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            return back()->with('error', 'No se pudo crear ZIP NC.');
        }

        $dateFrom = $request->dateFrom
            ? Carbon::parse($request->dateFrom)->startOfDay()
            : Carbon::now()->startOfDay();

        $dateTo = $request->dateTo
            ? Carbon::parse($request->dateTo)->endOfDay()
            : Carbon::now()->endOfDay();

        $query = CreditNote::with([
            'sale',
            'store.taxInfo',
            'customer',
            'creditNoteDetails.productType',
            'tipoDte'
        ])
            ->where('dte_status', 'PROCESADO')
            ->where('store_id', $store->id)
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        $query->chunk(40, function ($notas) use ($zipPrincipal, $tempPath) {

            foreach ($notas as $nc) {

                $sale = $nc->sale;

                $json = $this->dteService->buildDTEJsonNC($nc, $sale);

                $codigoGen = $json['identificacion']['codigoGeneracion'];

                $jsonPath = "{$tempPath}/dte_{$codigoGen}.json";
                file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT));

                $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                    . "?ambiente=01"
                    . "&codGen={$codigoGen}"
                    . "&fechaEmi=" . date('Y-m-d', strtotime($json['identificacion']['fecEmi']));

                $renderer = new ImageRenderer(new RendererStyle(150), new SvgImageBackEnd());
                $writer = new Writer($renderer);
                $qrImage = base64_encode($writer->writeString($urlQR));

                $pdf = Pdf::loadView('reportes.notascredito', [
                    'dte' => $json,
                    'emisor' => $json['emisor'],
                    'receptor' => $json['receptor'],
                    'resumen' => $json['resumen'],
                    'qrImage' => $qrImage
                ]);

                $pdfPath = "{$tempPath}/dte_{$codigoGen}.pdf";
                $pdf->save($pdfPath);

                $zipIndividualPath = "{$tempPath}/DTE_{$codigoGen}.zip";

                $zip = new \ZipArchive;
                if ($zip->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    $zip->addFile($jsonPath, "dte_{$codigoGen}.json");
                    $zip->addFile($pdfPath, "dte_{$codigoGen}.pdf");
                    $zip->close();
                }

                $zipPrincipal->addFile($zipIndividualPath, "DTE_{$codigoGen}.zip");
            }
        });

        $zipPrincipal->close();

        $oci->uploadReportsToOCI($zipPrincipalName, $zipPrincipalPath);

        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }


    public function dteReporteND(Request $request, Store $store, OCIService $oci)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $basePath = storage_path("app/dteND_reportes");
        $tempPath = storage_path("app/dteND_reportes/temp");

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
            Log::info("Directorio ND base creado", ['path' => $basePath]);
        }

        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
            Log::info("Directorio ND temp creado", ['path' => $tempPath]);
        }

        foreach (glob($tempPath . '/*') as $file) {
            if (is_file($file)) unlink($file);
        }

        $zipPrincipalName = 'dtesND_export_' . date('Y-m-d') . '_' . time() . '.zip';
        $zipPrincipalPath = storage_path("app/dteND_reportes/{$zipPrincipalName}");

        $zipPrincipal = new \ZipArchive;

        $result = $zipPrincipal->open($zipPrincipalPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            return back()->with('error', 'No se pudo crear ZIP ND.');
        }

        $dateFrom = $request->dateFrom
            ? Carbon::parse($request->dateFrom)->startOfDay()
            : Carbon::now()->startOfDay();

        $dateTo = $request->dateTo
            ? Carbon::parse($request->dateTo)->endOfDay()
            : Carbon::now()->endOfDay();

        $query = DebitNote::with([
            'sale',
            'store.taxInfo',
            'customer',
            'debitNoteDetails.productType',
            'tipoDte'
        ])
            ->where('dte_status', 'PROCESADO')
            ->where('store_id', $store->id)
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);

        $query->chunk(40, function ($notas) use ($zipPrincipal, $tempPath) {

            foreach ($notas as $nd) {

                $sale = $nd->sale;

                $json = $this->dteService->buildDTEJsonND($nd, $sale);

                $codigoGen = $json['identificacion']['codigoGeneracion'];

                $jsonPath = "{$tempPath}/dte_{$codigoGen}.json";
                file_put_contents($jsonPath, json_encode($json, JSON_PRETTY_PRINT));

                $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                    . "?ambiente=01"
                    . "&codGen={$codigoGen}"
                    . "&fechaEmi=" . date('Y-m-d', strtotime($json['identificacion']['fecEmi']));

                $renderer = new ImageRenderer(new RendererStyle(150), new SvgImageBackEnd());
                $writer = new Writer($renderer);
                $qrImage = base64_encode($writer->writeString($urlQR));

                $pdf = Pdf::loadView('reportes.notasdebito', [
                    'dte' => $json,
                    'emisor' => $json['emisor'],
                    'receptor' => $json['receptor'],
                    'resumen' => $json['resumen'],
                    'qrImage' => $qrImage
                ]);

                $pdfPath = "{$tempPath}/dte_{$codigoGen}.pdf";
                $pdf->save($pdfPath);

                $zipIndividualPath = "{$tempPath}/DTE_{$codigoGen}.zip";

                $zip = new \ZipArchive;
                if ($zip->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    $zip->addFile($jsonPath, "dte_{$codigoGen}.json");
                    $zip->addFile($pdfPath, "dte_{$codigoGen}.pdf");
                    $zip->close();
                }

                $zipPrincipal->addFile($zipIndividualPath, "DTE_{$codigoGen}.zip");
            }
        });

        $zipPrincipal->close();

        $oci->uploadReportsToOCI($zipPrincipalName, $zipPrincipalPath);

        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }
}
