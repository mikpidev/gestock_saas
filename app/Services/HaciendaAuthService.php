<?php

namespace App\Services;

use App\Models\HaciendaToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HaciendaAuthService
{
    protected $url = 'https://apitest.dtes.mh.gob.sv/seguridad/auth';
    protected $user;
    protected $pass;

    public function __construct()
    {
        $this->user = env('HACIENDA_USER');
        $this->pass = env('HACIENDA_PASS');
    }

    /**
     * Obtiene un token válido de Hacienda.
     */
    public function getToken()
    {
        $token = HaciendaToken::latest()->first();

        if ($token && $this->isTokenValid($token->token, $token->expires_at)) {
            return $token->token;
        }

        return $this->generateNewToken();
    }

    /**
     * Genera un nuevo token y lo guarda en base de datos.
     */
    public function generateNewToken()
    {
        try {
            $response = Http::asForm()
                ->withOptions(['verify' => false])
                ->post($this->url, [
                    'user' => $this->user,
                    'pwd' => $this->pass,
                ]);
        } catch (\Exception $e) {
            throw new \Exception('Error de conexión con Hacienda: ' . $e->getMessage());
        }

        if ($response->failed()) {
            Log::error('Error en la petición de token a Hacienda', ['body' => $response->body()]);
            throw new \Exception('Error en la petición de token a Hacienda');
        }

        $data = $response->json();
        $tokenValue = $data['body']['token'] ?? $data['token'] ?? null;

        if (!$tokenValue) {
            Log::error('Token no encontrado en la respuesta de Hacienda', ['data' => $data]);
            throw new \Exception('Token no encontrado en la respuesta de Hacienda');
        }

        // Limpiar tokens antiguos
        HaciendaToken::truncate();

        // Guardar token con tiempo real de expiración
        $token = HaciendaToken::create([
            'token' => $tokenValue,
            'expires_at' => Carbon::now()->addMinutes(5), // token válido 5 min
        ]);

        Log::info('Nuevo token generado', ['token' => substr($tokenValue, 0, 20) . '...']);

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
