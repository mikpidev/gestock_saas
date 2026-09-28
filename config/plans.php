<?php

/**
 * Store plans. Prices are list prices in USD and do not include IVA (+IVA aparte).
 * Starter $25+IVA = one-shot setup/config fee; limited support; not monthly recurring.
 * dte_monthly_limit null means unlimited DTE.
 * annual_revenue_limit null means no annual revenue gate.
 * Legacy company plans are remapped on migration (free→basic, basic→premium,
 * premium→empresarial). The self-service `starter` plan is new and is not that
 * legacy free tier.
 */
return [
    'starter' => [
        'label' => 'Starter',
        'price_usd' => 25,
        'billing_period' => 'annual_one_shot',
        'billing_label' => 'config_fee',
        'includes_iva' => false,
        'dte_monthly_limit' => 50,
        'annual_revenue_limit' => 10000,
    ],
    'basic' => [
        'price_usd' => 25,
        'includes_iva' => false,
        'dte_monthly_limit' => 200,
        'annual_revenue_limit' => null,
    ],
    'premium' => [
        'price_usd' => 40,
        'includes_iva' => false,
        'dte_monthly_limit' => 1000,
        'annual_revenue_limit' => null,
    ],
    'empresarial' => [
        'price_usd' => 75,
        'includes_iva' => false,
        'dte_monthly_limit' => null,
        'annual_revenue_limit' => null,
    ],
];
