<?php

declare(strict_types=1);

use CommissionApp\Command\ProcessCsvCommand;
use CommissionApp\Service\CommissionCalculator;
use CommissionApp\Service\CurrencyConverter;
use PHPUnit\Framework\TestCase;

final class ProcessCsvCommandTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function test_process_returns_formatted_fees_and_execute_writes_each_line(): void
    {
        $file = $this->csv(
            "date,userId,userType,operationType,amount,currency\n"
            . "2024-07-01,1,private,deposit,1000,EUR\n"
            . "2024-07-02,2,business,withdraw,1000,EUR\n"
        );
        $written = [];

        $results = $this->command()->execute(
            $file,
            static function (string $line) use (&$written): void {
                $written[] = $line;
            }
        );

        self::assertSame(['0.30', '5.00'], $results);
        self::assertSame($results, $written);
    }

    public function test_missing_required_header_is_reported(): void
    {
        $file = $this->csv(
            "date,userId,userType,amount,currency\n"
            . "2024-07-01,1,private,1000,EUR\n"
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('operationType');

        $this->command()->process($file);
    }

    public function test_invalid_data_identifies_the_failing_csv_row(): void
    {
        $file = $this->csv(
            "date,userId,userType,operationType,amount,currency\n"
            . "2024-07-01,1,private,deposit,1000,EUR\n"
            . "2024-02-31,2,business,withdraw,1000,EUR\n"
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid CSV row 3');

        $this->command()->process($file);
    }

    public function test_unreadable_or_missing_file_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not readable');

        $this->command()->process('/path/that/does/not/exist.csv');
    }

    private function command(): ProcessCsvCommand
    {
        return new ProcessCsvCommand(
            new CommissionCalculator(
                new CurrencyConverter([
                    'EUR' => 1,
                    'USD' => 1.1497,
                    'JPY' => 129.53,
                ])
            )
        );
    }

    private function csv(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'commission-csv-');
        self::assertNotFalse($file);
        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }
}
