<?php

namespace App\Services;

use App\Models\HaciendaToken;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HaciendaAuthService
{
    protected $url = 'https://apitest.dtes.mh.gob.sv/seguridad/auth';
    protected $user;
    protected $pass;

    public function __construct()
    {
        // Asegurarse que la URL base está bien definida
        $this->user = env('HACIENDA_USER');
        $this->pass = env('HACIENDA_PASS');
    }

    public function getToken()
    {
        $token = HaciendaToken::latest()->first();

        if ($token && $token->expires_at->isFuture()) {
            return $token->token;
        }

        return $this->generateNewToken();
    }

    public function generateNewToken()
    {
        // Debug: mostrar URL y datos antes de enviar
        // dd($this->url, $this->user, $this->pass);

        try {
            $response = Http::asForm()
                ->withOptions(['verify' => 'C:\docker\temp\04142309731011.crt'])
                ->post($this->url, [
                    'user' => $this->user,
                    'pwd' => $this->pass,
                ]);
        } catch (\Exception $e) {
            throw new \Exception('Error de conexión: ' . $e->getMessage());
        }

        // Mostrar cuerpo crudo si falla para debugging
        if ($response->failed()) {
            dd('Error en la petición', $response->body());
        }

        $data = $response->json();

        // Ajuste: revisar si el token realmente viene en esta ruta
        $tokenValue = $data['body']['token'] ?? $data['token'] ?? null;

        if (!$tokenValue) {
            dd('Token no encontrado en la respuesta de Hacienda', $data);
        }

        $token = HaciendaToken::create([
            'token' => $tokenValue,
            'expires_at' => Carbon::now()->addHours(47), // margen de seguridad
        ]);

        return $token->token;
    }
}
