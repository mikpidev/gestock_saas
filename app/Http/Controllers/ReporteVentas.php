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
        $pdf = \PDF::loadView('reportes.ventas', compact('store', 'sales', 'saleDetails', 'productType'));
        return $pdf->download('reporte_ventas.pdf');
    }

    public function dteReporte()
    {

        ini_set('memory_limit', '512M');

        $sales = Sale::with(([

            'store.taxInfo',
            'customer',
            'creditNotes.creditNoteDetails.productType',
            'debitNotes.debitNoteDetails.productType',
        ]))->get();

        $path = storage_path("app/dte_reportes/");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        foreach ($sales as $sale) {

            $baseFile = $sale->codigo_generacion ?? '';
            $tipo = strtolower($sale->tipoDte->codigo ?? '');

            //Procesar doc basado en DTE

            switch ($tipo) {

                case '01': //FE
                    $json = $this->buildDTEJsonFE($sale);
                    $pdf = Pdf::loadView('reportes.ventas', compact('sale'));
                    break;
                case '03': //CCF
                    $json = $this->buildDTEJsonCCF($sale);
                    $pdf = Pdf::loadView('reportes.ventas', compact('sale'));
                    break;
                case '14': //SE
                    $json = $this->buildDTEJsonSE($sale);
                    $pdf = Pdf::loadView('reportes.ventas', compact('sale'));
                    break;
                default:
                    $json = null;
                    $pdf = null;
                    break;
            }

            //Guardamos archivos
            if ($json && $pdf) {
                file_put_contents("{$path}{$baseFile}.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $pdf->save("{$path}{$baseFile}.pdf");
            }

            //Notas de Credito (NC)

            foreach ($sale->creditNotes as $nc) {

                $jsonNC = $this->buildDTEJsonNC($nc, $sale);
                $pdfNC = Pdf::loadView('reportes.dte.nc', ['sale' => $sale, 'note' => $nc]);

                $name = $nc->codigo_generacion ?? ($baseFile . "_NC_" . $nc->id);
                file_put_contents("{$path}{$name}.json", json_encode($jsonNC, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $pdfNC->save("{$path}{$name}.pdf");
            }

            foreach ($sale->debitNotes as $nd) {
                $jsonND = $this->buildDTEJsonND($nd, $sale);
                $pdfND  = Pdf::loadView('reportes.dte.nd', ['sale' => $sale, 'note' => $nd]);

                $name = $nd->codigo_generacion ?? ($baseFile . "_ND_" . $nd->id);
                file_put_contents("{$path}{$name}.json", json_encode($jsonND, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $pdfND->save("{$path}{$name}.pdf");
            }
        }


        return response()->json([
            "message" => "Todos los DTE, NC, ND y Anulaciones generados correctamente",
            "path" => $path
        ]);
    }

    private function buildDTEJsonFE($sale)
    {
        return [
            "codigoGeneracion" => $sale->codigo_generacion,
            "tipoDte"  => "FE",
            "fecha"    => $sale->created_at->format('Y-m-d H:i:s'),
            "cliente"  => $sale->customer->nombre ?? null,
            "total"    => $sale->total_amount,
            "items"    => $sale->details->map(function ($d) {
                return [
                    "descripcion" => $d->productType->name ?? null,
                    "cantidad"    => $d->qty,
                    "precio"      => $d->price,
                    "subtotal"    => $d->qty * $d->price
                ];
            }),
        ];
    }

    private function buildDTEJsonCCF($sale)
    {
        return [
            "codigoGeneracion" => $sale->codigo_generacion,
            "tipoDte"  => "CCF",
            "fecha"    => $sale->created_at->format('Y-m-d H:i:s'),
            "cliente"  => $sale->customer->nombre ?? null,
            "total"    => $sale->total_amount,
            "iva"      => $sale->total_iva,
        ];
    }

    private function buildDTEJsonSE($sale)
    {
        return [
            "codigoGeneracion" => $sale->codigo_generacion,
            "tipoDte"  => "SE",
            "fecha"    => $sale->created_at->format('Y-m-d H:i:s'),
            "cliente"  => $sale->customer->nombre ?? null,
            "total"    => $sale->total_amount,
        ];
    }

    private function buildDTEJsonNC($note, $sale)
    {
        return [
            "codigoGeneracion" => $note->codigo_generacion,
            "tipoDte"   => "NC",
            "referencia" => $sale->codigo_generacion,
            "fecha"     => $note->created_at->format('Y-m-d H:i:s'),
            "motivo"    => $note->motivo ?? null,
            "total"     => $note->total_amount ?? 0,
            "items"     => $note->creditNoteDetails->map(function ($d) {
                return [
                    "descripcion" => $d->productType->name ?? null,
                    "cantidad"    => $d->qty,
                    "precio"      => $d->price,
                ];
            }),
        ];
    }

    private function buildDTEJsonND($note, $sale)
    {
        return [
            "codigoGeneracion" => $note->codigo_generacion,
            "tipoDte"   => "ND",
            "referencia" => $sale->codigo_generacion,
            "fecha"     => $note->created_at->format('Y-m-d H:i:s'),
            "motivo"    => $note->motivo ?? null,
            "total"     => $note->total_amount ?? 0,
            "items"     => $note->debitNoteDetails->map(function ($d) {
                return [
                    "descripcion" => $d->productType->name ?? null,
                    "cantidad"    => $d->qty,
                    "precio"      => $d->price,
                ];
            }),
        ];
    }
}
