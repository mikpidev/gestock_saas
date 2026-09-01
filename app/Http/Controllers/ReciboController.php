<?php

namespace App\Http\Controllers;

use App\Models\DteResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
use App\Models\Store;
use App\Services\DocumentService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ReciboController extends Controller
{

    protected $dteService;

    public function __construct(DocumentService $dteService)
    {
        $this->dteService = $dteService;
    }
    /**
     * Genera y muestra el PDF del recibo.
     */
    public function print($storeId, $saleId)
    {
        // Limitar memoria
        ini_set('memory_limit', '256M');

        // Traer venta con relaciones necesarias
        $venta = Sale::with([
            'customer:id,nombre,numDocumento,nrc',
            'user:id,name',
            'store:id,store_name,address',
            'store.taxInfo:id,store_id,nit,nrc,telefono',
            'details:id,sale_id,product_type_id,quantity,unit_price,subtotal',
            'details.productType:id,name',
        ])
            ->where('store_id', $storeId)
            ->findOrFail($saleId);
        // Traer respuesta DTE asociada
        $dteResponse = DteResponse::where('sale_id', $venta->id)->first();

        //Traer Store Name

        $logo = public_path($venta->store->store_name . '.png');
        // URL QR Hacienda
        $urlQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=01"
            . "&codGen={$venta->codigo_generacion}"
            . "&fechaEmi={$venta->sale_date->format('Y-m-d')}";

        // Generar QR en SVG (BaconQrCode)
        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrImage = base64_encode($writer->writeString($urlQR));

        // Calcular totales (si quieres pasar a la vista)
        $total = $venta->details->sum(fn($d) => $d->quantity * $d->subtotal);

        // Generar PDF usando la vista optimizada
        $pdf = Pdf::loadView('recibos.recibo', compact('venta', 'dteResponse', 'logo', 'urlQR', 'qrImage', 'total'))
            ->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->stream('recibo_' . $venta->id . '.pdf', ['Attachment' => false]);
    }


    public function preorder($storeId, $saleId)
    {
        // Limitar memoria
        ini_set('memory_limit', '256M');

        // Traer venta con relaciones necesarias
        $venta = Sale::with([
            'customer:id,nombre,numDocumento,nrc',
            'user:id,name',
            'store:id,store_name,address',
            'store.taxInfo:id,store_id,nit,nrc,telefono',
            'details:id,sale_id,product_type_id,quantity,unit_price,subtotal',
            'details.productType:id,name',
        ])
            ->where('store_id', $storeId)
            ->findOrFail($saleId);
        // Traer respuesta DTE asociada
        /*
        $dteResponse = DteResponse::where('sale_id', $venta->id)->first();

         // URL QR Hacienda
        $urlQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=01"
            . "&codGen={$venta->codigo_generacion}"
            . "&fechaEmi={$venta->sale_date->format('Y-m-d')}";

        // Generar QR en SVG (BaconQrCode)
        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrImage = base64_encode($writer->writeString($urlQR));

        // Calcular totales (si quieres pasar a la vista)
        $total = $venta->details->sum(fn($d) => $d->quantity * $d->subtotal); */

        // Generar PDF usando la vista optimizada
        $pdf = Pdf::loadView('recibos.pre_order', compact('venta'))
            ->setPaper([0, 0, 226.77, 400], 'portrait');

        return $pdf->stream('recibo_' . $venta->id . '.pdf', ['Attachment' => false]);
    }


    public function downloadDTE($storeId, $saleId)
    {
        // Limitar memoria
        ini_set('memory_limit', '256M');

        set_time_limit(120);

        $baseQuery = Sale::with([
            'store',
            'store.taxInfo',
            'customer',
            'details.productType',
            'creditNotes.creditNoteDetails.productType',
            'debitNotes.debitNoteDetails.productType',
            'tipoDte'
        ])->where('store_id', $storeId)
            ->findOrFail($saleId);


        $store = $baseQuery->store->store_name;

        $sale = $baseQuery;


        \Log::info("Base Query Sale", [
            'sale_id' => $sale->id,
            'Codigo de Generacion' => $sale->codigo_generacion,
            'Sale Date' => $sale->sale_date,
        ]);


        //Path para ventas app/store_name/dtes_export/month/day/year
        $basePath = storage_path("app/{$store}/dtes_export/" . date("Y/m/d"));

        \Log::info("Verficando si Base Path existe", ['path' => $basePath]);

        if (!is_dir($basePath)) {
            mkdir($basePath, 0775, true);
            \Log::info("Directorio base creado", ['path' => $basePath]);
        }

        // Generamos JSON del DTE

        $dteResponse = DteResponse::where('sale_id', $sale->id)->latest()->first();
        // Mapeo de descripciones
        $tipoDteDescripcion = [
            '01' => 'Factura',
            '03' => 'Crédito Fiscal',
            '14' => 'Factura Sujeto Excluido',
            // agregar los necesarios
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
                abort(400, 'Tipo DTE desconocido');
        }

        if ($dteResponse) {
            $json['sello_recibido'] = $dteResponse->sello_recibido;
        }

        $codigoGen = $sale->codigo_generacion;
        $jsonContent = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

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

        if ($tipo === '14') {
            $pdf = Pdf::loadView('recibos.pdf-SE', [
                'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
                'dte'      => $json,
                'emisor'   => $json['emisor'],
                'store'    => $store,
                //validar si es SE - pass sujetoExcluido en lugar de receptor
                'receptor' => $tipo === '14' ? $json['sujetoExcluido'] : $json['receptor'],
                'resumen'  => $json['resumen'],
                'qrImage'  => $qrImage,
                'dteResponse' => $dteResponse
            ]);
        } else {        // Generar PDF
            $pdf = Pdf::loadView('recibos.pdf', [
                'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
                'dte'      => $json,
                'emisor'   => $json['emisor'],
                'store'    => $store,
                //validar si es SE - pass sujetoExcluido en lugar de receptor
                'receptor' => $tipo === '14' ? $json['sujetoExcluido'] : $json['receptor'],
                'resumen'  => $json['resumen'],
                'qrImage'  => $qrImage,
                'dteResponse' => $dteResponse
            ]);
        }

        $pdfContent = $pdf->output();


        // Crear ZIP individual
        $zipIndividualName = "DTE_{$codigoGen}.zip";
        $zipIndividualPath = $basePath . "/DTE_{$codigoGen}.zip";

        if (file_exists($zipIndividualPath)) {
            unlink($zipIndividualPath);
        }
        $zipIndividual = new \ZipArchive;

        $result = $zipIndividual->open(
            $zipIndividualPath,
            \ZipArchive::CREATE
        );

        if ($result !== true) {
            return back()->with('error', 'No se pudo crear el ZIP.');
        }

        $zipIndividual->addFromString(
            "dte_{$codigoGen}.json",
            $jsonContent,

        );

        $zipIndividual->addFromString(
            "dte_{$codigoGen}.pdf",
            $pdfContent
        );

        \Log::info("Data de descarga", [
            'message' => "DTE Download Feature in process.",
            'sale ID' => $sale->id,
            'Base path' => $basePath,
            'Codigo Generacion' => $sale->codigo_generacion,
            'tipoDteDescripcion' => $tipoDteDescripcion[$tipo] ?? 'Desconocido',
            'emisor'   => $json['emisor'],
            'store'    => $store,
        ]);

        $zipIndividual->close();

        return response()->download(
            $zipIndividualPath,
            "DTE_{$codigoGen}.zip"
        );
    }
}
