<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Sale;
use App\Models\DteResponse;
use App\Models\DteResponseNC;
use App\Models\DteResponseND;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use League\CommonMark\Environment\Environment;

class ConsultaService
{
    /**
     * Consulta el estado de un DTE de venta en Hacienda.
     */
    public function consultarSale(Sale $sale, $token): array
    {
        if (!$sale->codigo_generacion) {
            Log::warning("Sale sin código de generación, no se puede consultar DTE", [
                'sale_id' => $sale->id
            ]);
            return ['estado' => 'SIN_CODIGO', 'mensaje' => 'No hay código de generación'];
        }

        $tipoDTE = $sale->tipoDte?->codigo;


        if (!$tipoDTE) {
            throw new \Exception('Tipo de DTE no seleccionado o no encontrado para esta venta');
        }

        $environment = $sale->store->environment ?? 'default_environment';

        if ($environment === 'Development') {
            $url = config('services.hacienda.test_url') . 'recepcion/consultadte/';
        } elseif ($environment === 'Production') {
            $url = config('services.hacienda.prod_url') . 'recepcion/consultadte/';
        } else {
            Log::error("Ambiente desconocido para la venta {$sale->id}: {$environment}");
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Ambiente desconocido'
            ];
        }

        //Logs before the request
/*         Log::info("Consultando DTE en Hacienda", [
            'sale_id' => $sale->id,
            'nitEmisor' => $sale->store->taxInfo->nit,
            'tdte' => $tipoDTE,
            'codigoGeneracion' => $sale->codigo_generacion,
            'url' => $url
        ]); */
        
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post($url, [
                    'nitEmisor' =>  $sale->store->taxInfo->nit,
                    'tdte' => $tipoDTE,
                    'codigoGeneracion' => $sale->codigo_generacion
                ]);


            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $data = $response->json();

