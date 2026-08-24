<?php

declare(strict_types=1);

namespace CommissionApp\Command;

use CommissionApp\Model\Operation;
use CommissionApp\Service\CommissionCalculator;
use League\Csv\Reader;
use RuntimeException;
use Throwable;

final class ProcessCsvCommand
{
    private const REQUIRED_COLUMNS = [
        'date',
        'userId',
        'userType',
        'operationType',
        'amount',
        'currency',
    ];

    public function __construct(private readonly CommissionCalculator $commissionCalculator)
    {
    }

    /**
     * @return list<string>
     */
    public function process(string $filePath): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("CSV file is not readable: {$filePath}");
        }

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $header = $csv->getHeader();
        } catch (Throwable $exception) {
            throw new RuntimeException("Unable to read CSV file: {$exception->getMessage()}", 0, $exception);
        }

        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, $header));

        if ($missing !== []) {
            throw new RuntimeException('CSV is missing required columns: ' . implode(', ', $missing));
        }

        $results = [];
        $rowNumber = 2;

        foreach ($csv->getRecords() as $record) {
            try {
                $operation = new Operation(
                    (string) $record['date'],
                    $record['userId'],
                    (string) $record['userType'],
                    (string) $record['operationType'],
                    $record['amount'],
                    (string) $record['currency']
                );
                $commission = $this->commissionCalculator->calculate($operation);
                $results[] = number_format($commission, 2, '.', '');
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    sprintf('Invalid CSV row %d: %s', $rowNumber, $exception->getMessage()),
                    0,
                    $exception
                );
            }

            $rowNumber++;
        }

        return $results;
    }

    /**
     * @param null|callable(string):void $writeLine
     * @return list<string>
     */
    public function execute(string $filePath, ?callable $writeLine = null): array
    {
        $results = $this->process($filePath);
        $writeLine ??= static function (string $line): void {
            echo $line . PHP_EOL;
        };

        foreach ($results as $line) {
            $writeLine($line);
        }

        return $results;
    }
}
