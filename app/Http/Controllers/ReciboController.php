<?php

namespace App\Http\Controllers;

use App\Models\DteResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
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
        $pdf = Pdf::loadView('recibos.recibo', compact('venta','dteResponse' ,'urlQR', 'qrImage', 'total'))
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


}
