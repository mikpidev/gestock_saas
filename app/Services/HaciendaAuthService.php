<?php

namespace App\Services;

use App\Models\HaciendaToken;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Support\SaleDteLog;


class HaciendaAuthService
{
/*     protected $url = 'https://api.dtes.mh.gob.sv/seguridad/auth';
    protected $user;
    protected $pass; */

/*     public function __construct()
    {
        $this->user = config('services.hacienda.user');
        $this->pass = config('services.hacienda.pass');
    } */

    /**
     * Obtiene un token válido de Hacienda.
     */
    public function getToken(Store  $store)
    {
        $token = HaciendaToken::latest()->first();

        if ($token && $this->isTokenValid($token->token, $token->expires_at)) {
            return $token->token;
        }

        $nit = $store->taxInfo->nit;
        $api_key = $store->mh_access->api_key;
        $environment = $store->environment ?? 'default_environment';

        return $this->generateNewToken($nit, $api_key, $environment);
    }

    /**
     * Genera un nuevo token y lo guarda en base de datos.
     */
    public function generateNewToken(string $nit, string $api_key, string $environment)
    {

        try {

           if ($environment === 'Production') {
                $url = config('services.hacienda.token_url_prod') . '/seguridad/auth';
            } elseif ($environment === 'Development') {
                $url = config('services.hacienda.token_url_test') . '/seguridad/auth';
            } else {
                return [
                    'estado' => 'ERROR',
                    'mensaje' => "Ambiente inválido: {$environment}"
                ];
            }


            $response = Http::asForm()
                ->withOptions(['verify' => false])
                ->post($url, [
                    'user' => $nit,
                    'pwd' => $api_key,
                ]);
        } catch (\Exception $e) {
            throw new \Exception('Error de conexión con Hacienda: ' . $e->getMessage());
        }

        SaleDteLog::info('Obteniendo token de Hacienda', [
            'response_status' => $response->status(),
        ]);
        if ($response->failed()) {
            SaleDteLog::error('Error en la petición de token a Hacienda', [
                'response_status' => $response->status(),
            ]);
            throw new \Exception('Error en la petición de token a Hacienda');
        }

        $data = $response->json();
        $tokenValue = $data['body']['token'] ?? $data['token'] ?? null;

        SaleDteLog::info('Token obtenido de Hacienda', [
            'token_preview' => SaleDteLog::tokenPreview($tokenValue),
        ]);

        if (!$tokenValue) {
            SaleDteLog::error('Token no encontrado en la respuesta de Hacienda', [
                'response_status' => $response->status(),
            ]);
            throw new \Exception('Token no encontrado en la respuesta de Hacienda');
        
        }

        // Limpiar tokens antiguos
        HaciendaToken::truncate();

        // Guardar token con tiempo real de expiración
        $token = HaciendaToken::create([
            'token' => $tokenValue,
            'expires_at' => Carbon::now()->addMinutes(5), // token válido 5 min
        ]);

        SaleDteLog::info('Nuevo token generado', [
            'token_preview' => SaleDteLog::tokenPreview($tokenValue),
        ]);

        return $token->token;
    }

    /**
     * Valida si un token aún es válido.
     */
    protected function isTokenValid($token, $expiresAt)
    {
        // Verificar fecha de expiración
        if (!$expiresAt || Carbon::now()->greaterThanOrEqualTo($expiresAt)) {
            return false;
        }

        // Opcional: decodificar JWT y validar exp real
        try {
            $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
            if (isset($payload['exp']) && Carbon::now()->timestamp >= $payload['exp']) {
                return false;
            }
        } catch (\Throwable $e) {
            // Token inválido
            return false;
        }

        return true;
    }
}
