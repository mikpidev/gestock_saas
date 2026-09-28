<?php


namespace App\Services;

use App\Models\DteResponse;
use Illuminate\Support\Facades\Http;
use App\Support\SaleDteLog;
use App\Models\Sale;
use App\Models\VoidDTE;
use Carbon\Carbon;

class VoidService
{



    /**
     * Envía un documento firmado a Hacienda
     *
     * @param Sale $sale
     * @param array $signedData
     * @param string $token
     * @return array
     */


    public function sendVoidToHacienda(Sale $sale, $void, array $signedData, string $token): array
    {
        $tipoDTE = $sale->tipoDte?->codigo;

        if (!$tipoDTE) {
            SaleDteLog::error("Tipo de DTE inválido para la venta {$sale->id}");
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Tipo de DTE inválido o no encontrado'
            ];
        }

        // Mapear código de DTE a versión para anulaciones
        $versionDTE = [
            '01' => 1, // FE
            '03' => 3, // CCF
            '14' => 1  // Sujeto Excluido
        ];

        $version = $versionDTE[$tipoDTE] ?? 1;

        $environment = $sale->store->environment ?? 'default_environment';

        if ($environment === 'Production') {
            $url = config('services.hacienda.prod_url') . 'anulardte';
            $ambiente = '01';
        } elseif ($environment === 'Development') {
            $url = config('services.hacienda.test_url') . 'anulardte';
            $ambiente = '00';
        } else {
            SaleDteLog::error("Ambiente desconocido para la venta {$sale->id}: {$environment}");
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Ambiente desconocido'
            ];
        }

        //Logs before the request
        SaleDteLog::info("Enviando DTE void a Hacienda", [
            'sale_id' => $sale->id,
            'tdte' => $tipoDTE,
            'codigoGeneracion' => $sale->codigo_generacion,
        ]);
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
                ->post($url, [
                    'ambiente' => $ambiente,
                    'idEnvio' => 1,
                    'version' => 3,
                    'documento' => $signedData['body'] ?? null
                ]);

            SaleDteLog::info("Hacienda Void Response ({$tipoDTE})", SaleDteLog::httpMh($response, [
                'sale_id' => $sale->id,
            ]));

            $data = $response->json();

            // Guardar en DteResponse
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
            } catch (\Exception $e) {
                SaleDteLog::error('Error guardando DteResponse de anulación', [
                    'sale_id' => $sale->id,
                    'message' => SaleDteLog::safeMessage($e->getMessage()),
                ]);
            }

            return $data;
        } catch (\Throwable $th) {
            SaleDteLog::error('Error enviando anulación a Hacienda', [
                'sale_id' => $sale->id,
                'tipo_documento' => $sale->tipo_documento_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return [
                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];
        }
    }

    public function sendNCVoidToHacienda($creditNote, $void, $signedData, $token): array
    {
        $tipoDTE = "05"; // Nota de Crédito Electrónica

        $environment = $creditNote->sale->store->environment ?? 'default_environment';

        if ($environment === 'Production') {
            $ambiente = '01';
            $url = config('services.hacienda.prod_url') . 'anulardte';
        } elseif ($environment === 'Development') {
            $ambiente = '00';
            $url = config('services.hacienda.test_url') . 'anulardte';
        } else {
            SaleDteLog::error("Ambiente desconocido para la NC {$creditNote->id}: {$environment}");
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
                    'ambiente' => $ambiente,
                    'idEnvio' => 1,
                    'version' => 2,
                    'documento' => $signedData['body'] ?? null
                ]);

            $data = $response->json();

            SaleDteLog::info("Hacienda Response ({$tipoDTE})", SaleDteLog::httpMh($response, [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $creditNote->sale_id ?? null,
            ]));

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
                SaleDteLog::error('Error guardando DteResponseNC', [
                    'credit_note_id' => $creditNote->id,
                    'message' => SaleDteLog::safeMessage($e->getMessage()),
                ]);
            }

            return $data;
        } catch (\Throwable $th) {
            SaleDteLog::error('Error enviando NC a Hacienda', [
                'credit_note_id' => $creditNote->id,
                'sale_id' => $creditNote->sale_id ?? null,
                'tipo_documento' => $creditNote->tipo_documento_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return [
                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];
        }
    }


    public function sendNDVoidToHacienda($debitNote, $void, $signedData, $token): array
    { // Obtener código de DTE directamente de la relación
        $tipoDTE = "06"; // Nota de Crédito Electrónica
        $environment = $debitNote->sale->store->environment ?? 'default_environment';
        if ($environment === 'Production') {
            $ambiente = '01';
            $url = config('services.hacienda.prod_url') . 'anulardte';
        } elseif ($environment === 'Development') {
            $ambiente = '00';
            $url = config('services.hacienda.test_url') . 'anulardte';
        } else {
            SaleDteLog::error("Ambiente desconocido para la ND {$debitNote->id}: {$environment}");
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
                    'ambiente' => $ambiente,
                    'idEnvio' => 1,
                    'version' => 2,
                    'documento' => $signedData['body'] ?? null
                ]);

            $data = $response->json();

            SaleDteLog::info("Hacienda Response ({$tipoDTE})", SaleDteLog::httpMh($response, [
                'debit_note_id' => $debitNote->id,
                'sale_id' => $debitNote->sale_id ?? null,
            ]));

            // Guardar en dte_responses_nc
            try {
                \App\Models\DteResponseND::create([
                    'debit_note_id' => $debitNote->id,
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
                SaleDteLog::error('Error guardando DteResponseND', [
                    'debit_note_id' => $debitNote->id,
                    'message' => SaleDteLog::safeMessage($e->getMessage()),
                ]);
            }

            return $data;
        } catch (\Throwable $th) {
            SaleDteLog::error('Error enviando ND a Hacienda', [
                'debit_note_id' => $debitNote->id,
                'sale_id' => $debitNote->sale_id ?? null,
                'tipo_documento' => $debitNote->tipo_documento_id,
                'message' => SaleDteLog::safeMessage($th->getMessage()),
            ]);

            return [
                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];
        }
    }
}
