<?php

declare(strict_types=1);

use CommissionApp\Model\Operation;
use PHPUnit\Framework\TestCase;

final class OperationValidationTest extends TestCase
{
    public function test_valid_values_are_normalized_and_exposed_with_types(): void
    {
        $operation = new Operation(
            '2024-12-30',
            '42',
            ' PRIVATE ',
            ' Withdraw ',
            '1000.50',
            'usd'
        );

        self::assertSame('2024-12-30', $operation->getDate());
        self::assertSame('2025-01', $operation->getIsoWeekKey());
        self::assertSame(42, $operation->getUserId());
        self::assertSame('private', $operation->getUserType());
        self::assertSame('withdraw', $operation->getOperationType());
        self::assertSame(1000.50, $operation->getAmount());
        self::assertSame('USD', $operation->getCurrency());
    }

    public function test_invalid_calendar_date_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Y-m-d');

        new Operation('2024-02-31', 1, 'private', 'withdraw', 10, 'EUR');
    }

    public function test_non_positive_user_id_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID');

        new Operation('2024-07-01', 0, 'private', 'withdraw', 10, 'EUR');
    }

    public function test_unsupported_user_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user type');

        new Operation('2024-07-01', 1, 'vip', 'withdraw', 10, 'EUR');
    }

    public function test_unsupported_operation_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('operation type');

        new Operation('2024-07-01', 1, 'private', 'transfer', 10, 'EUR');
    }

    public function test_non_positive_amount_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than zero');

        new Operation('2024-07-01', 1, 'private', 'withdraw', 0, 'EUR');
    }

    public function test_malformed_currency_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('three-letter');

        new Operation('2024-07-01', 1, 'private', 'withdraw', 10, 'EURO');
    }
}
