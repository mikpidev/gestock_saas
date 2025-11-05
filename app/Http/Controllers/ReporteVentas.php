<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\Sale;
use App\Models\SaleDetail;
use Barryvdh\DomPDF\Facade\Pdf;


use Illuminate\Http\Request;


class ReporteVentas extends Controller
{
    //Reporte verntas

    public function index() 
    {

        ini_set('memory_limit', '512M');

        $sales = Sale::with((['store', 'user']))->get();
        $saleDetails = SaleDetail::with(['productType'])->get();
        $productType = ProductType::all();  

        //Generar reporte de ventas PDF
        $pdf = \PDF::loadView('reportes.ventas', compact('sales', 'saleDetails', 'productType'));
        return $pdf->download('reporte_ventas.pdf');
    }

    
}
