<?php

namespace App\Http\Controllers;

use App\Models\Contingencia;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Support\SaleDteLog;
use App\Services\DocumentService;
use App\Services\HaciendaAuthService;
use App\Services\ReceptionService;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Store;
use App\Services\ConsultaService;
use App\Services\ContingenciaService;
use App\Services\DteQuotaService;
use Illuminate\Http\JsonResponse;

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
        if ($denied = $this->quotaExceededResponse($sale->store)) {
            return $denied;
        }

        $startedNs = hrtime(true);
        $statusBefore = $sale->dte_status;
        $dteType = $sale->tipoDte?->codigo;
        $mh = [];
        $statusAfter = $statusBefore;

        try {

            // Obtener tipo DTE desde la relación
            $tipoDTE = $sale->tipoDte?->codigo;
            $dteType = $tipoDTE;

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

            SaleDteLog::info("DTE antes de firmar ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'tipo_dte' => $tipoDTE,
            ]);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            SaleDteLog::info("Documento firmado ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'has_body' => isset($signedData['body']) && is_string($signedData['body']) && $signedData['body'] !== '',
            ]);

            // Obtener token Hacienda
            
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';

            SaleDteLog::info("Obteniendo token de Hacienda ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'environment' => $environment,
            ]);

            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            SaleDteLog::info("Token obtenido de Hacienda ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'token_preview' => SaleDteLog::tokenPreview($token),
            ]);

            //  Enviar a Hacienda
            $haciendaResponse = $this->receptionService->sendToHacienda($sale, $signedData, $token);
            SaleDteLog::info("Respuesta Hacienda ({$tipoDTE})", array_merge(
                ['sale_id' => $sale->id],
                SaleDteLog::mhSummary(is_array($haciendaResponse) ? $haciendaResponse : [])
            ));
            $mh = is_array($haciendaResponse) ? $haciendaResponse : [];
            if (is_string($haciendaResponse['estado'] ?? null)) {
                $statusAfter = $haciendaResponse['estado'];
            }

            //  Guardar info del DTE en la venta
            $sale->update([
                'dte_codigo' => $sale->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            if ($mh === []) {
                $mh = ['mensaje' => $th->getMessage()];
            }
            SaleDteLog::error('Error generando DTE', [
                'sale_id' => $sale->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        } finally {
            $this->logDteAttempt($sale->id, $sale->store_id, auth()->id(), $dteType, $statusBefore, $statusAfter, $mh, $startedNs);
        }
    }


    public function generarDTECreditNote(CreditNote $creditNote, Sale $sale)
    {
        if ($denied = $this->quotaExceededResponse($creditNote->store)) {
            return $denied;
        }

        $startedNs = hrtime(true);
        $statusBefore = $creditNote->dte_status;
        $dteType = '05';
        $mh = [];
        $statusAfter = $statusBefore;

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
            SaleDteLog::info("DTE antes de firmar ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'tipo_dte' => $tipoDTE,
            ]);

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            SaleDteLog::info("Documento firmado ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'has_body' => isset($signedData['body']) && is_string($signedData['body']) && $signedData['body'] !== '',
            ]);

            // Obtener token Hacienda
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';
            // Obtener token Hacienda
            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            //  Enviar a Hacienda
            $haciendaResponse = $this->receptionService->sendNCToHacienda($creditNote, $signedData, $token);
            SaleDteLog::info("Respuesta Hacienda ({$tipoDTE})", array_merge(
                ['sale_id' => $sale->id],
                SaleDteLog::mhSummary(is_array($haciendaResponse) ? $haciendaResponse : [])
            ));
            $mh = is_array($haciendaResponse) ? $haciendaResponse : [];
            if (is_string($haciendaResponse['estado'] ?? null)) {
                $statusAfter = $haciendaResponse['estado'];
            }

            //  Guardar info del DTE en la nota de crédito
            $creditNote->update([
                'dte_codigo' => $creditNote->codigo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            if ($mh === []) {
                $mh = ['mensaje' => $th->getMessage()];
            }
            SaleDteLog::error('Error generando DTE', [
                'sale_id' => $sale->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        } finally {
            $this->logDteAttempt($sale->id, $creditNote->store_id, auth()->id(), $dteType, $statusBefore, $statusAfter, $mh, $startedNs);
        }
    }

    public function generarDTEDebitNote(DebitNote $debitNote, Sale $sale)
    {
        if ($denied = $this->quotaExceededResponse($debitNote->store)) {
            return $denied;
        }

        $startedNs = hrtime(true);
        $statusBefore = $debitNote->dte_status;
        $dteType = '06';
        $mh = [];
        $statusAfter = $statusBefore;

        try {

            // Obtener tipo DTE desde la relación
            $tipoDTE = "06"; // Código fijo para Nota de Crédito Electrónica

            if (!$tipoDTE) {
                throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta nota de crédito');
            }

            $dteJson = $this->documentService->buildDTEJsonND($debitNote, $sale);
            SaleDteLog::info("DTE antes de firmar ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'tipo_dte' => $tipoDTE,
            ]);

            $nit = $sale->store->taxInfo->nit ?? '00000000000000';
            $password_pri = $sale->store->mh_access->password_pri ?? 'default_password';
            $cert_firma_digital = $sale->store->mh_access->port_firma_digital ?? 'default_port';

            //  Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $nit, $password_pri, $cert_firma_digital);
            SaleDteLog::info("Documento firmado ({$tipoDTE})", [
                'sale_id' => $sale->id,
                'has_body' => isset($signedData['body']) && is_string($signedData['body']) && $signedData['body'] !== '',
            ]);

            // Obtener token Hacienda
            $api_key = $sale->store->mh_access->api_key ?? 'default_api_key';
            $environment = $sale->store->environment ?? 'default_environment';
            $token = $this->authService->generateNewToken($nit, $api_key, $environment);

            //  Enviar a Hacienda
            $haciendaResponse = $this->receptionService->sendNDToHacienda($debitNote, $signedData, $token);
            SaleDteLog::info("Respuesta Hacienda ({$tipoDTE})", array_merge(
                ['sale_id' => $sale->id],
                SaleDteLog::mhSummary(is_array($haciendaResponse) ? $haciendaResponse : [])
            ));
            $mh = is_array($haciendaResponse) ? $haciendaResponse : [];
            if (is_string($haciendaResponse['estado'] ?? null)) {
                $statusAfter = $haciendaResponse['estado'];
            }

            //  Guardar info del DTE en la nota de crédito
            $debitNote->update([
                'dte_codigo' => $debitNote->co6digo_generacion,
                'dte_estado' => $haciendaResponse['estado'] ?? 'PENDING'
            ]);

            return response()->json($haciendaResponse);
        } catch (\Throwable $th) {
            if ($mh === []) {
                $mh = ['mensaje' => $th->getMessage()];
            }
            SaleDteLog::error('Error generando DTE', [
                'sale_id' => $sale->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return response()->json([
                'error' => 'Error generando DTE',
                'message' => $th->getMessage()
            ], 500);
        } finally {
            $this->logDteAttempt($sale->id, $debitNote->store_id, auth()->id(), $dteType, $statusBefore, $statusAfter, $mh, $startedNs);
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
            SaleDteLog::error('Error consultando DTE', [
                'sale_id' => $sale->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
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
            SaleDteLog::error('Error consultando DTE', [
                'sale_id' => $creditNote->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
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
            SaleDteLog::error('Error consultando DTE', [
                'sale_id' => $debitNote->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
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

            SaleDteLog::info('DTE Contingencia antes de firmar', [
                'contingencia_id' => $contingencia->id,
            ]);

            // Firmar documento
            $signedData = $this->documentService->signDocument($dteJson, $contingencia->store->taxInfo->nit, $contingencia->store->mh_access->password_pri, $contingencia->store->mh_access->port_firma_digital);

            SaleDteLog::info('DTE Contingencia firmado', [
                'contingencia_id' => $contingencia->id,
                'has_body' => isset($signedData['body']) && is_string($signedData['body']) && $signedData['body'] !== '',
            ]);

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
            SaleDteLog::error('Error generando contingencia DTE', [
                'contingencia_id' => $contingencia->id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Soft quota block. Returned before signing so the generic catch does not turn it into HTTP 500.
     */
    private function quotaExceededResponse(?Store $store): ?JsonResponse
    {
        if ($store === null) {
            return null;
        }

        $denial = app(DteQuotaService::class)->denial($store);

        return $denial === null ? null : response()->json($denial, 422);
    }

    private function logDteAttempt(
        int $saleId,
        ?int $storeId,
        mixed $userId,
        ?string $dteType,
        mixed $statusBefore,
        mixed $statusAfter,
        array $mh,
        int $startedNs,
    ): void {
        $code = $mh['codigoMsg'] ?? $mh['codigo_msg'] ?? null;
        if (!is_int($code) && !(is_string($code) && $code !== '' && !str_contains($code, '{') && !str_contains($code, '<'))) {
            $code = null;
        } elseif (is_string($code)) {
            $code = mb_substr($code, 0, 32);
        }

        $message = $mh['descripcionMsg'] ?? $mh['mensaje'] ?? $mh['descripcion_msg'] ?? null;
        if (!is_string($message) || $message === '' || str_contains($message, '<') || str_contains($message, '{')) {
            $message = null;
        } else {
            $message = mb_substr($message, 0, 160);
        }

        SaleDteLog::info('gestock.dte', [
            'sale_id' => $saleId,
            'store_id' => $storeId,
            'user_id' => is_int($userId) ? $userId : (is_string($userId) && ctype_digit($userId) ? (int) $userId : null),
            'dte_type' => $dteType,
            'dte_status_before' => is_string($statusBefore) ? $statusBefore : null,
            'dte_status_after' => is_string($statusAfter) ? $statusAfter : null,
            'mh_code' => $code,
            'mh_message' => $message,
            'duration_ms' => (int) round((hrtime(true) - $startedNs) / 1_000_000),
        ]);
    }
}
