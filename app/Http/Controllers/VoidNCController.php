<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\SaleDteLog;
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
            SaleDteLog::info("DTE NC antes de firmar", [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $sale->id,
            ]);

            // Firmar documento
            $signedData = $this->documentService->signDocument($dteJson);
            SaleDteLog::info("Documento NC firmado", ['credit_note_id' => $creditNote->id]);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            // Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendNCVoidToHacienda($creditNote, $void, $signedData, $token);
            SaleDteLog::info("Respuesta Hacienda NC", array_merge([
                'credit_note_id' => $creditNote->id,
                'sale_id' => $sale->id,
                'void_id' => $void->id,
            ], SaleDteLog::mhSummary(is_array($haciendaResponse) ? $haciendaResponse : [])));

            // Guardar respuesta de Hacienda en VoidNC
            $void->update([
                'estado' => $haciendaResponse['estado'] ?? 'ERROR',
                'sello_recibido' => $haciendaResponse['selloRecibido'] ?? null,
                'response_json' => $haciendaResponse
            ]);

            return response()->json($haciendaResponse);

        } catch (\Throwable $th) {
            SaleDteLog::error('Error anulando NC', [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $creditNote->sale_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
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
            SaleDteLog::error('Error anulando NC', [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $creditNote->sale_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);
            return redirect()->back()->withErrors('Error generando la anulación de la NC: ' . $th->getMessage());
        }
    }
}
