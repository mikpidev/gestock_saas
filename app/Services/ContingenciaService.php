<?php
namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Contingencia;

class ContingenciaService
{
    public function sendContingencia(
        Contingencia $contingencia,
        array $signedData,
        string $token
    ): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Content-Type'  => 'application/json'
            ])->withOptions(['verify' => false])
              ->post(
                  'https://apitest.dtes.mh.gob.sv/fesv/contingencia',[
                        'nit' => $contingencia->store->taxInfo->nit,
                        'documento' => $signedData['body'] ?? null
                  ]);

            Log::info('Respuesta MH Contingencia', [
                'contingencia_id' => $contingencia->id,
                'status'          => $response->status(),
                'body'            => $response->json()
            ]);

            return $response->json();

        } catch (\Throwable $e) {
            Log::error('Error contingencia MH', [
                'contingencia_id' => $contingencia->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}

