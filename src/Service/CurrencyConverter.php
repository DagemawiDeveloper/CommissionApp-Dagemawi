<?php

namespace CommissionApp\Service;

use InvalidArgumentException;

/**
 * Converts amounts between currencies using rates expressed relative to EUR.
 */
class CurrencyConverter
{
    /** @var array<string, float> */
    private array $rates;

    /**
     * @param array<string, int|float> $rates
     */
    public function __construct(array $rates)
    {
        if (!isset($rates['EUR'])) {
            $rates['EUR'] = 1.0;
        }

        foreach ($rates as $currency => $rate) {
            if (!is_numeric($rate) || (float) $rate <= 0) {
                throw new InvalidArgumentException("Exchange rate for {$currency} must be greater than zero.");
            }
        }

        $this->rates = array_map('floatval', $rates);
    }

    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $this->assertSupported($fromCurrency);
        $this->assertSupported($toCurrency);

        $amountInEur = $amount / $this->rates[$fromCurrency];

        return $amountInEur * $this->rates[$toCurrency];
    }

    private function assertSupported(string $currency): void
    {
        if (!array_key_exists($currency, $this->rates)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }
}
