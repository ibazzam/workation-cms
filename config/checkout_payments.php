<?php

return [
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
        'local_maldivian' => ['MVR', 'USD'],
        'foreign_national' => ['USD'],
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
            'supported_currencies' => ['MVR', 'USD'],
            'mode' => env('WORKATION_PAYMENT_STRIPE_MODE', 'internal'),
            'checkout_url' => env('WORKATION_PAYMENT_STRIPE_CHECKOUT_URL', ''),
            'allowed_segments' => ['local_maldivian', 'foreign_national'],
            'webhook_secret' => env('WORKATION_PAYMENT_STRIPE_WEBHOOK_SECRET', env('APP_KEY', 'workation-stripe')),
        ],
    ],
];