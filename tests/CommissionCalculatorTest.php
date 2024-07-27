<?php

use PHPUnit\Framework\TestCase;
use CommissionApp\Service\CommissionCalculator;
use CommissionApp\Service\CurrencyConverter;
use CommissionApp\Model\Operation;

/**
 * Class CommissionCalculatorTest
 * 
 * Unit tests for the CommissionCalculator class.
 * These tests validate the commission calculation logic for various scenarios.
 */
class CommissionCalculatorTest extends TestCase
{
    /**
     * Tests the calculation of commissions for different scenarios.
     */
    public function testCalculatePrivateWithdraw()
    {
        // Define exchange rates for currency conversion
        $rates = [
            'EUR' => 1,
            'USD' => 1.1497,
            'JPY' => 129.53
        ];
        $currencyConverter = new CurrencyConverter($rates);
        $commissionCalculator = new CommissionCalculator($currencyConverter);

        // Test case 1: Withdrawal within the free limit
        // No commission should be applied as the amount is within the free limit of 1000 EUR
        $operation1 = new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR');
        $this->assertEquals(0.00, $commissionCalculator->calculate($operation1)); // Expected: 0.00

        // Test case 2: Withdrawal exceeding the free limit
        // The amount of 500 EUR is above the free limit, so a commission of 0.3% should be applied to the 500 EUR
        $operation2 = new Operation('2024-07-02', 1, 'private', 'withdraw', 500.00, 'EUR');
        $this->assertEquals(1.50, $commissionCalculator->calculate($operation2)); // Expected: 1.50

        // Test case 3: Another withdrawal within the same week exceeding the free limit
        // The amount of 500 EUR should incur a commission of 0.3%, since the free limit has already been exceeded
        $operation3 = new Operation('2024-07-03', 1, 'private', 'withdraw', 500.00, 'EUR');
        $this->assertEquals(1.50, $commissionCalculator->calculate($operation3)); // Expected: 1.50

        // Test case 4: Withdrawal in a new week
        // The free limit should reset at the start of the new week, so no commission should be applied to this withdrawal
        $operation4 = new Operation('2024-07-08', 1, 'private', 'withdraw', 1000.00, 'EUR');
        $this->assertEquals(0.00, $commissionCalculator->calculate($operation4)); // Expected: 0.00

        // Test case 5: Business client withdrawal
        // Business clients have a commission rate of 0.5%, so a commission should be applied to the full amount
        $operation5 = new Operation('2024-07-01', 2, 'business', 'withdraw', 1000.00, 'EUR');
        $this->assertEquals(5.00, $commissionCalculator->calculate($operation5)); // Expected: 5.00

        // Test case 6: Deposit operation
        // Deposits incur a commission of 0.03%, so the commission for a 1000 EUR deposit should be calculated
        $operation6 = new Operation('2024-07-01', 1, 'private', 'deposit', 1000.00, 'EUR');
        $this->assertEquals(0.30, $commissionCalculator->calculate($operation6)); // Expected: 0.30
    }
}