<?php
namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use App\Support\SaleDteLog;
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

            SaleDteLog::info('Respuesta MH Contingencia', SaleDteLog::httpMh($response, [
                'contingencia_id' => $contingencia->id,
            ]));

            return $response->json();

        } catch (\Throwable $e) {
            SaleDteLog::error('Error contingencia MH', [
                'contingencia_id' => $contingencia->id,
                'message' => SaleDteLog::safeMessage($e->getMessage()),
            ]);

            throw $e;
        }
    }
}

