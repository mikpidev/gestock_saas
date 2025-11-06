<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Sale;

class ReciboController extends Controller
{
    // Imprimir recibo (primera vez o directo por URL)
    public function print($storeId, $saleId)
    {
        $venta = Sale::with(['customer', 'details.productType'])
            ->where('store_id', $storeId)
            ->findOrFail($saleId);
    
        $pdf = Pdf::loadView('recibos.recibo', compact('venta'))
                  ->setPaper([0, 0, 226.77, 600], 'portrait');
    
        return $pdf->stream('recibo_'.$venta->id.'.pdf', ['Attachment' => false]);
    }
    
    public function reprint($storeId, $saleId)
    {
        // Puedes agregar logs si quieres guardar cuántas veces se reimprimió
        return $this->print($storeId, $saleId);
    }
    
}
