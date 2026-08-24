<?php

declare(strict_types=1);

use CommissionApp\Model\Operation;
use CommissionApp\Service\CommissionCalculator;
use CommissionApp\Service\CurrencyConverter;
use PHPUnit\Framework\TestCase;

final class CommissionCalculatorTest extends TestCase
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

        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_only_amount_above_weekly_free_limit_is_commissionable(): void
    {
        $calculator = $this->calculator();

        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 800.00, 'EUR'))
        );
        self::assertSame(
            0.90,
            $calculator->calculate(new Operation('2024-07-02', 1, 'private', 'withdraw', 500.00, 'EUR'))
        );
    }

    public function test_fourth_private_withdrawal_is_commissionable_even_below_amount_limit(): void
    {
        $calculator = $this->calculator();

        foreach (['2024-07-01', '2024-07-02', '2024-07-03'] as $date) {
            self::assertSame(
                0.00,
                $calculator->calculate(new Operation($date, 1, 'private', 'withdraw', 100.00, 'EUR'))
            );
        }

        self::assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-04', 1, 'private', 'withdraw', 100.00, 'EUR'))
        );
    }

    public function test_private_allowance_resets_in_a_new_week(): void
    {
        $calculator = $this->calculator();

        $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR'));
        self::assertSame(
            1.50,
            $calculator->calculate(new Operation('2024-07-02', 1, 'private', 'withdraw', 500.00, 'EUR'))
        );
        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-08', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_weekly_state_is_isolated_by_user_and_iso_week_even_for_unordered_rows(): void
    {
        $calculator = $this->calculator();

        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-08', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
        self::assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-09', 1, 'private', 'withdraw', 100.00, 'EUR'))
        );
        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-09', 2, 'private', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_iso_week_spanning_calendar_year_shares_one_allowance(): void
    {
        $calculator = $this->calculator();

        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-12-30', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
        self::assertSame(
            0.30,
            $calculator->calculate(new Operation('2025-01-01', 1, 'private', 'withdraw', 100.00, 'EUR'))
        );
        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2025-01-06', 1, 'private', 'withdraw', 1000.00, 'EUR'))
        );
    }

    public function test_business_withdrawal_and_deposit_commissions_use_half_up_rounding(): void
    {
        $calculator = $this->calculator();

        self::assertSame(
            5.00,
            $calculator->calculate(new Operation('2024-07-01', 2, 'business', 'withdraw', 1000.00, 'EUR'))
        );
        self::assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'deposit', 1000.00, 'EUR'))
        );
        self::assertSame(
            0.01,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'deposit', 16.67, 'EUR'))
        );
    }

    public function test_private_allowance_is_evaluated_in_eur_for_foreign_currency(): void
    {
        $calculator = $this->calculator();

        self::assertSame(
            0.00,
            $calculator->calculate(new Operation('2024-07-01', 1, 'private', 'withdraw', 1149.70, 'USD'))
        );
        self::assertSame(
            0.30,
            $calculator->calculate(new Operation('2024-07-02', 1, 'private', 'withdraw', 100.00, 'USD'))
        );
    }
}
