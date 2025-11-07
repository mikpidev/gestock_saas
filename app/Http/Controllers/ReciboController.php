<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;
use App\Models\StoreTaxInfo;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;


class ReciboController extends Controller
{

    public function print($storeId, $saleId)
    {
        ini_set('memory_limit', '512M');

        $venta = Sale::with(['customer', 'details.productType'])
            ->where('store_id', $storeId)
            ->findOrFail($saleId);

        // ✅ Construcción del URL de Hacienda QR
        $urlQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=00"
            . "&codGen={$venta->codigo_generacion}"
            . "&fechaEmi={$venta->sale_date->format('Y-m-d')}";

        // ✅ Generar QR en SVG (compatible con DomPDF y sin necesidad de Imagick/GD)
        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrImage = base64_encode($writer->writeString($urlQR));

        $pdf = Pdf::loadView('recibos.recibo', compact('venta', 'urlQR', 'qrImage'))
            ->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->stream('recibo_' . $venta->id . '.pdf', ['Attachment' => false]);
    }


    public function reprint($storeId, $saleId)
    {
        ini_set('memory_limit', '512M');

        // Puedes agregar logs si quieres guardar cuántas veces se reimprimió
        return $this->print($storeId, $saleId);
    }
}
