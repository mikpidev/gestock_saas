<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CreditNote;
use App\Models\VoidNC;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\VoidService;

class VoidNCController extends Controller
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
     * Generar anulación de NC
     */
    public function voidNC(CreditNote $creditNote)
    {
        try {
            $sale = $creditNote->sale;
            if (!$sale) {
                throw new \Exception('No se encontró la venta asociada a la NC');
            }

            // Crear registro de anulación
            $void = $creditNote->voids()->create([
                'codigo_generacion' => $creditNote->codigo_generacion,
                'void_date' => now(),
                'desc' => 'Anulación NC'
            ]);

            // Construir JSON de la NC a anular
            $dteJson = $this->documentService->buildDTEJsonVoidNC($creditNote, $sale, $void);
            Log::info("DTE NC antes de firmar", ['credit_note_id' => $creditNote->id, 'dte' => $dteJson]);

            // Firmar documento
            $signedData = $this->documentService->signDocument($dteJson);
            Log::info("Documento NC firmado", ['credit_note_id' => $creditNote->id]);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            // Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendNCVoidToHacienda($creditNote, $void, $signedData, $token);
            Log::info("Respuesta Hacienda NC", [
                'credit_note_id' => $creditNote->id,
                'void_id' => $void->id,
                'response' => $haciendaResponse
            ]);

            // Guardar respuesta de Hacienda en VoidNC
            $void->update([
                'estado' => $haciendaResponse['estado'] ?? 'ERROR',
                'sello_recibido' => $haciendaResponse['selloRecibido'] ?? null,
                'response_json' => $haciendaResponse
            ]);

            return response()->json($haciendaResponse);

        } catch (\Throwable $th) {
            Log::error('Error anulando NC', [
                'credit_note_id' => $creditNote->id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error anulando NC',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Destroy con validación de Hacienda
     */
    public function destroy(CreditNote $creditNote)
    {
        try {
            $response = $this->voidNC($creditNote);

            if (($response->getData()->estado ?? '') !== 'PROCESADO') {
                return redirect()->back()->withErrors('Hacienda no confirmó la anulación de la NC.');
            }

            $creditNote->delete();

            return redirect()->route('stores.sales.index', $creditNote->store_id)
                ->with('success', 'Nota de crédito anulada correctamente.');

        } catch (\Throwable $th) {
            Log::error('Error anulando NC', [
                'credit_note_id' => $creditNote->id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return redirect()->back()->withErrors('Error generando la anulación de la NC: ' . $th->getMessage());
        }
    }
}
