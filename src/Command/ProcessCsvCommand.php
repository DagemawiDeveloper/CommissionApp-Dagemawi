<?php

namespace CommissionApp\Command;

use CommissionApp\Model\Operation;
use CommissionApp\Service\CommissionCalculator;
use League\Csv\Reader;

class ProcessCsvCommand
{
    private $commissionCalculator;

    public function __construct(CommissionCalculator $commissionCalculator)
    {
        $this->commissionCalculator = $commissionCalculator;
    }

    public function execute($filePath)
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            $operation = new Operation(
                $record['date'],
                $record['userId'],
                $record['userType'],
                $record['operationType'],
                $record['amount'],
                $record['currency']
            );

            $commission = $this->commissionCalculator->calculate($operation);

            // Print only the commission fee, formatted to 2 decimal places
            echo number_format($commission, 2, '.', '') . PHP_EOL;
        }
    }
}