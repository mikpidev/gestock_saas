<?php

namespace App\Services;

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
}
