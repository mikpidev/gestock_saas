<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;

class ReciboController extends Controller
{
    public function mostrar($storeId, $saleId)
    {
        // Carga la venta y sus relaciones
        $venta = Sale::with(['customer', 'saleDetails.productType'])
            ->where('store_id', $storeId)
            ->findOrFail($saleId);

        $pdf = Pdf::loadView('recibos.recibo', compact('venta'))
                  ->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->stream('recibo_'.$venta->id.'.pdf');
    }
}
