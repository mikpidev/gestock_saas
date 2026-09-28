<?php

namespace App\Http\Middleware;

use App\Models\Sale;
use Closure;
use Illuminate\Http\Request;
use App\Support\SaleDteLog;
use Symfony\Component\HttpFoundation\Response;

class LogSaleStoreOutcome
{
    /** @var array<int, true> */
    private static array $hookedDispatchers = [];

    /** @var array<int, true> */
    private static array $createdIds = [];

    public function handle(Request $request, Closure $next): Response
    {
        $this->listenForCreatedSales();
        self::$createdIds = [];

        $response = $next($request);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $saleId = $this->saleIdFromResponse($response);
        if ($saleId === null) {
            return $response;
        }

        $sale = Sale::query()->find($saleId);
        if ($sale === null) {
            return $response;
        }

        SaleDteLog::info('gestock.sale', [
            'outcome' => isset(self::$createdIds[$saleId]) ? 'created' : 'replay',
            'user_id' => $request->user()?->id,
            'store_id' => $sale->store_id,
            'sale_id' => $sale->id,
            'idempotency_key' => $this->idempotencyKeyForLog($sale, $request),
            'correlativo' => $this->correlativoFromControl($sale->numero_control),
            'status_http' => $response->getStatusCode(),
        ]);

        return $response;
    }

    private function listenForCreatedSales(): void
    {
        $dispatcher = Sale::getEventDispatcher();
        if ($dispatcher === null) {
            return;
        }

        $dispatcherId = spl_object_id($dispatcher);
        if (isset(self::$hookedDispatchers[$dispatcherId])) {
            return;
        }

        $dispatcher->listen('eloquent.created: '.Sale::class, function (Sale $sale) {
            self::$createdIds[$sale->id] = true;
        });

        self::$hookedDispatchers[$dispatcherId] = true;
    }

    private function saleIdFromResponse(Response $response): ?int
    {
        $payload = json_decode($response->getContent(), true);
        if (!is_array($payload)) {
            return null;
        }

        $saleId = $payload['sale_id'] ?? null;
        if (is_int($saleId) || (is_string($saleId) && ctype_digit($saleId))) {
            return (int) $saleId;
        }

        $ticketUrl = $payload['ticket_url'] ?? null;
        if (is_string($ticketUrl) && preg_match('#/sales/(\d+)/print$#', $ticketUrl, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function idempotencyKeyForLog(Sale $sale, Request $request): ?string
    {
        $stored = $sale->getAttribute('idempotency_key');
        $key = is_string($stored) ? trim($stored) : null;

        if ($key === null || $key === '') {
            $header = $request->headers->get('Idempotency-Key');
            $key = is_string($header) ? trim($header) : null;
        }

        if ($key === null || $key === '') {
            return null;
        }

        if (strlen($key) <= 64) {
            return $key;
        }

        return hash('sha256', $key);
    }

    private function correlativoFromControl(mixed $numeroControl): ?int
    {
        if (!is_string($numeroControl) || !preg_match('/-(\d+)$/', $numeroControl, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
