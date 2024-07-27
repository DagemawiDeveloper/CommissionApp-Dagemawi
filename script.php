<?php

require 'vendor/autoload.php';

use CommissionApp\Command\ProcessCsvCommand;
use CommissionApp\Service\CommissionCalculator;
use CommissionApp\Service\CurrencyConverter;

$rates = [
    'EUR' => 1,
    'USD' => 1.1497,
    'JPY' => 129.53
];

$currencyConverter = new CurrencyConverter($rates);
$commissionCalculator = new CommissionCalculator($currencyConverter);
$processCsvCommand = new ProcessCsvCommand($commissionCalculator);

$processCsvCommand->execute($argv[1]);