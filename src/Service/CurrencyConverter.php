<?php

declare(strict_types=1);

namespace CommissionApp\Service;

use InvalidArgumentException;

/**
 * Converts amounts between currencies using rates expressed relative to EUR.
 */
final class CurrencyConverter
{
    /** @var array<string, float> */
    private array $rates;

    /**
     * @param array<string, int|float> $rates
     */
    public function __construct(array $rates)
    {
        $normalized = [];

        foreach ($rates as $currency => $rate) {
            $currency = strtoupper(trim((string) $currency));

            if (! preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new InvalidArgumentException("Invalid currency code: {$currency}");
            }

            if (array_key_exists($currency, $normalized)) {
                throw new InvalidArgumentException("Duplicate exchange rate for {$currency}.");
            }

            if (! is_numeric($rate) || ! is_finite((float) $rate) || (float) $rate <= 0) {
                throw new InvalidArgumentException("Exchange rate for {$currency} must be finite and greater than zero.");
            }

            $normalized[$currency] = (float) $rate;
        }

        $normalized['EUR'] ??= 1.0;

        if (abs($normalized['EUR'] - 1.0) > PHP_FLOAT_EPSILON) {
            throw new InvalidArgumentException('EUR must use the base exchange rate 1.0.');
        }

        $this->rates = $normalized;
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if (! is_finite($amount) || $amount < 0) {
            throw new InvalidArgumentException('Converted amount must be finite and non-negative.');
        }

        $fromCurrency = strtoupper(trim($fromCurrency));
        $toCurrency = strtoupper(trim($toCurrency));

        $this->assertSupported($fromCurrency);
        $this->assertSupported($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $amountInEur = $amount / $this->rates[$fromCurrency];

        return $amountInEur * $this->rates[$toCurrency];
    }

    private function assertSupported(string $currency): void
    {
        if (! array_key_exists($currency, $this->rates)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }
}
