<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\VoidDTE;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\VoidService;

class VoidDTEController extends Controller
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
     * Anulación de Factura Electrónica (FE)
     */
    public function voidDTE(Sale $sale)
    {
        try {
            // Obtener tipo DTE desde la relación
            $tipoDTE = $sale->tipoDte?->codigo;

            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta venta');
            }

            // Crear registro de VoidDTE
            $void = $sale->voids()->create([
                'codigo_generacion' => $sale->codigo_generacion,
                'void_date' => now(),
                'desc' => 'Anulación FE'
            ]);

            // Construir JSON del DTE según tipo
            switch ($tipoDTE) {
                case '01': // Factura Electrónica
                    $nit = $sale->store->taxInfo->nit ?? '00000000000000';
                    $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
                    $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';

                    $dteJson = $this->documentService->buildDTEJsonVoidFE($sale, $void);
                    break;
                case '03': // Comprobante Fiscal
                    $nit = $sale->store->taxInfo->nit ?? '00000000000000';
                    $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
                    $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';

                    $dteJson = $this->documentService->buildDTEJsonVoidCF($sale, $void);
                    break;
                case '14': // Sujeto Excluido
                    $nit = $sale->store->taxInfo->nit ?? '00000000000000';
                    $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
                    $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';
                    $dteJson = $this->documentService->buildDTEJsonVoidSE($sale, $void);
                    break;
                default:
                    throw new \Exception('Tipo de documento no soportado para DTE de anulación');
            }

            Log::info("DTE void antes de firmar ({$tipoDTE})", ['sale_id' => $sale->id, 'dte' => $dteJson]);

            // Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            Log::info("Documento void firmado ({$tipoDTE})", ['sale_id' => $sale->id]);

            // Obtener token Hacienda
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';
            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            // Enviar a Hacienda
            $haciendaResponse = $this->voidService->sendVoidToHacienda($sale, $void, $signedData, $token);
            Log::info("Respuesta Hacienda void ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'void_id' => $void->id,
                'response' => $haciendaResponse
            ]);

            // Guardar info del DTE de anulación en la venta
            $sale->update([
                'dte_codigo' => $sale->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            Log::error('Error anulando DTE', [
                'sale_id' => $sale->id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error anulando DTE',
                'message' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * Anulación de Nota de Crédito (NC)
     */
    public function voidDTECreditNote(CreditNote $creditNote, Sale $sale)
    {
        try {
            // Crear registro de VoidDTE
            $void = $creditNote->voids()->create([
                'codigo_generacion' => $creditNote->codigo_generacion,
                'void_date' => now(),
                'desc' => 'Anulación NC'
            ]);
            $nit = $sale->store->taxInfo->nit ?? '00000000000000';
            $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
            $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';

            $dteJson = $this->documentService->buildDTEJsonNC($creditNote, $sale, $void);
            Log::info("DTE NC antes de firmar", ['credit_note_id' => $creditNote->id]);

            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            Log::info("Documento NC firmado", ['credit_note_id' => $creditNote->id]);
            // Obtener token Hacienda

            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';
            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            $haciendaResponse = $this->voidService->sendNCVoidToHacienda($creditNote, $void, $signedData, $token);
            Log::info("Respuesta Hacienda NC", ['credit_note_id' => $creditNote->id, 'void_id' => $void->id]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            Log::error('Error anulando NC', ['credit_note_id' => $creditNote->id, 'error' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return response()->json(['error' => 'Error anulando NC', 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Anulación de Nota de Débito (ND)
     */
    public function voidDTEDebitNote(DebitNote $debitNote, Sale $sale)
    {
        try {
            // Crear registro de VoidDTE
            $void = $debitNote->voids()->create([
                'codigo_generacion' => $debitNote->codigo_generacion,
                'void_date' => now(),
                'desc' => 'Anulación ND'
            ]);

            $nit = $sale->store->taxInfo->nit ?? '00000000000000';
            $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
            $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';

            $dteJson = $this->documentService->buildDTEJsonND($debitNote, $sale, $void);
            Log::info("DTE ND antes de firmar", ['debit_note_id' => $debitNote->id]);

            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            Log::info("Documento ND firmado", ['debit_note_id' => $debitNote->id]);
            
            // Obtener token Hacienda

            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';

            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            $haciendaResponse = $this->voidService->sendNDVoidToHacienda($debitNote, $void, $signedData, $token);
            Log::info("Respuesta Hacienda ND", ['debit_note_id' => $debitNote->id, 'void_id' => $void->id]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            Log::error('Error anulando ND', ['debit_note_id' => $debitNote->id, 'error' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            return response()->json(['error' => 'Error anulando ND', 'message' => $th->getMessage()], 500);
        }
    }
}
