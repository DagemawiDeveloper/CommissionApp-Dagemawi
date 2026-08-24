<?php

declare(strict_types=1);

use CommissionApp\Service\CurrencyConverter;
use PHPUnit\Framework\TestCase;

final class CurrencyConverterTest extends TestCase
{
    public function test_conversion_normalizes_currency_codes(): void
    {
        $converter = new CurrencyConverter([
            'usd' => 1.25,
        ]);

        self::assertSame(100.0, $converter->convert(125.0, ' usd ', 'eur'));
        self::assertSame(125.0, $converter->convert(100.0, 'EUR', 'USD'));
    }

    public function test_same_currency_still_must_be_supported(): void
    {
        $converter = new CurrencyConverter(['EUR' => 1]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported currency');

        $converter->convert(10.0, 'ABC', 'ABC');
    }

    public function test_invalid_or_non_positive_rate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than zero');

        new CurrencyConverter(['USD' => 0]);
    }

    public function test_eur_base_rate_must_equal_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('base exchange rate');

        new CurrencyConverter(['EUR' => 1.1, 'USD' => 1.25]);
    }

    public function test_negative_conversion_amount_is_rejected(): void
    {
        $converter = new CurrencyConverter(['EUR' => 1, 'USD' => 1.25]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-negative');

        $converter->convert(-1.0, 'EUR', 'USD');
    }
}
