<?php

namespace App\Http\Controllers;

use App\Models\Contingencia;
use App\Models\Sale;
use App\Models\DebitNote;
use App\Models\CreditNote;
use App\Models\InvoiceNumber;
use App\Models\Store;
use App\Services\ContingenciaService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ContingenciaController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Listado de contingencias
     */
    public function index(Store $store)
    {
        $contingencias = Contingencia::where('store_id', $store->id)
            ->orderByDesc('id')
            ->get();

        //retornar vista con contingencias
        return view('contingencias.index', compact('contingencias', 'store' ));
    }

    //create function

    public function create(Store $store)
    {
        //pasar tipo de contingencias a la vista

        $tipoContingencias = \App\Models\TipoContingencia::all();

        return view('contingencias.create', compact('store','tipoContingencias'));
    }

    /**
     * Crear / abrir contingencia
     */
    public function store(Request $request, Store $store)
    {
        $request->validate([
            'tipo_contingencia'   => 'required|exists:tipo_contingencias,id',
            'fecha_inicio'        => 'required|date',
            'hora_inicio'         => 'required',
            'motivo_contingencia' => 'nullable|string',
        ]);

        $fechaHoraInicio = Carbon::createFromFormat(
            'Y-m-d H:i',
            $request->fecha_inicio . ' ' . $request->hora_inicio
        );


        $invoiceNumber = InvoiceNumber::getContingenciaNumber($store->id, $request->tipo_contingencia);


        Contingencia::create([
            'store_id'             => $store->id,
            'user_id'              => auth()->id(),
            'tipo_contingencia_id' => $request->tipo_contingencia,
            'fecha_hora_inicio'    => $fechaHoraInicio,
            'estado'               => 'ABIERTA',
            'codigo_generacion'     => $invoiceNumber->codigo_generacion,
            'motivo_contingencia'  => $request->motivo_contingencia,
        ]);

        return redirect()
            ->route('contingencias.index', [
                'store' => $store->id
            ])
            ->with('success', 'Contingencia creada correctamente');
    }



    /**
     * Actualizar motivo
     */
    public function update(Request $request, Contingencia $contingencia)
    {
        if ($contingencia->estado !== 'ABIERTA') {
            return response()->json([
                'message' => 'Solo se puede editar una contingencia abierta'
            ], 422);
        }

        $request->validate([
            'motivo_contingencia' => 'nullable|string'
        ]);

        $contingencia->update([
            'motivo_contingencia' => $request->motivo_contingencia
        ]);

        return response()->json([
            'message' => 'Contingencia actualizada correctamente',
            'contingencia' => $contingencia
        ]);
    }

    /**
     * Cerrar contingencia
     */
    public function close(Store $store, Contingencia $contingencia)
    {
        if ($contingencia->estado !== 'ABIERTA') {
            return response()->json([
                'contingencia' => $contingencia,
                'message' => 'La contingencia no está abierta'
            ], 422);
        }
    
        // ===============================
        // TRANSACCIÓN SOLO BD
        // ===============================
        DB::transaction(function () use ($contingencia) {
    
            $inicio = $contingencia->fecha_hora_inicio;
    
            // 1. Documentos pendientes
            $ventas = Sale::where('store_id', $contingencia->store_id)
                ->where('dte_status', 'PENDIENTE')
                ->whereBetween('created_at', [$inicio, now()])
                ->get();
    
            $nd = DebitNote::where('store_id', $contingencia->store_id)
                ->where('dte_status', 'PENDIENTE')
                ->whereBetween('created_at', [$inicio, now()])
                ->get();
    
            $nc = CreditNote::where('store_id', $contingencia->store_id)
                ->where('dte_status', 'PENDIENTE')
                ->whereBetween('created_at', [$inicio, now()])
                ->get();
    
            // 2. Asociar contingencia
            $ventas->each(fn ($v) => $v->update(['contingencia_id' => $contingencia->id]));
            $nd->each(fn ($d) => $d->update(['contingencia_id' => $contingencia->id]));
            $nc->each(fn ($c) => $c->update(['contingencia_id' => $contingencia->id]));
    
            // 3. Cerrar contingencia
            $contingencia->update([
                'estado' => 'CERRADA',
                'fecha_hora_fin' => now()
            ]);
        });
    
        // ===============================
        // Cargar relaciones necesarias
        // ===============================
        $contingencia->load([

            'store.taxInfo',
            'sales.tipoDte',
            'sales',
            'debitNotes',
            'creditNotes'
        ]);

        \Log::info('CONTINGENCIA DEBUG', [
            'contingencia_id' => $contingencia->id,
            'ventas' => $contingencia->sales->pluck('id'),
            'nd' => $contingencia->debitNotes->pluck('id'),
            'nc' => $contingencia->creditNotes->pluck('id'),
        ]);
        
    
        // ===============================
        // Envío a Hacienda (fuera TX)
        // ===============================
        try {
            app(\App\Http\Controllers\DTEController::class)
                ->generarDTEContingencia($contingencia);
        } catch (\Throwable $e) {
            \Log::error('Error enviando contingencia a MH', [
                'contingencia_id' => $contingencia->id,
                'error' => $e->getMessage()
            ]);
        }
    
        return response()->json([
            'message' => 'Contingencia cerrada y enviada correctamente',
            'contingencia_id' => $contingencia->id,
            'total_dte' =>
                $contingencia->sales->count() +
                $contingencia->debitNotes->count() +
                $contingencia->creditNotes->count()
        ]);
    }
}    
