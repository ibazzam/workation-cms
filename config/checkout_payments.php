<?php

$parseCurrencyList = static function (?string $csv, array $fallback): array {
    $raw = trim((string) ($csv ?? ''));
    if ($raw === '') {
        return array_values(array_unique(array_map(static fn (string $currency): string => strtoupper(trim($currency)), $fallback)));
    }

    $tokens = preg_split('/\s*,\s*/', $raw) ?: [];
    $normalized = array_values(array_unique(array_filter(array_map(static fn (string $currency): string => strtoupper(trim($currency)), $tokens), static fn (string $currency): bool => $currency !== '')));

    return $normalized !== []
        ? $normalized
        : array_values(array_unique(array_map(static fn (string $currency): string => strtoupper(trim($currency)), $fallback)));
};

$foreignAllowedCurrencies = $parseCurrencyList(
    env('WORKATION_PAYMENT_FOREIGN_ALLOWED_CURRENCIES', 'USD'),
    ['USD']
);

$localAllowedCurrencies = $parseCurrencyList(
    env('WORKATION_PAYMENT_LOCAL_ALLOWED_CURRENCIES', 'MVR,USD'),
    ['MVR', 'USD']
);

$stripeSupportedCurrencies = $parseCurrencyList(
    env(
        'WORKATION_PAYMENT_STRIPE_SUPPORTED_CURRENCIES',
        implode(',', array_values(array_unique(array_merge(['MVR'], $foreignAllowedCurrencies))))
    ),
    array_values(array_unique(array_merge(['MVR'], $foreignAllowedCurrencies)))
);

return [
    // FX conversion uses this base: each rate means "how many MVR for 1 unit of that currency".
    'fx_base_currency' => strtoupper(trim((string) env('WORKATION_PAYMENT_FX_BASE_CURRENCY', 'MVR'))),
    'fx_rates' => [
        'MVR' => 1.0,
        'USD' => (float) env('WORKATION_PAYMENT_FX_MVR_PER_USD', 15.42),
    ],

    'commission_rate_percent' => 12.0,

    'provider_fee_rate_percent' => [
        'stripe' => 6.5,
        'mib' => 4.0,
        'bml' => 4.0,
    ],

    'provider_labels' => [
        'stripe' => 'Stripe',
        'mib' => 'MIB',
        'bml' => 'BML',
    ],

    'fallback_currency_by_segment' => [
        'local_maldivian' => 'MVR',
        'foreign_national' => 'USD',
    ],

    'global_segment_currency_restrictions' => [
        // Locals can settle in MVR or choose Stripe-supported USD checkout.
        'local_maldivian' => $localAllowedCurrencies,
        // Foreign currencies are configurable and default to USD.
        'foreign_national' => $foreignAllowedCurrencies,
    ],

    // Ordered by preference per customer segment.
    'gateway_priority_by_segment' => [
        'local_maldivian' => ['mib_mvr', 'bml_mvr', 'stripe'],
        'foreign_national' => ['stripe', 'mib_usd', 'bml_usd'],
    ],

    'gateways' => [
        'mib_mvr' => [
            'label' => env('WORKATION_PAYMENT_MIB_MVR_LABEL', 'MIB MVR Gateway'),
            'provider' => 'mib',
            'currency' => 'MVR',
            'mode' => env('WORKATION_PAYMENT_MIB_MVR_MODE', 'internal'),
            'checkout_url' => env('WORKATION_PAYMENT_MIB_MVR_CHECKOUT_URL', ''),
            'allowed_segments' => ['local_maldivian'],
            'webhook_secret' => env('WORKATION_PAYMENT_MIB_MVR_WEBHOOK_SECRET', env('APP_KEY', 'workation-mib-mvr')),
        ],
        'mib_usd' => [
            'label' => env('WORKATION_PAYMENT_MIB_USD_LABEL', 'MIB USD Gateway'),
            'provider' => 'mib',
            'currency' => 'USD',
            'mode' => env('WORKATION_PAYMENT_MIB_USD_MODE', 'internal'),
            'checkout_url' => env('WORKATION_PAYMENT_MIB_USD_CHECKOUT_URL', ''),
            'allowed_segments' => ['foreign_national'],
            'webhook_secret' => env('WORKATION_PAYMENT_MIB_USD_WEBHOOK_SECRET', env('APP_KEY', 'workation-mib-usd')),
        ],
        'bml_mvr' => [
            'label' => env('WORKATION_PAYMENT_BML_MVR_LABEL', 'BML MVR Gateway'),
            'provider' => 'bml',
            'currency' => 'MVR',
            'mode' => env('WORKATION_PAYMENT_BML_MVR_MODE', 'internal'),
            'checkout_url' => env('WORKATION_PAYMENT_BML_MVR_CHECKOUT_URL', ''),
            'allowed_segments' => ['local_maldivian'],
            'webhook_secret' => env('WORKATION_PAYMENT_BML_MVR_WEBHOOK_SECRET', env('APP_KEY', 'workation-bml-mvr')),
        ],
        'bml_usd' => [
            'label' => env('WORKATION_PAYMENT_BML_USD_LABEL', 'BML USD Gateway'),
            'provider' => 'bml',
            'currency' => 'USD',
            'mode' => env('WORKATION_PAYMENT_BML_USD_MODE', 'internal'),
            'checkout_url' => env('WORKATION_PAYMENT_BML_USD_CHECKOUT_URL', ''),
            'allowed_segments' => ['foreign_national'],
            'webhook_secret' => env('WORKATION_PAYMENT_BML_USD_WEBHOOK_SECRET', env('APP_KEY', 'workation-bml-usd')),
        ],
        'stripe' => [
            'label' => env('WORKATION_PAYMENT_STRIPE_LABEL', 'Stripe Checkout'),
            'provider' => 'stripe',
            'currency' => null,
            // Multi-currency is controlled by env, e.g. "MVR,USD,EUR,GBP,AED".
            'supported_currencies' => $stripeSupportedCurrencies,
            'mode' => env('WORKATION_PAYMENT_STRIPE_MODE', 'internal'),
            'checkout_url' => env('WORKATION_PAYMENT_STRIPE_CHECKOUT_URL', ''),
            'allowed_segments' => ['local_maldivian', 'foreign_national'],
            'webhook_secret' => env('WORKATION_PAYMENT_STRIPE_WEBHOOK_SECRET', env('APP_KEY', 'workation-stripe')),
        ],
    ],
];
