<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SaleDteLog;
use App\Models\DebitNote;
use App\Models\VoidND;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\VoidService;

class VoidNDController extends Controller
{
    protected DocumentService $documentService;
    protected HaciendaAuthService $authService;
    protected VoidService $voidService;

    public function __construct(
        DocumentService $documentService,
        HaciendaAuthService $authService,
        VoidService $voidService
    ) {
        $this->documentService = $documentService;
        $this->authService = $authService;
        $this->voidService = $voidService;
    }

    /**
     * Generar anulación de ND
     */
    public function voidND(DebitNote $debitNote)
    {
        try {
            $sale = $debitNote->sale;
            if (!$sale) {
                throw new \Exception('No se encontró la venta asociada a la ND');
            }

            // Crear registro de anulación
            $void = $debitNote->voids()->create([
                'codigo_generacion' => $debitNote->codigo_generacion,
                'void_date' => now(),
                'desc' => 'Anulación ND'
            ]);

            // Construir JSON de la ND a anular
            $dteJson = $this->documentService->buildDTEJsonVoidND($debitNote, $sale, $void);
            SaleDteLog::info("DTE ND antes de firmar", [
                'debit_note_id' => $debitNote->id,
                'sale_id' => $sale->id,
            ]);

            // Firmar documento
            $signedData = $this->documentService->signDocument($dteJson);
            SaleDteLog::info("Documento ND firmado", ['debit_note_id' => $debitNote->id]);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            // Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendNDVoidToHacienda($debitNote, $void, $signedData, $token);
            SaleDteLog::info("Respuesta Hacienda ND", array_merge([
                'debit_note_id' => $debitNote->id,
                'sale_id' => $sale->id,
                'void_id' => $void->id,
            ], SaleDteLog::mhSummary(is_array($haciendaResponse) ? $haciendaResponse : [])));

            // Guardar respuesta de Hacienda en VoidND
            $void->update([
                'estado' => $haciendaResponse['estado'] ?? 'ERROR',
                'sello_recibido' => $haciendaResponse['selloRecibido'] ?? null,
                'response_json' => $haciendaResponse
            ]);

            return response()->json($haciendaResponse);

        } catch (\Throwable $th) {
            SaleDteLog::error('Error anulando ND', [
                'debit_note_id' => $debitNote->id,
                'sale_id' => $debitNote->sale_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return response()->json([
                'error' => 'Error anulando ND',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Destroy con validación de Hacienda
     */
    public function destroy(DebitNote $debitNote)
    {
        try {
            $response = $this->voidND($debitNote);

            if (($response->getData()->estado ?? '') !== 'PROCESADO') {
                return redirect()->back()->withErrors('Hacienda no confirmó la anulación de la ND.');
            }

            $debitNote->delete();

            return redirect()->route('stores.sales.index', $debitNote->store_id)
                ->with('success', 'Nota de crédito anulada correctamente.');

        } catch (\Throwable $th) {
            SaleDteLog::error('Error anulando ND', [
                'debit_note_id' => $debitNote->id,
                'sale_id' => $debitNote->sale_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);
            return redirect()->back()->withErrors('Error generando la anulación de la ND: ' . $th->getMessage());
        }
    }
}
