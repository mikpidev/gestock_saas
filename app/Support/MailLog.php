<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * One metadata line per DTE mail attempt.
 * Channel gestock_mail writes storage/logs/gestock-mail.log.
 * It is not on the default stack and it is not on sale_dte.
 */
class MailLog
{
    public const CHANNEL = 'gestock_mail';

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function attempt(array $meta): void
    {
        $resultado = $meta['resultado'] ?? 'failed';
        if (!in_array($resultado, ['sent', 'failed', 'skipped'], true)) {
            $resultado = 'failed';
        }

        $context = [
            'sale_id' => self::nullableInt($meta['sale_id'] ?? null),
            'dte_status' => self::shortToken($meta['dte_status'] ?? null, 32),
            'correlativo' => self::correlativoFromControl($meta['numero_control'] ?? null),
            'resultado' => $resultado,
        ];

        $http = $meta['http_status'] ?? null;
        if (is_int($http) && $http >= 100 && $http <= 599) {
            $context['http_status'] = $http;
        }

        if (isset($meta['error']) && is_string($meta['error']) && $meta['error'] !== '') {
            $context['error'] = self::safeMessage($meta['error']);
        }

        $level = $resultado === 'failed' ? 'error' : 'info';
        Log::channel(self::CHANNEL)->log($level, 'gestock.mail', $context);
    }

    public static function correlativoFromControl(mixed $numeroControl): ?int
    {
        if (!is_string($numeroControl) || !preg_match('/-(\d+)$/', $numeroControl, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    public static function safeMessage(string $message): string
    {
        if (self::looksLikeSecret($message)) {
            return 'omitted';
        }

        return mb_substr($message, 0, 80);
    }

    public static function looksLikeSecret(string $value): bool
    {
        return str_contains($value, '<')
            || str_contains($value, '{')
            || str_contains($value, 'eyJ')
            || str_contains($value, '@');
    }

    private static function nullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return null;
    }

    private static function shortToken(mixed $value, int $limit): ?string
    {
        if (!is_string($value) || $value === '' || self::looksLikeSecret($value)) {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }
}
