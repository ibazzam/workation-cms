<?php

namespace App\Support;

use InvalidArgumentException;
use Illuminate\Support\Str;

class CheckoutPaymentRouter
{
    public const SEGMENT_LOCAL = 'local_maldivian';
    public const SEGMENT_FOREIGN = 'foreign_national';

    public static function resolveCustomerSegment(string $nationality, ?string $guestResidency): string
    {
        return ReservationPricingPolicy::isForeigner($nationality, $guestResidency)
            ? self::SEGMENT_FOREIGN
            : self::SEGMENT_LOCAL;
    }

    public static function globallyAllowedCurrencies(string $segment): array
    {
        $rules = (array) config('checkout_payments.global_segment_currency_restrictions', []);
        $allowed = $rules[$segment] ?? ($segment === self::SEGMENT_FOREIGN ? ['USD'] : ['MVR']);

        return array_values(array_unique(array_filter(array_map(
            static fn ($currency): string => strtoupper(trim((string) $currency)),
            (array) $allowed
        ), static fn (string $currency): bool => $currency !== '')));
    }

    private static function providerLabel(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $configured = (string) config('checkout_payments.provider_labels.' . $provider, '');
        if ($configured !== '') {
            return $configured;
        }

        if (strlen($provider) <= 4) {
            return strtoupper($provider);
        }

        return Str::headline(str_replace('_', ' ', $provider));
    }

    private static function fxRateToBase(string $currency): float
    {
        $currencyCode = strtoupper(trim($currency));
        $rates = (array) config('checkout_payments.fx_rates', []);
        $rate = (float) ($rates[$currencyCode] ?? 0);

        if ($rate <= 0) {
            throw new InvalidArgumentException('Missing FX rate for currency: ' . $currencyCode);
        }

        return $rate;
    }

    private static function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        if ($from === '' || $to === '') {
            throw new InvalidArgumentException('Currency conversion requires both source and target currencies.');
        }
        if ($from === $to) {
            return round(max(0, $amount), 2);
        }

        $fromRateToBase = self::fxRateToBase($from);
        $toRateToBase = self::fxRateToBase($to);

        // Rates are configured as "MVR per 1 unit" (base-relative).
        // amount(base) = amount(from) * fromRateToBase
        // amount(to)   = amount(base) / toRateToBase
        $amountInBase = max(0, $amount) * $fromRateToBase;
        $amountInTarget = $amountInBase / $toRateToBase;

