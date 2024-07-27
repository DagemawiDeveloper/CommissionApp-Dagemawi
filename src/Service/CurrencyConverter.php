<?php

namespace CommissionApp\Service;

/**
 * Class CurrencyConverter
 * 
 * Converts amounts between different currencies based on provided exchange rates.
 */
class CurrencyConverter
{
    /**
     * @var array An associative array of exchange rates with currency codes as keys.
     */
    private $rates;

    /**
     * CurrencyConverter constructor.
     * 
     * @param array $rates An associative array of exchange rates where the keys are currency codes
     *                     and values are the exchange rates relative to EUR.
     */
    public function __construct(array $rates)
    {
        $this->rates = $rates;
    }

    /**
     * Converts an amount from one currency to another.
     * 
     * @param float  $amount       The amount to be converted.
     * @param string $fromCurrency The currency code of the amount's current currency.
     * @param string $toCurrency   The currency code of the target currency.
     * @return float The converted amount in the target currency.
     */
    public function convert($amount, $fromCurrency, $toCurrency)
    {
        // If the source and target currencies are the same, return the amount unchanged.
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Convert the amount to EUR first, then to the target currency.
        $amountInEur = $amount / $this->rates[$fromCurrency];
        return $amountInEur * $this->rates[$toCurrency];
    }
}