            // Guardar DTE Response
            try {
                DteResponse::create([
                    'sale_id' => $sale->id,
                    'version' => $data['version'] ?? null,
                    'ambiente' => $data['ambiente'] ?? null,
                    'versionApp' => $data['versionApp'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'codigo_generacion' => $data['codigoGeneracion'] ?? null,
                    'sello_recibido' => $data['selloRecibido'] ?? null,
                    'fh_procesamiento' => isset($data['fhProcesamiento'])
                        ? Carbon::createFromFormat('d/m/Y H:i:s', $data['fhProcesamiento'])
                        : null,
                    'clasifica_msg' => $data['clasificaMsg'] ?? null,
                    'codigo_msg' => $data['codigoMsg'] ?? null,
                    'descripcion_msg' => $data['descripcionMsg'] ?? null,
                    'observaciones' => $data['observaciones'] ?? [],
                ]);
            } catch (\Throwable $e) {
                Log::error("Error guardando DteResponse", ['sale_id' => $sale->id, 'error' => $e->getMessage()]);
            }

            // Actualizar estado de la venta
            $sale->dte_status = $data['estado'] ?? 'PENDIENTE';
            $sale->save();

            return $data;
        } catch (\Throwable $e) {
            Log::error("Error consultando DTE en Hacienda", [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Consulta el estado de un DTE de una NC en Hacienda.
     */
    public function consultarNC(CreditNote $creditNote, $token): array
    {
        if (!$creditNote->codigo_generacion) {
            Log::warning("creditNote sin código de generación, no se puede consultar DTE", [
                'credit_note_id' => $creditNote->id
            ]);
            return ['estado' => 'SIN_CODIGO', 'mensaje' => 'No hay código de generación'];
        }

        // 04 es el tipo de DTE para Nota de Crédito
        $tipoDTE = "04"; // Ajustar según tipo de venta
        $environment = $creditNote->store->environment ?? 'default_environment';

        if ($environment === 'Development') {
            $url = config('services.hacienda.test_url') . 'recepcion/consultadte';
        } elseif ($environment === 'Production') {
            $url = config('services.hacienda.prod_url') . 'recepcion/consultadte';
        } else {
            Log::error("Ambiente desconocido para la NC {$creditNote->id}: {$environment}");
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Ambiente desconocido'
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post($url, [
                    'nitEmisor' => $creditNote->store->taxInfo->nit,
                    'tdte' => $tipoDTE,
                    'codigoGeneracion' => $creditNote->codigo_generacion
                ]);


            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $data = $response->json();

            // Guardar DTE Response
            try {
                DteResponseNC::create([
                    'credit_note_id' => $creditNote->id,
                    'version' => $data['version'] ?? null,
                    'ambiente' => $data['ambiente'] ?? null,
                    'versionApp' => $data['versionApp'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'codigo_generacion' => $data['codigoGeneracion'] ?? null,
                    'sello_recibido' => $data['selloRecibido'] ?? null,
                    'fh_procesamiento' => isset($data['fhProcesamiento'])
                        ? Carbon::createFromFormat('d/m/Y H:i:s', $data['fhProcesamiento'])
                        : null,
                    'clasifica_msg' => $data['clasificaMsg'] ?? null,
                    'codigo_msg' => $data['codigoMsg'] ?? null,
                    'descripcion_msg' => $data['descripcionMsg'] ?? null,
                    'observaciones' => $data['observaciones'] ?? [],
                ]);
            } catch (\Throwable $e) {
                Log::error("Error guardando DteResponse", ['credit_note_id' => $creditNote->id, 'error' => $e->getMessage()]);
            }

            // Actualizar estado de la venta
            $creditNote->dte_status = $data['estado'] ?? 'PENDIENTE';
            $creditNote->save();

            return $data;
        } catch (\Throwable $e) {
            Log::error("Error consultando DTE en Hacienda", [
                'sale_id' => $creditNote->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }
    }

    public function consultarND(DebitNote $debitNote, $token): array
    {
        if (!$debitNote->codigo_generacion) {
            Log::warning("Debit Note sin código de generación, no se puede consultar DTE", [
                'debit_note_id' => $debitNote->id
            ]);
            return ['estado' => 'SIN_CODIGO', 'mensaje' => 'No hay código de generación'];
        }

        $tipoDTE = "06"; // Ajustar según tipo de venta

        $environment = $debitNote->store->environment ?? 'default_environment';

        if ($environment === 'Development') {
            $url = config('services.hacienda.test_url') . 'recepcion/consultadte';
        } elseif ($environment === 'Production') {
            $url = config('services.hacienda.prod_url') . 'recepcion/consultadte';
        } else {
            Log::error("Ambiente desconocido para la ND {$debitNote->id}: {$environment}");
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Ambiente desconocido'
            ];
        }


        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post($url, [
                    'nitEmisor' => $debitNote->store->taxInfo->nit,
                    'tdte' => $tipoDTE,
                    'codigoGeneracion' => $debitNote->codigo_generacion
                ]);


            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $data = $response->json();

            // Guardar DTE Response
            try {
                DteResponseND::create([
                    'debit_note_id' => $debitNote->id,
                    'version' => $data['version'] ?? null,
                    'ambiente' => $data['ambiente'] ?? null,
                    'versionApp' => $data['versionApp'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'codigo_generacion' => $data['codigoGeneracion'] ?? null,
                    'sello_recibido' => $data['selloRecibido'] ?? null,
                    'fh_procesamiento' => isset($data['fhProcesamiento'])
                        ? Carbon::createFromFormat('d/m/Y H:i:s', $data['fhProcesamiento'])
                        : null,
                    'clasifica_msg' => $data['clasificaMsg'] ?? null,
                    'codigo_msg' => $data['codigoMsg'] ?? null,
                    'descripcion_msg' => $data['descripcionMsg'] ?? null,
                    'observaciones' => $data['observaciones'] ?? [],
                ]);
            } catch (\Throwable $e) {
                Log::error("Error guardando DteResponse", ['debit_note_id' => $debitNote->id, 'error' => $e->getMessage()]);
            }

            // Actualizar estado de la venta
            $debitNote->dte_status = $data['estado'] ?? 'PENDIENTE';
            $debitNote->save();

            return $data;
        } catch (\Throwable $e) {
            Log::error("Error consultando DTE en Hacienda", [
                'sale_id' => $debitNote->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['estado' => 'ERROR', 'mensaje' => $e->getMessage()];
        }
    }
}
