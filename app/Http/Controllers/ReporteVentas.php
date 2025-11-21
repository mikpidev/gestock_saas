<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Http\Controllers\DTEController;
use App\Services\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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

    public function dteReporte()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(120);
    
        $basePath = storage_path("app/dte_reportes/");
        $tempPath = storage_path("app/dte_reportes/temp/");
    
        // Crear carpetas si no existen
        if (!file_exists($basePath)) mkdir($basePath, 0777, true);
        if (!file_exists($tempPath)) mkdir($tempPath, 0777, true);
    
        // Nombre ZIP PRINCIPAL
        $zipPrincipalName = 'dtes_export_' . date('Y-m-d') . '.zip';
        $zipPrincipalPath = $basePath . $zipPrincipalName;
    
        // Crear ZIP PRINCIPAL
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
            ->whereDate('sale_date', '>=', now()->subDays(16));
    
        $query->chunk(110, function ($sales) use ($basePath, $tempPath, $zipPrincipal) {
    
            foreach ($sales as $sale) {
    
                $sale->load([
                    'store.taxInfo',
                    'customer',
                    'details.productType',
                    'creditNotes.creditNoteDetails.productType',
                    'debitNotes.debitNoteDetails.productType',
                    'tipoDte'
                ]);
    
                // Mapeo de descripciones
                $tipoDteDescripcion = [
                    '01' => 'Factura',
                    '03' => 'Crédito Fiscal',
                    '14' => 'Factura Sujeto Excluido',
                ];
    
                $tipo = strtolower($sale->tipoDte->codigo ?? '');
    
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
                        continue 2; // Saltar a la siguiente venta
                }
    
                $codigoGen = $sale->codigo_generacion;
    
                // ---------------------------------------------------------------------
                // 1️⃣ Guardar JSON
                // ---------------------------------------------------------------------
                $jsonFilename = "{$tempPath}/dte_{$codigoGen}.json";
                file_put_contents($jsonFilename, json_encode($json, JSON_PRETTY_PRINT));
    
                // ---------------------------------------------------------------------
                // 2️⃣ Generar QR
                // ---------------------------------------------------------------------
                $urlQR = "https://admin.factura.gob.sv/consultaPublica"
                    . "?ambiente=00"
                    . "&codGen={$json['identificacion']['codigoGeneracion']}"
                    . "&fechaEmi=" . date('Y-m-d', strtotime($json['identificacion']['fecEmi']));
    
                $renderer = new ImageRenderer(
                    new RendererStyle(150),
                    new SvgImageBackEnd()
                );
    
                $writer = new Writer($renderer);
                $qrImage = base64_encode($writer->writeString($urlQR));
    
                // ---------------------------------------------------------------------
                // 3️⃣ Generar PDF
                // ---------------------------------------------------------------------
                $pdf = Pdf::loadView('reportes.ventas', [
                    'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
                    'dte'      => $json,
                    'emisor'   => $json['emisor'],
                    'receptor' => $json['receptor'],
                    'resumen'  => $json['resumen'],
                    'qrImage'  => $qrImage
                ]);
    
                $pdfFilename = "{$tempPath}/dte_{$codigoGen}.pdf";
                $pdf->save($pdfFilename);
    
                // ---------------------------------------------------------------------
                // 4️⃣ Crear ZIP individual del DTE
                // ---------------------------------------------------------------------
                $zipIndividualName = "DTE_{$codigoGen}.zip";
                $zipIndividualPath = "{$tempPath}/{$zipIndividualName}";
    
                $zipIndividual = new \ZipArchive;
                $zipIndividual->open($zipIndividualPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    
                $zipIndividual->addFile($jsonFilename, "dte_{$codigoGen}.json");
                $zipIndividual->addFile($pdfFilename, "dte_{$codigoGen}.pdf");
    
                $zipIndividual->close();
    
                // Agregar ZIP indiv al ZIP principal
                $zipPrincipal->addFile($zipIndividualPath, $zipIndividualName);
    
                \Log::info("DTE generado correctamente", [
                    "sale_id" => $sale->id,
                    "zip" => $zipIndividualPath
                ]);
            }
        });
    
        // -------------------------------------------------------------------------
        // 5️⃣ Cerrar ZIP principal
        // -------------------------------------------------------------------------
        $zipPrincipal->close();
    
        // -------------------------------------------------------------------------
        // 6️⃣ Respuesta con link de descarga
        // -------------------------------------------------------------------------
        return response()->download($zipPrincipalPath)->deleteFileAfterSend(true);
    }
    
    private function processNC($nc, $sale, $path)
    {
        $json = $this->dteService->buildDTEJsonNC($nc, $sale);
        $file = $nc->codigo_generacion ?? "{$sale->codigo_generacion}_NC_{$nc->id}";

        file_put_contents("{$path}{$file}.json", json_encode($json, JSON_PRETTY_PRINT));

        $pdf = Pdf::loadView('reportes.dte.nc', ['sale' => $sale, 'note' => $nc]);
        $pdf->save("{$path}{$file}.pdf");
    }

    private function processND($nd, $sale, $path)
    {
        $json = $this->dteService->buildDTEJsonND($nd, $sale);
        $file = $nd->codigo_generacion ?? "{$sale->codigo_generacion}_ND_{$nd->id}";

        file_put_contents("{$path}{$file}.json", json_encode($json, JSON_PRETTY_PRINT));

        $pdf = Pdf::loadView('reportes.dte.nd', ['sale' => $sale, 'note' => $nd]);
        $pdf->save("{$path}{$file}.pdf");
    }
}
