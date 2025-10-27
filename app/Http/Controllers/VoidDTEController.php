<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Services\VoidService;

class VoidDTEController extends Controller
{
    //

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

    public function voidDTE(Sale $sale)
    {

        try{

            $tipoDTE = $sale->tipoDTE?->codigo;

            
            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta venta');
            }

            // Construir JSON del DTE según tipo

            switch ($tipoDTE) {

                case '01': // Factura Electronica
                    $dteJson = $this->documentService->buildDTEJsonVoidFE($sale);
                    break;
                case '03': // Comprobante Fiscal
                    $dteJson = $this->documentService->buildDTEJsonVoidCF($sale, []);
                    break;
                case '14': // Sujeto Excluido
                    $dteJson = $this->documentService->buildDTEJsonVoidSE($sale, []);
                    break;
                default:
                    throw new \Exception('Tipo de documento no soportado para DTE');
            }

            Log::info("DTE antes de firmar ({$tipoDTE})", $dteJson);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson);
            Log::info("Documento firmado ({$tipoDTE})", $signedData);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            //  Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendVoidToHacienda($sale, $signedData, $token);
            Log::info("Respuesta Hacienda ({$tipoDTE})", $haciendaResponse);

            //  Guardar info del DTE en la venta
            $sale->update([
                'dte_codigo' => $sale->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            Log::error('Error generando DTE: ' . $th->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        }

    }

    public function voidDTECreditNote(CreditNote $creditNote, Sale $sale)
    {
        try {

            // Obtener tipo DTE desde la relación
            $tipoDTE = "05"; // Código fijo para Nota de Crédito Electrónica

            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta nota de crédito');
            }

            $dteJson = $this->documentService->buildDTEJsonNC($creditNote, $sale);
            Log::info("DTE antes de firmar ({$tipoDTE})", $dteJson);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson);
            Log::info("Documento firmado ({$tipoDTE})", $signedData);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            //  Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendNCVoidToHacienda($creditNote, $signedData, $token);
            Log::info("Respuesta Hacienda ({$tipoDTE})", $haciendaResponse);

            //  Guardar info del DTE en la nota de crédito
            $creditNote->update([
                'dte_codigo' => $creditNote->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            Log::error('Error generando DTE: ' . $th->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function generarDTEDebitNote(DebitNote $debitNote, Sale $sale)
    {
        try {

            // Obtener tipo DTE desde la relación
            $tipoDTE = "06"; // Código fijo para Nota de Crédito Electrónica

            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta nota de crédito');
            }

            $dteJson = $this->documentService->buildDTEJsonND($debitNote, $sale);
            Log::info("DTE antes de firmar ({$tipoDTE})", $dteJson);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson);
            Log::info("Documento firmado ({$tipoDTE})", $signedData);

            // Obtener token Hacienda
            $token = $this->authService->generateNewToken();

            //  Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendNDVoidToHacienda($debitNote, $signedData, $token);
            Log::info("Respuesta Hacienda ({$tipoDTE})", $haciendaResponse);

            //  Guardar info del DTE en la nota de crédito
            $debitNote->update([
                'dte_codigo' => $debitNote->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            Log::error('Error generando DTE: ' . $th->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
