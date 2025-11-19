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

        $path = storage_path("app/dte_reportes/");
        if (!file_exists($path)) mkdir($path, 0777, true);

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

        $query->chunk(110, function ($sales) use ($path) {

            foreach ($sales as $sale) {

                // Cargar relaciones por cada venta (sin excluir ventas)
                $sale->load([
                    'store.taxInfo',
                    'customer',
                    'details.productType',
                    'creditNotes.creditNoteDetails.productType',
                    'debitNotes.debitNoteDetails.productType',
                    'tipoDte'
                ]);

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
                        Log::warning("Tipo DTE desconocido o faltante", [
                            "sale_id" => $sale->id,
                            "tipo" => $tipo
                        ]);
                        continue 2;
                }

               

                // Guardar cada JSON
                $jsonFilename = "{$path}/dte_{$sale->codigo_generacion}.json";
                file_put_contents($jsonFilename, json_encode($json, JSON_PRETTY_PRINT));

                //Generar QR (AQUÍ se agrega)
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
                
                // Generar PDF
                $pdf = Pdf::loadView('reportes.ventas', [
                    'dte'      => $json,
                    'emisor'   => $json['emisor'],
                    'receptor' => $json['receptor'],
                    'resumen'  => $json['resumen'],
                    'qrImage'  => $qrImage
                ]);
                $pdf->save("{$path}/dte_{$sale->codigo_generacion}.pdf");

                \Log::info("DTE generado correctamente", [
                    "sale_id" => $sale->id,
                    "path" => $jsonFilename
                ]);
            }
        });

        return response()->json([
            "message" => "Reportes generados correctamente",
            "path" => $path
        ]);
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
