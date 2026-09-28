<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Sale and DTE debug lines share the sale_dte stack (storage/logs/gestock-sale-dte.log).
 * Context passed here is already reduced; helpers below only keep status fields.
 */
class SaleDteLog
{
    public const CHANNEL = 'sale_dte';

    public static function info(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->error($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->warning($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->debug($message, $context);
    }

    /**
     * Short hash so a log can show that a token existed without the token bytes.
     */
    public static function tokenPreview(mixed $token): ?string
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        return substr(hash('sha256', $token), 0, 12);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function mhSummary(array $payload): array
    {
        $summary = [];

        $estado = $payload['estado'] ?? null;
        if (is_string($estado) && $estado !== '' && !self::looksLikePayload($estado)) {
            $summary['estado'] = mb_substr($estado, 0, 32);
        }

        $code = $payload['codigoMsg'] ?? $payload['codigo_msg'] ?? null;
        if ((is_int($code) || (is_string($code) && $code !== '')) && !self::looksLikePayload((string) $code)) {
            $summary['mh_code'] = is_string($code) ? mb_substr($code, 0, 32) : $code;
        }

        $message = $payload['descripcionMsg'] ?? $payload['mensaje'] ?? $payload['descripcion_msg'] ?? null;
        if (is_string($message) && $message !== '' && !self::looksLikePayload($message)) {
            $summary['mh_message'] = mb_substr($message, 0, 160);
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function httpMh(Response $response, array $extra = []): array
    {
        $json = $response->json();

        return array_merge($extra, [
            'http_status' => $response->status(),
        ], self::mhSummary(is_array($json) ? $json : []));
    }

    public static function safeMessage(string $message): string
    {
        if (self::looksLikePayload($message)) {
            return 'omitted payload';
        }

        return mb_substr($message, 0, 300);
    }

    public static function looksLikePayload(string $value): bool
    {
        return str_contains($value, '<')
            || str_contains($value, '{')
            || str_contains($value, 'eyJ');
    }
}
