<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Sale;
use App\Models\Store;
use Carbon\Carbon;

class DteQuotaService
{
    public const TIMEZONE = 'America/Guatemala';

    public const PROCESSED = 'PROCESADO';

    /**
     * Soft-block payload, or null when the store may emit another DTE.
     *
     * @return array{message: string, error: string, used: int|float, limit: int|float}|null
     */
    public function denial(Store $store): ?array
    {
        $monthlyLimit = $this->monthlyLimit($store);
        if ($monthlyLimit !== null) {
            $used = $this->processedDteCount($store);
            if ($used >= $monthlyLimit) {
                return [
                    'message' => 'Límite mensual de DTE alcanzado para esta sucursal.',
                    'error' => 'dte_monthly_limit',
                    'used' => $used,
                    'limit' => $monthlyLimit,
                ];
            }
        }

        if ($store->plan !== 'free') {
            return null;
        }

        $revenueLimit = config('plans.free.annual_revenue_limit');
        if ($revenueLimit === null) {
            return null;
        }

        $revenue = $this->annualProcessedRevenue($store);
        if ($revenue >= (float) $revenueLimit) {
            return [
                'message' => 'Límite anual de facturación del plan Free alcanzado para esta sucursal.',
                'error' => 'annual_revenue_limit',
                'used' => $revenue,
                'limit' => (float) $revenueLimit,
            ];
        }

        return null;
    }

    /**
     * Column override wins when it is set. Null falls back to config.
     * Config null (empresarial) means unlimited.
     */
    public function monthlyLimit(Store $store): ?int
    {
        if ($store->dte_monthly_limit !== null) {
            return (int) $store->dte_monthly_limit;
        }

        $configured = config('plans.'.$store->plan.'.dte_monthly_limit');

        return $configured === null ? null : (int) $configured;
    }

    /**
     * Month-to-date DTE usage for the store dashboard.
     *
     * @return array{
     *     used: int,
     *     limit: int|null,
     *     remaining: int|null,
     *     plan: string|null,
     *     pct: int|null,
     *     warning_level: string,
     *     message: string|null
     * }
     */
    public function usageSummary(Store $store): array
    {
        $used = $this->processedDteCount($store);
        $limit = $this->monthlyLimit($store);
        $plan = $store->plan;

        if ($limit === null) {
            return [
                'used' => $used,
                'limit' => null,
                'remaining' => null,
                'plan' => $plan,
                'pct' => null,
                'warning_level' => 'ok',
                'message' => null,
            ];
        }

        $pct = $limit === 0 ? 100 : (int) round(($used / $limit) * 100);
        $level = 'ok';
        if ($pct >= 80 || ($plan === 'free' && $used >= 40)) {
            $level = 'critical';
        } elseif ($pct >= 60) {
            $level = 'warn';
        }

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
            'plan' => $plan,
            'pct' => $pct,
            'warning_level' => $level,
            'message' => $level === 'ok' ? null : $this->usageMessage($plan, $used, $limit),
        ];
    }

    public function processedDteCount(Store $store, ?Carbon $moment = null): int
    {
        [$start, $end] = $this->bounds($moment ?? Carbon::now(self::TIMEZONE), 'month');

        return $this->countProcessed(Sale::class, $store->id, $start, $end)
            + $this->countProcessed(CreditNote::class, $store->id, $start, $end)
            + $this->countProcessed(DebitNote::class, $store->id, $start, $end);
    }

    public function annualProcessedRevenue(Store $store, ?Carbon $moment = null): float
    {
        [$start, $end] = $this->bounds($moment ?? Carbon::now(self::TIMEZONE), 'year');

        return (float) Sale::query()
            ->where('store_id', $store->id)
            ->where('dte_status', self::PROCESSED)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');
    }

    /**
     * @return array{0: string, 1: string} UTC timestamps
     */
    public function bounds(Carbon $moment, string $unit): array
    {
        $local = $moment->copy()->timezone(self::TIMEZONE);
        $start = $unit === 'year' ? $local->copy()->startOfYear() : $local->copy()->startOfMonth();
        $end = $unit === 'year' ? $local->copy()->endOfYear() : $local->copy()->endOfMonth();

        return [
            $start->utc()->format('Y-m-d H:i:s'),
            $end->utc()->format('Y-m-d H:i:s'),
        ];
    }

    private function usageMessage(?string $plan, int $used, int $limit): string
    {
        $hint = match ($plan) {
            'free' => 'Actualiza a Basic ('.config('plans.basic.dte_monthly_limit').' DTE/mes) o contacta a soporte.',
            'basic' => 'Actualiza a Premium ('.config('plans.premium.dte_monthly_limit').' DTE/mes) o contacta a soporte.',
            'premium' => 'Actualiza a Empresarial (DTE ilimitados) o contacta a soporte.',
            default => 'Contacta a soporte para ampliar tu plan.',
        };

        return "Llevas {$used} de {$limit} DTE este mes. {$hint}";
    }

    /**
     * @param  class-string<Sale|CreditNote|DebitNote>  $model
     */
    private function countProcessed(string $model, int $storeId, string $start, string $end): int
    {
        return $model::query()
            ->where('store_id', $storeId)
            ->where('dte_status', self::PROCESSED)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }
}
