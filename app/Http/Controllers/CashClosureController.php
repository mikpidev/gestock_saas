<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashClosure;
use App\Models\Sale;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Store;
use Carbon\Carbon;


class CashClosureController extends Controller
{
    //  Registrar un nuevo corte de caja
    public function closeCash(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id'
        ]);

        $storeId = $request->store_id;
        $userId  = auth()->id();

        // Último corte de ESA tienda
        $lastClosure = CashClosure::where('store_id', $storeId)
            ->orderByDesc('id')
            ->first();

        // Primera venta a tomar de esa tienda
        $fromSaleId = $lastClosure
            ? $lastClosure->to_sale_id + 1
            : Sale::where('store_id', $storeId)->min('id');

        // Obtener ventas nuevas SOLO de esa tienda
        $sales = Sale::where('store_id', $storeId)
            ->where('id', '>=', $fromSaleId)
            ->get();

        if ($sales->isEmpty()) {
            return back()->withErrors(['msg' => 'No hay ventas nuevas para realizar corte en esta tienda']);
        }

        $toSaleId = $sales->last()->id;

        // Cálculos
        $totalSalesCount = $sales->count();
        $amountSales     = $sales->sum('total_amount');
        $totalCash = $sales->where('payment_method', 'Efectivo')->sum('total_amount');
        $totalCard = $sales->where('payment_method', 'Tarjeta')->sum('total_amount');

        // Notas solo de ventas de esta tienda
        $creditNotes = CreditNote::whereHas('sale', function ($q) use ($storeId, $fromSaleId, $toSaleId) {
            $q->where('store_id', $storeId)
                ->whereBetween('id', [$fromSaleId, $toSaleId]);
        })->get();

        $debitNotes = DebitNote::whereHas('sale', function ($q) use ($storeId, $fromSaleId, $toSaleId) {
            $q->where('store_id', $storeId)
                ->whereBetween('id', [$fromSaleId, $toSaleId]);
        })->get();

        // Crear el corte de caja
        $closure = CashClosure::create([
            'store_id'            => $storeId,
            'user_id'             => $userId,
            'from_sale_id'        => $fromSaleId,
            'to_sale_id'          => $toSaleId,
            'total_sales'         => $totalSalesCount,
            'total_credit_notes'  => $creditNotes->count(),
            'total_debit_notes'   => $debitNotes->count(),
            'amount_sales'        => $amountSales,
            'amount_credit_notes' => $creditNotes->sum('total_amount'),
            'amount_debit_notes'  => $debitNotes->sum('total_amount'),
            'total_cash'          => $totalCash,
            'total_card'          => $totalCard,
        ]);

        return redirect()
            ->route('stores.cash.closures.index', ['store' => $storeId, 'id' => $closure->id])
            ->with('success', 'Corte de caja generado correctamente');
    }


    public function index(Store $store)
    {
        $closures = CashClosure::where('store_id', $store->id)->latest()->get();

        return view('recibos.index', compact('store', 'closures'));
    }

    //  Reimprimir o consultar un corte existente
    public function show($id)
    {
        $closure = CashClosure::with('store', 'user')->findOrFail($id);
        return response()->json($closure);
    }

    public function print($store, $id)
    {
        ini_set('memory_limit', '256M');

        // Obtener el cierre y asegurar que pertenece a la tienda
        $closure = CashClosure::with(['user:id,name', 'store:id,store_name'])
            ->where('store_id', $store) // asegurar que es de la tienda correcta
            ->findOrFail($id);

        // Obtener solo las ventas del cierre Y de la misma tienda
        $sales = Sale::where('store_id', $store)
            ->whereBetween('id', [$closure->from_sale_id, $closure->to_sale_id])
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('recibos.cash_closings', [
            'closure' => $closure,
            'sales'   => $sales,
        ])->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->stream("corte_caja_{$closure->id}.pdf", ['Attachment' => false]);
    }
}
