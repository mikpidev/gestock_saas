<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;

class VoidService {



    /**
     * Envía un documento firmado a Hacienda
     *
     * @param Sale $sale
     * @param array $signedData
     * @param string $token
     * @return array
     */


    public function sendVoidToHacienda (Sale $sale, array $signedData, string $token): array 
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

        try {

            $response = Http::withHeaders([

                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
            ->post('https://apitest.dtes.mh.gob.sv/fesv/anulardte', [

                'ambiente' => '00',
                'idEnvio' => 1,
                'version' => 2,
                'tipoDte' => $tipoDTE,
                'documento' => $signedData['body'] ?? null

            ]);

            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response -> body() 
                
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

    public function sendNCVoidToHacienda ($creditNote, $signedData, $token): array 
    {

        // Obtener código de DTE directamente de la relación
        $tipoDTE = "05";
    

        try {

            $response = Http::withHeaders([

                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
            ->post('https://apitest.dtes.mh.gob.sv/fesv/anulardte', [

                'ambiente' => '00',
                'idEnvio' => 1,
                'version' => 2,
                'tipoDte' => $tipoDTE,
                'documento' => $signedData['body'] ?? null

            ]);

            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response -> body() 
                
            ]);

            return $response->json();

        } catch (\Throwable $th) {

            Log::error("Error enviando DTE a Hacienda: " . $th->getMessage(), [
                'credit_note_id' =>$creditNote->id,
                'tipo_documento' => $creditNote->tipo_documento_id,
                'trace' => $th->getTraceAsString()
            ]);

            return [

                'estado' => 'ERROR',
                'mensaje' => $th->getMessage()
            ];

        }

    }

    public function sendNDVoidToHacienda ($debitNote, $signedData, $token): array 
    {

        // Obtener código de DTE directamente de la relación
        $tipoDTE = "06";
    

        try {

            $response = Http::withHeaders([

                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ])->withOptions(['verify' => false])
            ->post('https://apitest.dtes.mh.gob.sv/fesv/anulardte', [

                'ambiente' => '00',
                'idEnvio' => 1,
                'version' => 2,
                'tipoDte' => $tipoDTE,
                'documento' => $signedData['body'] ?? null

            ]);

            Log::info("Hacienda Response ({$tipoDTE})", [
                'status' => $response->status(),
                'body' => $response -> body() 
                
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