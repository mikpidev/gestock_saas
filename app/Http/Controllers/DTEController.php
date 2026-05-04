<?php

namespace App\Http\Controllers;

use App\Models\Contingencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\ReceptionService;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Store;
use App\Services\ConsultaService;
use App\Services\ContingenciaService;

class DTEController extends Controller
{
    protected DocumentService $documentService;
    protected HaciendaAuthService $authService;
    protected ReceptionService $receptionService;
    protected ContingenciaService $contingenciaService;

    public function __construct(
        DocumentService $documentService,
        HaciendaAuthService $authService,
        ReceptionService $receptionService,
        ContingenciaService $contingenciaService
    ) {
        $this->documentService = $documentService;
        $this->authService = $authService;
        $this->receptionService = $receptionService;
        $this->contingenciaService = $contingenciaService;
    }

    /**
     * Genera y envía un DTE (Factura Electrónica)
     */
    public function generarDTE(Sale $sale)
    {
        try {

            // Obtener tipo DTE desde la relación
            $tipoDTE = $sale->tipoDte?->codigo;

            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta venta');
            }

            // Construir JSON del DTE según tipo

            switch ($tipoDTE) {

                case '01': // Factura Electronica

                    $nit = $sale->store->taxInfo->nit ?? '00000000000000';
                    $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
                    $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';
                    $dteJson = $this->documentService->buildDTEJsonFE($sale, []);
                    break;
                case '03': // Comprobante Fiscal
                    $nit = $sale->store->taxInfo->nit ?? '00000000000000';
                    $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
                    $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';
                    $dteJson = $this->documentService->buildDTEJsonCF($sale, []);
                    break;
                case '14': // Sujeto Excluido
                    $nit = $sale->store->taxInfo->nit ?? '00000000000000';
                    $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
                    $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';
                    $dteJson = $this->documentService->buildDTEJsonSE($sale, []);
                    break;
                default:
                    throw new \Exception('Tipo de documento no soportado para DTE');
            }

            Log::info("DTE antes de firmar ({$tipoDTE})", $dteJson);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            Log::info("Documento firmado ({$tipoDTE})", $signedData);

            // Obtener token Hacienda
            
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';

            Log::info("Obteniendo token de Hacienda ({$tipoDTE})", [
                'nit' => $nit,
                'environment' => $environment
            ]);

            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            Log::info("Token obtenido de Hacienda ({$tipoDTE})", ['token' => $token]);

            //  Enviar a Hacienda
            $haciendaResponse = $this->receptionService->sendToHacienda($sale, $signedData, $token);
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


    public function generarDTECreditNote(CreditNote $creditNote, Sale $sale)
    {
        try {

            // Obtener tipo DTE desde la relación
            $tipoDTE = "05"; // Código fijo para Nota de Crédito Electrónica

            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta nota de crédito');
            }

            $nit = $creditNote->store->taxInfo->nit ?? '00000000000000';
            $password_pri = $creditNote->store->mh_access->password_pri ?? 'default_password';
            $cert_firma_digital = $creditNote->store->mh_access->port_firma_digital ?? 'default_port';

            $dteJson = $this->documentService->buildDTEJsonNC($creditNote, $sale);
            Log::info("DTE antes de firmar ({$tipoDTE})", $dteJson);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            Log::info("Documento firmado ({$tipoDTE})", $signedData);

            // Obtener token Hacienda
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';
            // Obtener token Hacienda
            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            //  Enviar a Hacienda
            $haciendaResponse = $this->receptionService->sendNCToHacienda($creditNote, $signedData, $token);
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

            $nit = $sale->store->taxInfo->nit ?? '00000000000000';
            $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
            $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            Log::info("Documento firmado ({$tipoDTE})", $signedData);

            // Obtener token Hacienda
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';
            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            //  Enviar a Hacienda
            $haciendaResponse = $this->receptionService->sendNDToHacienda($debitNote, $signedData, $token);
            Log::info("Respuesta Hacienda ({$tipoDTE})", $haciendaResponse);

            //  Guardar info del DTE en la nota de crédito
            $debitNote->update([
                'dte_codigo' => $debitNote->co6digo_generacion,
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


    /**
     * Consulta el estado del DTE en Hacienda para una venta específica
     */
    public function consultarDTE(Sale $sale, ConsultaService $consultaService)
    {
        try {

            // Obtener token de Hacienda
            $token = $this->authService->getToken(); // Usar getToken para reutilizar token válido

            // Llamar al servicio de consulta
            $data = $consultaService->consultarSale($sale, $token);

            // Actualizar info en la venta
            $sale->update([
                'dte_estado' => $data['estado'] ?? 'PENDING'
            ]);

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'estado' => $sale->dte_estado,
                'hacienda_response' => $data
            ]);
        } catch (\Throwable $th) {
            Log::error('Error consultando DTE: ' . $th->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * Consulta el estado del DTE en Hacienda para una NC específica
     */
    public function consultarDTENC(CreditNote $creditNote, ConsultaService $consultaService)
    {
        try {
            // Obtener token de Hacienda
            $token = $this->authService->getToken(); // Usar getToken para reutilizar token válido

            // Llamar al servicio de consulta
            $data = $consultaService->consultarNC($creditNote, $token);

            // Actualizar info en la venta
            $creditNote->update([
                'dte_estado' => $data['estado'] ?? 'PENDING'
            ]);

            return response()->json([
                'success' => true,
                'sale_id' => $creditNote->id,
                'estado' => $creditNote->dte_estado,
                'hacienda_response' => $data
            ]);
        } catch (\Throwable $th) {
            Log::error('Error consultando DTE: ' . $th->getMessage(), [
                'sale_id' => $creditNote->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    /**
     * Consulta el estado del DTE en Hacienda para una NC específica
     */
    public function consultarDTEND(DebitNote $debitNote, ConsultaService $consultaService)
    {
        try {
            // Obtener token de Hacienda
            $token = $this->authService->getToken(); // Usar getToken para reutilizar token válido

            // Llamar al servicio de consulta
            $data = $consultaService->consultarND($debitNote, $token);

            // Actualizar info en la venta
            $debitNote->update([
                'dte_estado' => $data['estado'] ?? 'PENDING'
            ]);

            return response()->json([
                'success' => true,
                'sale_id' => $debitNote->id,
                'estado' => $debitNote->dte_estado,
                'hacienda_response' => $data
            ]);
        } catch (\Throwable $th) {
            Log::error('Error consultando DTE: ' . $th->getMessage(), [
                'sale_id' => $debitNote->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function generarDTEContingencia(Contingencia $contingencia)
    {
        try {
            // Construir JSON de contingencia
            $dteJson = $this->documentService->buildDTEJsonContingencia($contingencia);

            Log::info('DTE Contingencia antes de firmar', $dteJson);

            // Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $contingencia->store->taxInfo->nit, $contingencia->store->mh_access->password_pri, $contingencia->store->mh_access->port_firma_digital);

            Log::info('DTE Contingencia firmado', $signedData);

            // Token
            $token = $this->authService->generateNewToken( $contingencia->store->taxInfo->nit, $contingencia->store->mh_access->api_key, $contingencia->store->environment);

            // Enviar a Hacienda
            $haciendaResponse = $this->contingenciaService
                ->sendContingencia($contingencia, $signedData, $token);

            // Guardar estado
            $contingencia->update([
                'estado' => 'ENVIADA'
            ]);

            return response()->json([
                'success' => true,
                'contingencia_id' => $contingencia->id,
                'hacienda_response' => $haciendaResponse
            ]);
        } catch (\Throwable $th) {
            Log::error('Error generando contingencia DTE: ' . $th->getMessage(), [
                'contingencia_id' => $contingencia->id,
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
