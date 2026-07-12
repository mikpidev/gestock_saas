<?php

namespace App\Http\Controllers;

use App\Models\DteResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
use App\Models\Store;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ReciboController extends Controller
{
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
        $pdf = Pdf::loadView('recibos.recibo', compact('venta', 'dteResponse','logo', 'urlQR', 'qrImage', 'total'))
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


        $storeName = $baseQuery->store->store_name;

        $sale = $baseQuery;

        
        \Log::info("Base Query Sale", [
            'sale_id' => $sale->id,
            'Codigo de Generacion' => $sale->codigo_generacion,
            'Sale Date' => $sale->sale_date,
        ]);


        //Path para ventas app/store_name/dtes_export/month/day/year
        $basePath = storage_path("app/{$storeName}/dtes_export/" . date("Y/m/d"));
        /*         $tempPath = storage_path("app/{$storeName}/dtes_export/" . date("Y/m/d") . "/temp");
 */
        \Log::info("Verficando si Base Path existe", ['path' => $basePath]);

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
            \Log::info("Directorio base creado", ['path' => $basePath]);
        }


        /*         if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
            \Log::info("Directorio temporal creado", ['path' => $tempPath]);
        } */

        //creamos Zip donde se guardara Json y PDF
        $zipFileName = "DTE_{$sale->codigo_generacion}.zip";
        $zipFilePath = $basePath . '/' . $zipFileName;


        $zip = new \ZipArchive();
        $result = $zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);


        if ($result !== true) {
            \Log::error('No se pudo abrir el ZIP', [
                'path' => $zipFilePath,
                'result' => $result
            ]);

            return back()->with('error', 'No se pudo crear el archivo ZIP.');
        }

        // Generamos JSON del DTE

        $baseQuery->chunk(40, function ($sales) use ($zip){
            
        });

        return response()->json(
            [
                'message' => "DTE Download Feature in process.",
                'Base path' => $basePath,
                'Codigo Generacion' => $sale->codigo_generacion,
                'Zip File Name' => $zipFileName,
                'Zip File Path' => $zipFilePath,
            ],
            200
        );
    }
}
