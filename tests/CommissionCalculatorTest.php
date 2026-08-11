<?php

use CommissionApp\Model\Operation;
use CommissionApp\Service\CommissionCalculator;
use CommissionApp\Service\CurrencyConverter;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    private function calculator(): CommissionCalculator
    {
        return new CommissionCalculator(
            new CurrencyConverter([
                'EUR' => 1,
                'USD' => 1.1497,
                'JPY' => 129.53,
            ])
        );
    }

    public function test_private_withdrawal_is_free_inside_weekly_amount_allowance(): void
    {
        $calculator = $this->calculator();

        $operation = new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR');

        $this->assertSame(0.00, $calculator->calculate($operation));
    }

    public function test_only_amount_above_weekly_free_limit_is_commissionable(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 800.00, 'EUR'))
        );

        // Only €300 of this €500 withdrawal exceeds the remaining €200 allowance.
        $this->assertSame(
            0.90,
            $calculator->calculate(new Operation('2024-07-02', 1, 'private', 'withdraw', 500.00, 'EUR'))
        );
    }

    public function test_fourth_private_withdrawal_is_commissionable_even_below_amount_limit(): void
    {
        $calculator = $this->calculator();

        foreach (['2024-07-01', '2024-07-02', '2024-07-03'] as $date) {
            $this->assertSame(
                0.00,
                $calculator->calculate(new Operation($date, 1, 'private', 'withdraw', 100.00, 'EUR'))
            );
        }

        // The amount allowance still has room, but the three free operations are used.
        $this->assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-04', 1, 'private', 'withdraw', 100.00, 'EUR'))
        );
    }

    public function test_private_allowance_resets_in_a_new_week(): void
    {
        $calculator = $this->calculator();

        $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR'));
        $this->assertSame(
            1.50,
            $calculator->calculate(new Operation('2024-07-02', 1, 'private', 'withdraw', 500.00, 'EUR'))
        );

        $this->assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-08', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_private_weekly_state_is_isolated_per_user(): void
    {
        $calculator = $this->calculator();

        $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR'));

        $this->assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-02', 2, 'private', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_business_withdrawal_commission(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(
            5.00,
            $calculator->calculate(new Operation('2024-07-01', 2, 'business', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_deposit_commission(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'deposit', 1000.00, 'EUR'))
        );
    }

    public function test_private_allowance_is_evaluated_in_eur_for_foreign_currency(): void
    {
        $calculator = $this->calculator();

        // 1149.70 USD converts to exactly 1000 EUR at the configured rate.
        $this->assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1149.70, 'USD'))
        );

        // The weekly EUR allowance is exhausted, so this withdrawal is fully commissionable.
        $this->assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-02', 1, 'private', 'withdraw', 100.00, 'USD'))
        );
    }
}
