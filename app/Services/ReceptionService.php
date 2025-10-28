<?php

namespace App\Services;

use App\Models\DteResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;

class ReceptionService
{
    /**
     * Envía un documento firmado a Hacienda
     *
     * @param Sale $sale
     * @param array $signedData
     * @param string $token
     * @return array
     */

    public function sendToHacienda(Sale $sale, array $signedData, string $token): array
    {
        // Obtener código de DTE directamente de la relación
        $tipoDTE = $sale->tipoDte?->codigo;

        if (!$tipoDTE) {
            Log::error("Tipo de DTE inválido para la venta {$sale->id}");
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Tipo de DTE inválido o no encontrado'
            ];
        }

        // Mapear código de DTE a versión
        $versionDTE = [
            '01' => 1, // Factura Electrónica
            '03' => 3, // Comprobante Fiscal (CCF)
            '14' => 1  // Sujeto Excluido
        ];

        $version = $versionDTE[$tipoDTE];

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', [
                    'ambiente' => '00',
                    'idEnvio' => 1,
                    'version' => $version,
                    'tipoDte' => $tipoDTE,
                    'codigoGeneracion' => $sale->codigo_generacion,
                    'documento' => $signedData['body'] ?? null
                ]);

            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $data = $response->json();

            try {
                DteResponse::create([
                    'sale_id' => $sale->id,
                    'version' => $data['version'],
                    'ambiente' => $data['ambiente'],
                    'versionApp' => $data['versionApp'],
                    'estado' => $data['estado'],
                    'codigo_generacion' => $data['codigoGeneracion'],
                    'sello_recibido' => $data['selloRecibido'],
                    'fh_procesamiento' => \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data['fhProcesamiento']),
                    'clasifica_msg' => $data['clasificaMsg'],
                    'codigo_msg' => $data['codigoMsg'],
                    'descripcion_msg' => $data['descripcionMsg'],
                    'observaciones' => $data['observaciones'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error('Error guardando DteResponse: ' . $e->getMessage());
            }



            return $response->json();
        } catch (\Throwable $th) {
            Log::error("Error enviando DTE a Hacienda: " . $th->getMessage(), [
                'sale_id' => $sale->id,
                'tipo_documento' => $sale->tipo_documento_id,
                'trace' => $th->getTraceAsString()
            ]);
            return [
                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];
        }
    }

    public function sendNCToHacienda($creditNote, $signedData, $token): array
    {
        $tipoDTE = '05'; // Nota de Crédito Electrónica
        $version = 3;
    
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', [
                    'ambiente' => '00',
                    'idEnvio' => 1,
                    'version' => $version,
                    'tipoDte' => $tipoDTE,
                    'codigoGeneracion' => $creditNote->codigo_generacion,
                    'documento' => $signedData['body'] ?? null
                ]);
    
            $data = $response->json();
    
            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
    
            // Guardar en dte_responses_nc
            try {
                \App\Models\DteResponseNC::create([
                    'credit_note_id' => $creditNote->id,
                    'version' => $data['version'] ?? null,
                    'ambiente' => $data['ambiente'] ?? null,
                    'versionApp' => $data['versionApp'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'codigo_generacion' => $data['codigoGeneracion'] ?? null,
                    'sello_recibido' => $data['selloRecibido'] ?? null,
                    'fh_procesamiento' => isset($data['fhProcesamiento'])
                        ? \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $data['fhProcesamiento'])
                        : null,
                    'clasifica_msg' => $data['clasificaMsg'] ?? null,
                    'codigo_msg' => $data['codigoMsg'] ?? null,
                    'descripcion_msg' => $data['descripcionMsg'] ?? null,
                    'observaciones' => $data['observaciones'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error('Error guardando DteResponseNC al crear NC: ' . $e->getMessage());
            }
    
            return $data;
    
        } catch (\Throwable $th) {
            Log::error("Error enviando NC a Hacienda: " . $th->getMessage(), [
                'credit_note_id' => $creditNote->id,
                'tipo_documento' => $creditNote->tipo_documento_id,
                'trace' => $th->getTraceAsString()
            ]);
    
            return [
                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];
        }
    }
    

    public function sendNDToHacienda($debitNote, $signedData, $token)
    {
        // Obtener código de DTE directamente de la relación
        $tipoDTE = '06'; // Código fijo para Nota de Crédito Electrónica

        $versionDTE = 3;

        $version = $versionDTE;

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post('https://apitest.dtes.mh.gob.sv/fesv/recepciondte', [
                    'ambiente' => '00',
                    'idEnvio' => 1,
                    'version' => $version,
                    'tipoDte' => $tipoDTE,
                    'codigoGeneracion' => $debitNote->codigo_generacion,
                    'documento' => $signedData['body'] ?? null
                ]);

            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $response->json();
        } catch (\Throwable $th) {
            Log::error("Error enviando DTE a Hacienda: " . $th->getMessage(), [
                'debit_note_id' => $debitNote->id,
                'tipo_documento' => $debitNote->tipo_documento_id,
                'trace' => $th->getTraceAsString()
            ]);
            return [
                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];
        }
    }
}