        return round($amountInTarget, 2);
    }

    public static function buildPaymentQuote(array $context, ?string $requestedCurrency = null, ?string $requestedGateway = null): array
    {
        $policyContext = $context;
        $policyContext['requested_gateway'] = $requestedGateway;
        $policy = self::buildPaymentPolicy($policyContext, $requestedCurrency);

        $sourceCurrency = strtoupper(trim((string) ($context['reservation_currency'] ?? $policy['currency'] ?? 'MVR')));
        $targetCurrency = strtoupper(trim((string) ($policy['currency'] ?? $sourceCurrency)));
        $sourceAmount = round(max(0, (float) ($context['amount'] ?? 0)), 2);
        $convertedAmount = self::convertAmount($sourceAmount, $sourceCurrency, $targetCurrency);

        $sourceRateToBase = self::fxRateToBase($sourceCurrency);
        $targetRateToBase = self::fxRateToBase($targetCurrency);
        $effectiveRate = $targetRateToBase > 0 ? round($sourceRateToBase / $targetRateToBase, 8) : 1.0;

        return $policy + [
            'source_amount' => $sourceAmount,
            'source_currency' => $sourceCurrency,
            'amount' => $convertedAmount,
            'currency' => $targetCurrency,
            'fx_rate' => $effectiveRate,
            'fx_base_currency' => strtoupper(trim((string) config('checkout_payments.fx_base_currency', 'MVR'))),
            'quoted_at' => now()->toIso8601String(),
        ];
    }

    public static function availableOptions(array $context): array
    {
        $segment = self::resolveCustomerSegment(
            (string) ($context['primary_nationality'] ?? ''),
            (string) ($context['guest_residency'] ?? '')
        );

        $globalAllowedCurrencies = self::globallyAllowedCurrencies($segment);
        $gateways = (array) config('checkout_payments.gateways', []);
        $options = [];

        foreach ($gateways as $gatewayKey => $gatewayConfig) {
            $allowedSegments = array_values(array_filter(array_map(
                static fn ($value): string => trim((string) $value),
                (array) ($gatewayConfig['allowed_segments'] ?? [self::SEGMENT_LOCAL, self::SEGMENT_FOREIGN])
            )));

            if (!in_array($segment, $allowedSegments, true)) {
                continue;
            }

            $supportedCurrencies = [];
            if (!empty($gatewayConfig['supported_currencies']) && is_array($gatewayConfig['supported_currencies'])) {
                $supportedCurrencies = $gatewayConfig['supported_currencies'];
            } elseif (!empty($gatewayConfig['currency'])) {
                $supportedCurrencies = [(string) $gatewayConfig['currency']];
            }

            foreach ($supportedCurrencies as $currency) {
                $currencyCode = strtoupper(trim((string) $currency));
                if ($currencyCode === '' || !in_array($currencyCode, $globalAllowedCurrencies, true)) {
                    continue;
                }

                $gatewayMode = strtolower(trim((string) ($gatewayConfig['mode'] ?? 'internal')));
                $checkoutUrl = trim((string) ($gatewayConfig['checkout_url'] ?? ''));
                $providerKey = strtolower(trim((string) ($gatewayConfig['provider'] ?? $gatewayKey)));
                $isStripeProvider = $providerKey === 'stripe';
                // Keep Stripe visible in the chooser for both segments/currencies.
                // Stripe may use native session creation (secret key) or custom handoff URL.
                // Non-Stripe external gateways still require a checkout URL to be listed.
                $isExternallyReady = $isStripeProvider
                    ? true
                    : ($gatewayMode !== 'external' || $checkoutUrl !== '');
                if (!$isExternallyReady) {
                    continue;
                }

                $options[] = [
                    'gateway' => (string) $gatewayKey,
                    'gateway_label' => (string) ($gatewayConfig['label'] ?? Str::headline(str_replace('_', ' ', (string) $gatewayKey))),
                    'provider' => strtolower(trim((string) ($gatewayConfig['provider'] ?? $gatewayKey))),
                    'provider_label' => self::providerLabel((string) ($gatewayConfig['provider'] ?? (string) $gatewayKey)),
                    'gateway_mode' => (string) ($gatewayConfig['mode'] ?? 'internal'),
                    'checkout_url_configured' => $checkoutUrl !== '',
                    'currency' => $currencyCode,
                ];
            }
        }

        $priority = (array) config('checkout_payments.gateway_priority_by_segment.' . $segment, []);
        usort($options, static function (array $a, array $b) use ($priority): int {
            $indexA = array_search((string) ($a['gateway'] ?? ''), $priority, true);
            $indexB = array_search((string) ($b['gateway'] ?? ''), $priority, true);
            $rankA = $indexA === false ? PHP_INT_MAX : (int) $indexA;
            $rankB = $indexB === false ? PHP_INT_MAX : (int) $indexB;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strcmp((string) ($a['currency'] ?? ''), (string) ($b['currency'] ?? ''));
        });

        return $options;
    }

    public static function chooseOption(array $context, ?string $requestedGateway = null, ?string $requestedCurrency = null): array
    {
        $options = self::availableOptions($context);
        if ($options === []) {
            throw new InvalidArgumentException('No eligible payment gateway is configured for this reservation.');
        }

        $segment = self::resolveCustomerSegment(
            (string) ($context['primary_nationality'] ?? ''),
            (string) ($context['guest_residency'] ?? '')
        );

        $gateway = strtolower(trim((string) $requestedGateway));
        $currency = strtoupper(trim((string) $requestedCurrency));

        if ($gateway !== '' || $currency !== '') {
            foreach ($options as $option) {
                $gatewayKey = strtolower(trim((string) ($option['gateway'] ?? '')));
                $providerKey = strtolower(trim((string) ($option['provider'] ?? '')));
                $gatewayMatches = $gateway === '' || $gatewayKey === $gateway || $providerKey === $gateway;
                $currencyMatches = $currency === '' || (string) $option['currency'] === $currency;
                if ($gatewayMatches && $currencyMatches) {
                    return $option;
                }
            }

            throw new InvalidArgumentException('Selected gateway and currency combination is not allowed for this customer segment.');
        }

        $reservationCurrency = strtoupper(trim((string) ($context['reservation_currency'] ?? '')));
        if ($reservationCurrency !== '') {
            foreach ($options as $option) {
                if ((string) $option['currency'] === $reservationCurrency) {
                    return $option;
                }
            }
        }

        $fallbackCurrency = strtoupper(trim((string) config('checkout_payments.fallback_currency_by_segment.' . $segment, '')));
        if ($fallbackCurrency !== '') {
            foreach ($options as $option) {
                if ((string) $option['currency'] === $fallbackCurrency) {
                    return $option;
                }
            }
        }

        return $options[0];
    }

    public static function resolveCurrency(string $segment, ?string $requestedCurrency, ?string $reservationCurrency = null): string
    {
        $requested = strtoupper(trim((string) $requestedCurrency));
        $reservation = strtoupper(trim((string) $reservationCurrency));
        $allowed = self::globallyAllowedCurrencies($segment);

        $candidate = $requested !== '' ? $requested : ($reservation !== '' ? $reservation : $allowed[0]);
        if (!in_array($candidate, $allowed, true)) {
            throw new InvalidArgumentException('Selected payment currency is not allowed for this customer segment.');
        }

        return $candidate;
    }

    public static function gatewayForCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        $mapping = (array) config('checkout_payments.currency_gateway_map', []);

        return (string) ($mapping[$currency] ?? ($currency === 'USD' ? 'international_usd' : 'local_mvr'));
    }

    public static function gatewayConfig(string $gateway): array
    {
        return (array) config('checkout_payments.gateways.' . $gateway, []);
    }

    public static function buildPaymentPolicy(array $context, ?string $requestedCurrency = null): array
    {
        $option = self::chooseOption($context, trim((string) ($context['requested_gateway'] ?? '')), $requestedCurrency);
        $segment = self::resolveCustomerSegment(
            (string) ($context['primary_nationality'] ?? ''),
            (string) ($context['guest_residency'] ?? '')
        );
        $gatewayConfig = self::gatewayConfig((string) $option['gateway']);

        return [
            'segment' => $segment,
            'allowed_currencies' => self::globallyAllowedCurrencies($segment),
            'currency' => (string) $option['currency'],
            'gateway' => (string) $option['gateway'],
            'gateway_label' => (string) ($gatewayConfig['label'] ?? Str::headline(str_replace('_', ' ', (string) $option['gateway']))),
            'provider' => (string) ($option['provider'] ?? ''),
            'provider_label' => (string) ($option['provider_label'] ?? ''),
            'gateway_mode' => (string) ($gatewayConfig['mode'] ?? 'internal'),
            'checkout_url' => trim((string) ($gatewayConfig['checkout_url'] ?? '')),
            'available_options' => self::availableOptions($context),
            'customer_notice' => $segment === self::SEGMENT_LOCAL
                ? 'Local guests can use MVR bank APIs or Stripe. Local cards are blocked on MIB/BML USD APIs.'
                : 'Foreign guests are routed to non-MVR gateways (USD and other enabled foreign currencies).',
        ];
    }

    public static function createIntentPayload(array $context, ?string $requestedCurrency = null, ?string $requestedGateway = null): array
    {
        $quote = self::buildPaymentQuote($context, $requestedCurrency, $requestedGateway);

        return $quote + [
            'intent_id' => 'payint_' . Str::lower(Str::random(28)),
        ];
    }

    public static function signPayload(string $gateway, array $payload): string
    {
        $secret = (string) (self::gatewayConfig($gateway)['webhook_secret'] ?? '');
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
    }

    public static function verifySignature(string $gateway, string $rawPayload, ?string $signature): bool
    {
        $gatewayConfig = self::gatewayConfig($gateway);
        $secret = (string) ($gatewayConfig['webhook_secret'] ?? '');
        $allowUnsigned = (bool) ($gatewayConfig['allow_unsigned_webhook'] ?? false);
        if (!is_string($signature) || trim($signature) === '') {
            return $allowUnsigned;
        }
        if ($secret === '') {
            return $allowUnsigned;
        }

        $expected = hash_hmac('sha256', $rawPayload, $secret);
        return hash_equals($expected, trim($signature));
    }

    public static function verifyStripeWebhookSignature(string $rawPayload, ?string $signatureHeader, ?string $secret = null, int $toleranceSeconds = 300): bool
    {
        $resolvedSecret = trim((string) ($secret ?? (self::gatewayConfig('stripe')['webhook_secret'] ?? '')));
        $header = trim((string) $signatureHeader);
        if ($resolvedSecret === '' || $header === '' || $rawPayload === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $segment) {
            [$k, $v] = array_pad(explode('=', trim($segment), 2), 2, '');
            $key = trim((string) $k);
            $value = trim((string) $v);
            if ($key === '' || $value === '') {
                continue;
            }
            $parts[$key][] = $value;
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];
        if ($timestamp <= 0 || $signatures === []) {
            return false;
        }

        if ($toleranceSeconds > 0 && abs(time() - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $rawPayload;
        $expected = hash_hmac('sha256', $signedPayload, $resolvedSecret);
        foreach ($signatures as $candidate) {
            if (hash_equals($expected, (string) $candidate)) {
                return true;
            }
        }

        return false;
    }
}